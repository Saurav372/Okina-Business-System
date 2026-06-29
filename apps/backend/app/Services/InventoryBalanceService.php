<?php

namespace App\Services;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Events\AuditEvent;
use App\Events\LowStockDetected;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InventoryItemNotFoundException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            // Determine if the threshold is crossed
            $threshold = $inventoryItem->resolvedLowStockThreshold();
            $isLowStockCrossed = $threshold !== null
                && $beforeAvailable > $threshold
                && $inventoryItem->available_quantity <= $threshold;

            // Dispatch AuditEvent after successful transaction commit
            DB::afterCommit(function () use ($movement, $sku, $type, $direction, $reason, $quantity, $beforeOnHand, $newOnHand, $beforeReserved, $newReserved, $options, $createdBy, $isLowStockCrossed, $threshold, $inventoryItem) {
                $resolvedActorId = $options['created_by_user_id'] ?? $createdBy;
                $actor = $options['actor'] ?? ($resolvedActorId ? (User::find($resolvedActorId) ?: Auth::user()) : null);

                event(new AuditEvent(
                    'inventory.stock_moved',
                    $actor,
                    [
                        'movement_public_id' => (string) $movement->id,
                        'sku_public_id' => $sku->sku_code,
                        'movement_type' => $type->value,
                        'direction' => $direction->value,
                        'reason' => $reason->value,
                        'quantity' => $quantity,
                        'before_on_hand' => $beforeOnHand,
                        'after_on_hand' => $newOnHand,
                        'before_reserved' => $beforeReserved,
                        'after_reserved' => $newReserved,
                        'actor_user_id' => $resolvedActorId,
                    ]
                ));

                if ($isLowStockCrossed) {
                    event(new LowStockDetected($sku, $inventoryItem->available_quantity, $threshold, $movement));

                    Log::warning('Low stock detected.', [
                        'sku_id' => $sku->id,
                        'sku_code' => $sku->sku_code,
                        'available_quantity' => $inventoryItem->available_quantity,
                        'threshold' => $threshold,
                        'movement_id' => $movement->id,
                        'movement_public_id' => (string) $movement->id,
                    ]);
                }
            });

            return $movement;
        });
    }

    /**
     * Deduct stock for an entire order's items atomically.
     *
     * @return array<InventoryMovement>
     *
     * @throws InventoryItemNotFoundException
     * @throws InsufficientStockException
     */
    public function deductOrderStock(Order $order, array $options = []): array
    {
        if ($order->items->isEmpty()) {
            return [];
        }

        // Eager load relationships before transaction to reduce query overhead
        $order->loadMissing(['items.sku.inventoryItem']);

        // Sort items by inventory item ID to ensure stable lock ordering (deadlock prevention)
        $sortedItems = $order->items->sortBy(function ($item) {
            return $item->sku?->inventoryItem?->id ?? 0;
        });

        $lockedSkuIds = [];

        return DB::transaction(function () use ($sortedItems, $options, &$lockedSkuIds, $order) {
            $movements = [];

            foreach ($sortedItems as $item) {
                $sku = $item->sku;
                if (! $sku) {
                    throw new \RuntimeException('OrderItem is missing a valid product SKU relationship.');
                }

                // Lock row using fresh query to guarantee lock is acquired on the DB row
                if (! isset($lockedSkuIds[$sku->id])) {
                    try {
                        $inventoryItem = InventoryItem::query()
                            ->where('product_sku_id', $sku->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $lockedSkuIds[$sku->id] = true;
                    } catch (ModelNotFoundException $e) {
                        throw new InventoryItemNotFoundException($sku);
                    }
                }

                // Check duplicate movement AFTER lock is acquired
                /** @var InventoryMovement|null $existing */
                $existing = InventoryMovement::query()
                    ->where('movement_type', InventoryMovementType::ORDER_DEDUCTION)
                    ->where('order_item_id', $item->id)
                    ->first();

                if ($existing !== null) {
                    $movements[] = $existing;

                    continue;
                }

                // Record deduction movement (direction OUT, type ORDER_DEDUCTION)
                $movements[] = $this->recordMovement(
                    $sku,
                    $item->quantity,
                    InventoryMovementType::ORDER_DEDUCTION,
                    InventoryDirection::OUT,
                    InventoryMovementReason::ORDER_FULFILLMENT,
                    array_merge($options, [
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                    ])
                );
            }

            return $movements;
        }, 3);
    }

    /**
     * Reverse stock deduction for an entire order's items atomically.
     *
     * @return array<InventoryMovement>
     *
     * @throws InventoryItemNotFoundException
     * @throws InsufficientStockException
     */
    public function reverseOrderStock(Order $order, array $options = []): array
    {
        if ($order->items->isEmpty()) {
            return [];
        }

        // Eager load relationships before transaction to reduce query overhead
        $order->loadMissing(['items.sku.inventoryItem']);

        // Sort items by inventory item ID to ensure stable lock ordering (deadlock prevention)
        $sortedItems = $order->items->sortBy(function ($item) {
            return $item->sku?->inventoryItem?->id ?? 0;
        });

        $lockedInventoryItems = [];

        return DB::transaction(function () use ($sortedItems, $options, &$lockedInventoryItems, $order) {
            $movements = [];

            foreach ($sortedItems as $item) {
                $sku = $item->sku;
                if (! $sku) {
                    throw new \RuntimeException('OrderItem is missing a valid product SKU relationship.');
                }

                // Lock row using fresh query to guarantee lock is acquired on the DB row
                if (! isset($lockedInventoryItems[$sku->id])) {
                    try {
                        $inventoryItem = InventoryItem::query()
                            ->where('product_sku_id', $sku->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $lockedInventoryItems[$sku->id] = $inventoryItem;
                    } catch (ModelNotFoundException $e) {
                        throw new InventoryItemNotFoundException($sku);
                    }
                }

                // Locate the original order_deduction movement
                /** @var InventoryMovement|null $deduction */
                $deduction = InventoryMovement::query()
                    ->where('movement_type', InventoryMovementType::ORDER_DEDUCTION)
                    ->where('order_id', $order->id)
                    ->where('order_item_id', $item->id)
                    ->first();

                // If no deduction happened, there is nothing to reverse (no-op)
                if (! $deduction) {
                    continue;
                }

                // Check duplicate cancellation_reversal AFTER lock is acquired
                /** @var InventoryMovement|null $existingReversal */
                $existingReversal = InventoryMovement::query()
                    ->where('movement_type', InventoryMovementType::CANCELLATION_REVERSAL)
                    ->where('order_id', $order->id)
                    ->where('order_item_id', $item->id)
                    ->first();

                if ($existingReversal !== null) {
                    $movements[] = $existingReversal;

                    continue;
                }

                // Record cancellation reversal (direction IN, type CANCELLATION_REVERSAL, reason ORDER_CANCELLATION)
                $movements[] = $this->recordMovement(
                    $sku,
                    $deduction->quantity,
                    InventoryMovementType::CANCELLATION_REVERSAL,
                    InventoryDirection::IN,
                    InventoryMovementReason::ORDER_CANCELLATION,
                    array_merge($options, [
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                    ])
                );
            }

            return $movements;
        }, 3);
    }

    /**
     * Get the query builder for movement history with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function movementHistoryQuery(array $filters): Builder
    {
        $query = InventoryMovement::query()
            ->with(['productSku', 'order', 'user']);

        // Filtering by product_sku_id / sku_code
        if (! empty($filters['product_sku_id'])) {
            $query->where('product_sku_id', (int) $filters['product_sku_id']);
        } elseif (! empty($filters['sku_code'])) {
            $query->whereHas('productSku', function ($q) use ($filters) {
                $q->where('sku_code', $filters['sku_code']);
            });
        }

        // Filtering by order_id / order_item_id
        if (! empty($filters['order_id'])) {
            $query->where('order_id', (int) $filters['order_id']);
        }
        if (! empty($filters['order_item_id'])) {
            $query->where('order_item_id', (int) $filters['order_item_id']);
        }

        // Filtering by vendor_order_id / vendor_order_item_id / purchase_stock_in_id
        if (! empty($filters['vendor_order_id'])) {
            $query->where('vendor_order_id', (int) $filters['vendor_order_id']);
        }
        if (! empty($filters['vendor_order_item_id'])) {
            $query->where('vendor_order_item_id', (int) $filters['vendor_order_item_id']);
        }
        if (! empty($filters['purchase_stock_in_id'])) {
            $query->where('purchase_stock_in_id', (int) $filters['purchase_stock_in_id']);
        }

        // Filtering by movement_type and direction
        if (! empty($filters['movement_type'])) {
            $type = $filters['movement_type'];
            if ($type instanceof InventoryMovementType) {
                $query->where('movement_type', $type);
            } else {
                $query->where('movement_type', $type);
            }
        }
        if (! empty($filters['direction'])) {
            $direction = $filters['direction'];
            if ($direction instanceof InventoryDirection) {
                $query->where('direction', $direction);
            } else {
                $query->where('direction', $direction);
            }
        }

        // Filtering by occurred_at range (inclusive)
        if (! empty($filters['occurred_from'])) {
            $query->where('occurred_at', '>=', $filters['occurred_from']);
        }
        if (! empty($filters['occurred_to'])) {
            $occurredTo = $filters['occurred_to'];
            if (is_string($occurredTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $occurredTo)) {
                $occurredTo .= ' 23:59:59';
            }
            $query->where('occurred_at', '<=', $occurredTo);
        }

        // Whitelist sortable columns
        $allowedSortBy = [
            'occurred_at',
            'created_at',
            'movement_type',
            'direction',
            'quantity',
        ];

        $sortBy = $filters['sort_by'] ?? 'occurred_at';
        if (! in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'occurred_at';
        }

        $sortDirection = isset($filters['sort_direction']) && strtolower($filters['sort_direction']) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection)
            ->orderBy('id', $sortDirection);

        return $query;
    }

    /**
     * Get paginated movement history.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getMovementHistory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return $this->movementHistoryQuery($filters)->paginate($perPage);
    }
}
