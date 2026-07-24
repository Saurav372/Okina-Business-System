<?php

namespace App\Services;

use App\Enums\InventoryMovementReason;
use App\Enums\VendorOrderStatus;
use App\Events\AuditEvent;
use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseReceivingService
{
    public function __construct(
        protected InventoryBalanceService $inventoryService
    ) {}

    /**
     * Process goods receipt for a Purchase Order, stock-in items, and update PO fulfillment status.
     *
     * @param  array<int, array{vendor_order_item_id: int, quantity_received: int}>  $items
     * @return array{vendor_order: VendorOrder, received_count: int, batch_code: string}
     */
    public function receive(
        VendorOrder $order,
        array $items,
        string $idempotencyKey,
        ?User $actor = null,
        ?string $notes = null
    ): array {
        if (! in_array($order->status, [VendorOrderStatus::ORDERED, VendorOrderStatus::PARTIALLY_RECEIVED], true)) {
            throw new InvalidPurchaseOrderStatusTransitionException(
                "Cannot receive items on purchase order with status [{$order->status->value}]. Only ordered or partially received orders can accept goods receipts."
            );
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'At least one line item quantity must be specified for receiving.',
            ]);
        }

        $actor = $actor ?: Auth::user();
        $batchCode = 'RCV-PO'.$order->id.'-'.now()->format('Ymd-His').'-'.strtoupper(Str::random(4));

        return DB::transaction(function () use ($order, $items, $idempotencyKey, $actor, $notes, $batchCode) {
            $totalReceivedUnitsThisReceipt = 0;
            $itemsProcessedCount = 0;

            foreach ($items as $itemData) {
                $lineItemId = (int) ($itemData['vendor_order_item_id'] ?? 0);
                $qtyToReceive = (int) ($itemData['quantity_received'] ?? 0);

                if ($qtyToReceive <= 0) {
                    continue;
                }

                /** @var VendorOrderItem $lineItem */
                $lineItem = $order->items()->where('id', $lineItemId)->lockForUpdate()->first();

                if (! $lineItem) {
                    throw ValidationException::withMessages([
                        'items' => "Purchase order line item [ID {$lineItemId}] does not belong to purchase order [{$order->public_id}].",
                    ]);
                }

                $remainingQty = $lineItem->remainingQuantity();

                if ($qtyToReceive > $remainingQty) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive {$qtyToReceive} units for SKU [{$lineItem->productSku?->sku_code}]. Maximum remaining orderable quantity is {$remainingQty} units.",
                    ]);
                }

                $itemKey = "{$idempotencyKey}_item_{$lineItem->id}";

                // 1. Stock-in to inventory via InventoryBalanceService
                $this->inventoryService->stockIn(
                    sku: $lineItem->productSku,
                    quantity: $qtyToReceive,
                    reason: InventoryMovementReason::PURCHASE_RECEIPT,
                    options: [
                        'vendor_order_id' => $order->id,
                        'vendor_order_item_id' => $lineItem->id,
                        'purchase_stock_in_id' => $lineItem->id,
                        'reference_type' => 'PurchaseOrderReceipt',
                        'reference_id' => $order->id,
                        'idempotency_key' => $itemKey,
                        'created_by_user_id' => $actor?->id,
                        'notes' => $notes ?: "Goods receipt batch {$batchCode}",
                    ]
                );

                // 2. Increment line item received_quantity
                $lineItem->quantity_received += $qtyToReceive;
                $lineItem->save();

                $totalReceivedUnitsThisReceipt += $qtyToReceive;
                $itemsProcessedCount++;
            }

            if ($itemsProcessedCount === 0) {
                throw ValidationException::withMessages([
                    'items' => 'At least one line item must have a received quantity greater than 0.',
                ]);
            }

            // 3. Evaluate total PO fulfillment status
            $order->refresh();
            $allCompleted = true;
            $anyReceived = false;

            foreach ($order->items as $item) {
                if ($item->quantity_received > 0) {
                    $anyReceived = true;
                }
                if ($item->quantity_received < $item->quantity_ordered) {
                    $allCompleted = false;
                }
            }

            $previousStatus = $order->status->value;

            if ($allCompleted) {
                $order->status = VendorOrderStatus::RECEIVED;
                $order->received_at = now();
            } elseif ($anyReceived) {
                $order->status = VendorOrderStatus::PARTIALLY_RECEIVED;
                $order->received_at = $order->received_at ?: now();
            }

            $order->save();

            DB::afterCommit(function () use ($order, $previousStatus, $batchCode, $totalReceivedUnitsThisReceipt, $actor) {
                event(new AuditEvent('purchase_orders.received', $actor, [
                    'vendor_order_id' => $order->id,
                    'public_id' => $order->public_id,
                    'vendor_id' => $order->vendor_id,
                    'previous_status' => $previousStatus,
                    'new_status' => $order->status->value,
                    'batch_code' => $batchCode,
                    'received_units' => $totalReceivedUnitsThisReceipt,
                    'actor_id' => $actor?->id,
                ]));
            });

            return [
                'vendor_order' => $order,
                'received_count' => $totalReceivedUnitsThisReceipt,
                'batch_code' => $batchCode,
            ];
        });
    }
}
