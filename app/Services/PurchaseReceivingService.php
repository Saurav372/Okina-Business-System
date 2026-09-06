<?php

namespace App\Services;

use App\Enums\InventoryMovementReason;
use App\Enums\VendorOrderStatus;
use App\Events\AuditEvent;
use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use App\Models\PurchaseReceipt;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use App\Support\Purchases\PurchaseReceiptCodeGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PurchaseReceivingService
{
    public function __construct(
        protected InventoryBalanceService $inventoryService
    ) {}

    /**
     * Process goods receipt for a Purchase Order, create PurchaseReceipt batch, stock-in items, and update PO status.
     *
     * @param  array<int, array{vendor_order_item_id: int, quantity_received: int}>  $items
     * @return array{vendor_order: VendorOrder, receipt: PurchaseReceipt, received_count: int, batch_code: string, replayed: bool}
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

        // 1. Normalize payload & compute canonical SHA-256 hash
        $normalizedItems = [];
        foreach ($items as $itemData) {
            $lineItemId = (int) ($itemData['vendor_order_item_id'] ?? 0);
            $qty = (int) ($itemData['quantity_received'] ?? 0);
            if ($qty > 0 && $lineItemId > 0) {
                $normalizedItems[] = [
                    'vendor_order_item_id' => $lineItemId,
                    'quantity_received' => $qty,
                ];
            }
        }

        if (empty($normalizedItems)) {
            throw ValidationException::withMessages([
                'items' => 'At least one line item must have a received quantity greater than 0.',
            ]);
        }

        usort($normalizedItems, fn ($a, $b) => $a['vendor_order_item_id'] <=> $b['vendor_order_item_id']);

        $trimmedNotes = $notes !== null ? trim($notes) : null;
        $canonicalPayload = [
            'vendor_order_id' => $order->id,
            'items' => $normalizedItems,
            'notes' => $trimmedNotes !== '' ? $trimmedNotes : null,
        ];
        $requestHash = hash('sha256', json_encode($canonicalPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return DB::transaction(function () use ($order, $idempotencyKey, $actor, $trimmedNotes, $normalizedItems, $requestHash) {
            // 2. Lock order and check existing idempotency record
            $lockedOrder = VendorOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $existingReceipt = PurchaseReceipt::where('vendor_order_id', $lockedOrder->id)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existingReceipt) {
                if ($existingReceipt->request_hash === $requestHash) {
                    $snapshot = $existingReceipt->response_snapshot ?: [];

                    return [
                        'vendor_order' => $lockedOrder->fresh(['items', 'receipts']),
                        'receipt' => $existingReceipt,
                        'received_count' => (int) ($snapshot['received_count'] ?? $existingReceipt->lines()->sum('quantity_received')),
                        'batch_code' => $existingReceipt->receipt_number,
                        'replayed' => true,
                    ];
                }

                throw new ConflictHttpException('Idempotency key reuse with mismatched request payload.');
            }

            // 3. Process receiving line by line
            $totalReceivedUnitsThisReceipt = 0;
            $receiptLinesToCreate = [];

            foreach ($normalizedItems as $itemData) {
                $lineItemId = $itemData['vendor_order_item_id'];
                $batchQuantity = $itemData['quantity_received'];

                /** @var VendorOrderItem $lineItem */
                $lineItem = $lockedOrder->items()->where('id', $lineItemId)->lockForUpdate()->first();

                if (! $lineItem) {
                    throw ValidationException::withMessages([
                        'items' => "Purchase order line item [ID {$lineItemId}] does not belong to purchase order [{$lockedOrder->public_id}].",
                    ]);
                }

                if ($lineItem->productSku?->trashed()) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive stock for deleted SKU [{$lineItem->productSku?->sku_code}].",
                    ]);
                }

                $cumulativeReceived = $lineItem->quantity_received;
                $orderedQuantity = $lineItem->quantity_ordered;
                $remainingOrderable = $orderedQuantity - $cumulativeReceived;

                if ($batchQuantity > $remainingOrderable) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive {$batchQuantity} units for SKU [{$lineItem->productSku?->sku_code}]. Maximum remaining orderable quantity is {$remainingOrderable} units.",
                    ]);
                }

                // 4. Stock-in to inventory & sync ProductSku stock_quantity
                $inventoryItem = $this->inventoryService->stockIn(
                    sku: $lineItem->productSku,
                    quantity: $batchQuantity,
                    reason: InventoryMovementReason::PURCHASE_RECEIPT,
                    options: [
                        'vendor_order_id' => $lockedOrder->id,
                        'vendor_order_item_id' => $lineItem->id,
                        'reference_type' => 'PurchaseReceipt',
                        'idempotency_key' => "{$idempotencyKey}_item_{$lineItem->id}",
                        'created_by_user_id' => $actor?->id,
                        'notes' => $trimmedNotes ?: "PO Goods Receipt {$lockedOrder->public_id}",
                    ]
                );

                // Synchronize ProductSku.stock_quantity = InventoryItem.available_quantity
                if ($lineItem->productSku && isset($inventoryItem->available_quantity)) {
                    $lineItem->productSku->update([
                        'stock_quantity' => (int) $inventoryItem->available_quantity,
                    ]);
                }

                // Increment line item cumulative received_quantity
                $lineItem->quantity_received += $batchQuantity;
                $lineItem->save();

                $totalReceivedUnitsThisReceipt += $batchQuantity;
                $receiptLinesToCreate[] = [
                    'vendor_order_item_id' => $lineItem->id,
                    'product_sku_id' => $lineItem->product_sku_id,
                    'quantity_received' => $batchQuantity,
                ];
            }

            // 5. Generate unique receipt number and persist PurchaseReceipt record
            $receipt = PurchaseReceiptCodeGenerator::executeWithRetry(function (string $receiptNumber) use ($lockedOrder, $idempotencyKey, $requestHash, $trimmedNotes, $actor, $totalReceivedUnitsThisReceipt) {
                return PurchaseReceipt::create([
                    'receipt_number' => $receiptNumber,
                    'vendor_order_id' => $lockedOrder->id,
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'response_snapshot' => [
                        'received_count' => $totalReceivedUnitsThisReceipt,
                        'receipt_number' => $receiptNumber,
                    ],
                    'notes' => $trimmedNotes,
                    'created_by_user_id' => $actor?->id,
                    'received_at' => now(),
                ]);
            });

            foreach ($receiptLinesToCreate as $lineData) {
                $receipt->lines()->create($lineData);
            }

            // Link InventoryMovement reference_id to $receipt->id
            DB::table('inventory_movements')
                ->where('vendor_order_id', $lockedOrder->id)
                ->where('reference_type', 'PurchaseReceipt')
                ->whereNull('reference_id')
                ->update(['reference_id' => $receipt->id]);

            // 6. Evaluate total PO fulfillment status & update timestamps
            $lockedOrder->refresh();
            $allCompleted = true;
            $anyReceived = false;

            foreach ($lockedOrder->items as $item) {
                if ($item->quantity_received > 0) {
                    $anyReceived = true;
                }
                if ($item->quantity_received < $item->quantity_ordered) {
                    $allCompleted = false;
                }
            }

            $previousStatus = $lockedOrder->status->value;

            if ($allCompleted) {
                $lockedOrder->status = VendorOrderStatus::RECEIVED;
                $lockedOrder->received_at = now();
            } elseif ($anyReceived) {
                $lockedOrder->status = VendorOrderStatus::PARTIALLY_RECEIVED;
            }

            $lockedOrder->updated_by_user_id = $actor?->id;
            $lockedOrder->save();

            DB::afterCommit(function () use ($lockedOrder, $receipt, $previousStatus, $totalReceivedUnitsThisReceipt, $actor) {
                event(new AuditEvent('purchase_receipts.created', $actor, [
                    'vendor_order_id' => $lockedOrder->id,
                    'public_id' => $lockedOrder->public_id,
                    'purchase_receipt_id' => $receipt->id,
                    'receipt_number' => $receipt->receipt_number,
                    'idempotency_key' => $receipt->idempotency_key,
                    'previous_status' => $previousStatus,
                    'new_status' => $lockedOrder->status->value,
                    'received_units' => $totalReceivedUnitsThisReceipt,
                    'actor_id' => $actor?->id,
                ]));

                if ($lockedOrder->status === VendorOrderStatus::RECEIVED) {
                    event(new AuditEvent('purchase_orders.fully_received', $actor, [
                        'vendor_order_id' => $lockedOrder->id,
                        'public_id' => $lockedOrder->public_id,
                        'receipt_number' => $receipt->receipt_number,
                        'actor_id' => $actor?->id,
                    ]));
                }
            });

            return [
                'vendor_order' => $lockedOrder,
                'receipt' => $receipt,
                'received_count' => $totalReceivedUnitsThisReceipt,
                'batch_code' => $receipt->receipt_number,
                'replayed' => false,
            ];
        });
    }
}
