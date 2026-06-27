<?php

namespace App\Services;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Events\AuditEvent;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryBalanceService
{
    /**
     * Set the stock balance for a Product SKU atomically (legacy setup/direct sync).
     */
    public function setBalance(ProductSku $sku, int $onHand, int $reserved): void
    {
        DB::transaction(function () use ($sku, $onHand, $reserved) {
            // Find and lock the inventory item row for update
            /** @var InventoryItem $inventoryItem */
            $inventoryItem = InventoryItem::query()
                ->where('product_sku_id', $sku->id)
                ->lockForUpdate()
                ->firstOrCreate([
                    'product_sku_id' => $sku->id,
                ]);

            // Mutate balance (validates invariants internally)
            $inventoryItem->setBalance($onHand, $reserved);
            $inventoryItem->save();

            // Synchronize the parent SKU's cached stock quantity
            $sku->stock_quantity = $inventoryItem->available_quantity;
            $sku->save();
        });
    }

    /**
     * Record a stock-in event.
     */
    public function stockIn(ProductSku $sku, int $quantity, InventoryMovementReason $reason, array $options = []): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock-in quantity must be positive.');
        }

        return $this->recordMovement($sku, $quantity, InventoryMovementType::STOCK_IN, InventoryDirection::IN, $reason, $options);
    }

    /**
     * Record a stock-out event.
     */
    public function stockOut(ProductSku $sku, int $quantity, InventoryMovementReason $reason, array $options = []): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock-out quantity must be positive.');
        }

        return $this->recordMovement($sku, $quantity, InventoryMovementType::STOCK_OUT, InventoryDirection::OUT, $reason, $options);
    }

    /**
     * Record a manual stock adjustment.
     */
    public function adjust(ProductSku $sku, int $newOnHand, int $newReserved, InventoryMovementReason $reason, array $options = []): InventoryMovement
    {
        return DB::transaction(function () use ($sku, $newOnHand, $newReserved, $reason, $options) {
            // Acquire SELECT ... FOR UPDATE lock on the InventoryItem row
            /** @var InventoryItem $inventoryItem */
            $inventoryItem = InventoryItem::query()
                ->where('product_sku_id', $sku->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency check early in adjust to handle retries cleanly
            $idempotencyKey = $options['idempotency_key'] ?? null;
            if ($idempotencyKey !== null) {
                /** @var InventoryMovement|null $existing */
                $existing = InventoryMovement::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $currentOnHand = $inventoryItem->on_hand_quantity;
            $currentReserved = $inventoryItem->reserved_quantity;

            $onHandDelta = $newOnHand - $currentOnHand;
            $reservedDelta = $newReserved - $currentReserved;

            if ($onHandDelta === 0 && $reservedDelta === 0) {
                throw new InvalidArgumentException('Manual adjustment must change either the on-hand or reserved balance.');
            }

            $quantity = max(abs($onHandDelta), abs($reservedDelta));

            // Resolve actor early
            $actorId = $options['created_by_user_id'] ?? Auth::id();
            $actor = $actorId ? (User::find($actorId) ?: Auth::user()) : null;

            $mergedOptions = array_merge($options, [
                'adjusted_on_hand' => $newOnHand,
                'adjusted_reserved' => $newReserved,
                'created_by_user_id' => $actorId,
                'actor' => $actor,
            ]);

            return $this->recordMovement($sku, $quantity, InventoryMovementType::MANUAL_ADJUSTMENT, InventoryDirection::ADJUST, $reason, $mergedOptions);
        });
    }

    /**
     * Atomic movement engine (append-only primitive).
     */
    public function recordMovement(
        ProductSku $sku,
        int $quantity,
        InventoryMovementType $type,
        InventoryDirection $direction,
        InventoryMovementReason $reason,
        array $options = []
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Movement quantity must be positive.');
        }

        return DB::transaction(function () use ($sku, $quantity, $type, $direction, $reason, $options) {
            // Find parent InventoryItem and lock it for update
            /** @var InventoryItem $inventoryItem */
            $inventoryItem = InventoryItem::query()
                ->where('product_sku_id', $sku->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency Race Protection: check for existing key inside locked transaction block
            $idempotencyKey = $options['idempotency_key'] ?? null;
            if ($idempotencyKey !== null) {
                /** @var InventoryMovement|null $existing */
                $existing = InventoryMovement::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            // Capture snapshots BEFORE update
            $beforeOnHand = $inventoryItem->on_hand_quantity;
            $beforeReserved = $inventoryItem->reserved_quantity;
            $beforeAvailable = $inventoryItem->available_quantity;

            // Apply stock changes depending on direction
            if ($direction === InventoryDirection::IN) {
                $newOnHand = $beforeOnHand + $quantity;
                $newReserved = $beforeReserved;
            } elseif ($direction === InventoryDirection::OUT) {
                // Business rule validation: prevent negative on-hand unless explicitly allowed
                if ($beforeOnHand - $quantity < 0 && ! $inventoryItem->allow_negative_stock) {
                    throw new InsufficientStockException($sku, $quantity, $beforeOnHand);
                }
                $newOnHand = $beforeOnHand - $quantity;
                $newReserved = $beforeReserved;
            } elseif ($direction === InventoryDirection::RESERVE) {
                $newOnHand = $beforeOnHand;
                $newReserved = $beforeReserved + $quantity;
            } elseif ($direction === InventoryDirection::RELEASE) {
                $newOnHand = $beforeOnHand;
                $newReserved = $beforeReserved - $quantity;
            } else { // ADJUST direction
                $newOnHand = $options['adjusted_on_hand'] ?? $beforeOnHand;
                $newReserved = $options['adjusted_reserved'] ?? $beforeReserved;
            }

            // Mutate balance & save
            $inventoryItem->setBalance($newOnHand, $newReserved);
            $inventoryItem->save();

            // Synchronize the parent SKU's cached stock quantity
            $sku->stock_quantity = $inventoryItem->available_quantity;
            $sku->save();

            // Resolve occurred_at and created_by_user_id
            $occurredAt = $options['occurred_at'] ?? now();
            $createdBy = Auth::id(); // null if guest/system-initiated

            // Create movement record (will be append-only)
            /** @var InventoryMovement $movement */
            $movement = InventoryMovement::create([
                'product_sku_id' => $sku->id,
                'inventory_item_id' => $inventoryItem->id,
                'order_id' => $options['order_id'] ?? null,
                'order_item_id' => $options['order_item_id'] ?? null,
                'vendor_order_id' => $options['vendor_order_id'] ?? null,
                'vendor_order_item_id' => $options['vendor_order_item_id'] ?? null,
                'purchase_stock_in_id' => $options['purchase_stock_in_id'] ?? null,
                'movement_type' => $type,
                'direction' => $direction,
                'quantity' => $quantity,
                'before_on_hand_quantity' => $beforeOnHand,
                'after_on_hand_quantity' => $newOnHand,
                'before_reserved_quantity' => $beforeReserved,
                'after_reserved_quantity' => $newReserved,
                'before_available_quantity' => $beforeAvailable,
                'after_available_quantity' => $inventoryItem->available_quantity,
                'reason_code' => $reason,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'created_by_user_id' => $options['created_by_user_id'] ?? $createdBy,
                'occurred_at' => $occurredAt,
                'notes' => $options['notes'] ?? null,
            ]);

            // Dispatch AuditEvent after successful transaction commit
            DB::afterCommit(function () use ($movement, $sku, $type, $reason, $quantity, $beforeOnHand, $newOnHand, $beforeReserved, $newReserved, $options, $createdBy) {
                $resolvedActorId = $options['created_by_user_id'] ?? $createdBy;
                $actor = $options['actor'] ?? ($resolvedActorId ? (User::find($resolvedActorId) ?: Auth::user()) : null);

                event(new AuditEvent(
                    'inventory.stock_moved',
                    $actor,
                    [
                        'movement_public_id' => (string) $movement->id,
                        'sku_public_id' => $sku->sku_code,
                        'movement_type' => $type->value,
                        'reason' => $reason->value,
                        'quantity' => $quantity,
                        'before_on_hand' => $beforeOnHand,
                        'after_on_hand' => $newOnHand,
                        'before_reserved' => $beforeReserved,
                        'after_reserved' => $newReserved,
                        'actor_user_id' => $resolvedActorId,
                    ]
                ));
            });

            return $movement;
        });
    }
}
