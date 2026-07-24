<?php

namespace App\Services;

use App\Enums\InventoryLocation;
use App\Enums\InventoryMovementReason;
use App\Enums\WarehouseTransferStatus;
use App\Events\AuditEvent;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WarehouseTransferService
{
    public function __construct(
        protected InventoryBalanceService $inventoryService
    ) {}

    /**
     * Create a draft warehouse transfer record.
     */
    public function initiateTransfer(
        ProductSku $sku,
        InventoryLocation $sourceLocation,
        InventoryLocation $destinationLocation,
        int $quantity,
        ?User $actor = null,
        ?string $notes = null
    ): WarehouseTransfer {
        if ($sourceLocation === $destinationLocation) {
            throw ValidationException::withMessages([
                'destination_location' => 'Source and destination locations cannot be identical.',
            ]);
        }

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Transfer quantity must be greater than zero.',
            ]);
        }

        $actor = $actor ?: Auth::user();
        $code = 'TRF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

        return DB::transaction(function () use ($sku, $sourceLocation, $destinationLocation, $quantity, $actor, $notes, $code) {
            $transfer = WarehouseTransfer::create([
                'transfer_code' => $code,
                'product_sku_id' => $sku->id,
                'source_location' => $sourceLocation,
                'destination_location' => $destinationLocation,
                'quantity' => $quantity,
                'status' => WarehouseTransferStatus::DRAFT,
                'initiated_by_user_id' => $actor?->id,
                'notes' => $notes,
            ]);

            DB::afterCommit(function () use ($transfer, $actor) {
                event(new AuditEvent('warehouse_transfer.created', $actor, [
                    'transfer_id' => $transfer->id,
                    'transfer_code' => $transfer->transfer_code,
                    'product_sku_id' => $transfer->product_sku_id,
                    'quantity' => $transfer->quantity,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $transfer;
        });
    }

    /**
     * Dispatch transfer (DRAFT -> IN_TRANSIT). Deducts source stock and logs movement.
     */
    public function shipTransfer(
        WarehouseTransfer $transfer,
        string $idempotencyKey,
        ?User $actor = null
    ): WarehouseTransfer {
        return DB::transaction(function () use ($transfer, $idempotencyKey, $actor) {
            /** @var WarehouseTransfer $locked */
            $locked = WarehouseTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WarehouseTransferStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => "Cannot ship warehouse transfer [{$locked->transfer_code}] with status [{$locked->status->value}]. Transfer must be in DRAFT status.",
                ]);
            }

            /** @var InventoryItem $inventoryItem */
            $inventoryItem = InventoryItem::query()
                ->where('product_sku_id', $locked->product_sku_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inventoryItem->on_hand_quantity < $locked->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock at source location [{$locked->source_location->label()}]. Available: {$inventoryItem->on_hand_quantity}, Requested: {$locked->quantity}.",
                ]);
            }

            $shipKey = "{$idempotencyKey}_ship_{$locked->id}";

            // Check idempotency early
            $existing = InventoryMovement::query()->where('idempotency_key', $shipKey)->first();
            if ($existing) {
                return $locked;
            }

            $actor = $actor ?: Auth::user();

            // Deduct stock via InventoryBalanceService
            $this->inventoryService->stockOut(
                sku: $locked->productSku,
                quantity: $locked->quantity,
                reason: InventoryMovementReason::STOCK_TRANSFER_OUT,
                options: [
                    'idempotency_key' => $shipKey,
                    'reference_type' => 'WarehouseTransfer',
                    'reference_id' => $locked->id,
                    'created_by_user_id' => $actor?->id,
                    'notes' => "Dispatched transfer {$locked->transfer_code} from {$locked->source_location->label()} to {$locked->destination_location->label()}",
                ]
            );

            $locked->status = WarehouseTransferStatus::IN_TRANSIT;
            $locked->shipped_at = now();
            $locked->save();

            DB::afterCommit(function () use ($locked, $actor) {
                event(new AuditEvent('warehouse_transfer.shipped', $actor, [
                    'transfer_id' => $locked->id,
                    'transfer_code' => $locked->transfer_code,
                    'quantity' => $locked->quantity,
                    'shipped_at' => $locked->shipped_at?->toIso8601String(),
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }

    /**
     * Receive goods stock-in (IN_TRANSIT -> COMPLETED). Adds destination stock and logs movement.
     */
    public function receiveTransfer(
        WarehouseTransfer $transfer,
        string $idempotencyKey,
        ?User $actor = null
    ): WarehouseTransfer {
        return DB::transaction(function () use ($transfer, $idempotencyKey, $actor) {
            /** @var WarehouseTransfer $locked */
            $locked = WarehouseTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WarehouseTransferStatus::IN_TRANSIT) {
                throw ValidationException::withMessages([
                    'status' => "Cannot receive warehouse transfer [{$locked->transfer_code}] with status [{$locked->status->value}]. Transfer must be IN_TRANSIT.",
                ]);
            }

            $rcvKey = "{$idempotencyKey}_receive_{$locked->id}";

            // Check idempotency early
            $existing = InventoryMovement::query()->where('idempotency_key', $rcvKey)->first();
            if ($existing) {
                return $locked;
            }

            $actor = $actor ?: Auth::user();

            // Add stock via InventoryBalanceService
            $this->inventoryService->stockIn(
                sku: $locked->productSku,
                quantity: $locked->quantity,
                reason: InventoryMovementReason::STOCK_TRANSFER_IN,
                options: [
                    'idempotency_key' => $rcvKey,
                    'reference_type' => 'WarehouseTransfer',
                    'reference_id' => $locked->id,
                    'created_by_user_id' => $actor?->id,
                    'notes' => "Received transfer {$locked->transfer_code} at {$locked->destination_location->label()}",
                ]
            );

            $locked->status = WarehouseTransferStatus::COMPLETED;
            $locked->completed_at = now();
            $locked->completed_by_user_id = $actor?->id;
            $locked->save();

            DB::afterCommit(function () use ($locked, $actor) {
                event(new AuditEvent('warehouse_transfer.received', $actor, [
                    'transfer_id' => $locked->id,
                    'transfer_code' => $locked->transfer_code,
                    'quantity' => $locked->quantity,
                    'completed_at' => $locked->completed_at?->toIso8601String(),
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }

    /**
     * Cancel transfer (DRAFT / IN_TRANSIT -> CANCELLED). Rejects if COMPLETED. Restores source stock if previously shipped.
     */
    public function cancelTransfer(
        WarehouseTransfer $transfer,
        ?User $actor = null,
        ?string $reasonNotes = null
    ): WarehouseTransfer {
        return DB::transaction(function () use ($transfer, $actor, $reasonNotes) {
            /** @var WarehouseTransfer $locked */
            $locked = WarehouseTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === WarehouseTransferStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => "Completed warehouse transfer [{$locked->transfer_code}] cannot be cancelled. Execute a new reverse transfer instead.",
                ]);
            }

            if ($locked->status === WarehouseTransferStatus::CANCELLED) {
                return $locked;
            }

            $actor = $actor ?: Auth::user();

            // If transfer was shipped (IN_TRANSIT), restore stock back to source location
            if ($locked->status === WarehouseTransferStatus::IN_TRANSIT) {
                $restoreKey = "cancel_restore_{$locked->id}";
                $this->inventoryService->stockIn(
                    sku: $locked->productSku,
                    quantity: $locked->quantity,
                    reason: InventoryMovementReason::INVENTORY_CORRECTION,
                    options: [
                        'idempotency_key' => $restoreKey,
                        'reference_type' => 'WarehouseTransferCancellation',
                        'reference_id' => $locked->id,
                        'created_by_user_id' => $actor?->id,
                        'notes' => "Restored stock from cancelled transfer {$locked->transfer_code}",
                    ]
                );
            }

            $locked->status = WarehouseTransferStatus::CANCELLED;
            $locked->notes = $reasonNotes ? $locked->notes." [Cancelled: {$reasonNotes}]" : $locked->notes;
            $locked->save();

            DB::afterCommit(function () use ($locked, $actor) {
                event(new AuditEvent('warehouse_transfer.cancelled', $actor, [
                    'transfer_id' => $locked->id,
                    'transfer_code' => $locked->transfer_code,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }
}
