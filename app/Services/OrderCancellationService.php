<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    public function __construct(
        protected InventoryBalanceService $inventoryBalanceService,
        protected RefundService $refundService,
    ) {}

    /**
     * Cancel an order with high-integrity operational, inventory, and financial consequences.
     *
     * @throws ValidationException|AuthorizationException
     */
    public function cancel(
        Order $order,
        User $actor,
        string $reasonCode,
        ?string $reasonNote = null,
        ?bool $materialConsumed = null,
        ?bool $customizationApplied = null,
        ?bool $scrapIncurred = null,
        ?int $affectedQuantity = null,
        ?int $scrapQuantity = null,
        ?int $scrapAmountMinor = null,
        ?string $productionImpactNotes = null,
        ?bool $physicalInterceptionConfirmed = null,
        bool $createRefundRequest = false,
        ?int $requestedRefundAmountMinor = null,
    ): OrderCancellation {
        return DB::transaction(function () use (
            $order,
            $actor,
            $reasonCode,
            $reasonNote,
            $materialConsumed,
            $customizationApplied,
            $scrapIncurred,
            $affectedQuantity,
            $scrapQuantity,
            $scrapAmountMinor,
            $productionImpactNotes,
            $physicalInterceptionConfirmed,
            $createRefundRequest,
            $requestedRefundAmountMinor,
        ) {
            // 1. Lock Order for Update
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            // 2. Strict Idempotency Check
            if ($order->status === OrderStatus::Cancelled->value) {
                return $order->cancellation ?? OrderCancellation::query()->where('order_id', $order->id)->firstOrFail();
            }

            // 3. Authorization Check via Dedicated OrderPolicy@cancel
            if (! Gate::forUser($actor)->allows('cancel', $order)) {
                throw new AuthorizationException('You are not authorized to cancel this order in its current status.');
            }

            // 4. Authoritative Shipping and Terminal Status Check
            if (
                $order->shipping_status === 'shipped'
                || $order->shipped_at !== null
                || $order->status === OrderStatus::Shipped->value
                || $order->status === OrderStatus::Delivered->value
            ) {
                throw ValidationException::withMessages([
                    'order' => 'Parcel has already been handed to the courier or dispatched. Use return workflow.',
                ]);
            }

            $shippingInterceptConfirmedByUserId = null;
            $shippingInterceptConfirmedAt = null;
            $shippingStatusAtCancellation = null;

            if ($order->status === OrderStatus::ReadyToShip->value) {
                if (! $physicalInterceptionConfirmed) {
                    throw ValidationException::withMessages([
                        'physical_interception_confirmed' => 'Physical interception confirmation is mandatory for orders ready to ship.',
                    ]);
                }

                $shippingInterceptConfirmedByUserId = $actor->id;
                $shippingInterceptConfirmedAt = now();
                $shippingStatusAtCancellation = $order->shipping_status ?? 'ready';
            }

            // Validation for in_production cancellations
            if ($order->status === OrderStatus::InProduction->value) {
                if (empty($productionImpactNotes)) {
                    throw ValidationException::withMessages([
                        'production_impact_notes' => 'Production impact notes are required when cancelling an order in production.',
                    ]);
                }
            }

            // Validation for "other" reason code
            if ($reasonCode === 'other' && empty($reasonNote)) {
                throw ValidationException::withMessages([
                    'reason_note' => 'An explanation note is required when reason code is other.',
                ]);
            }

            $previousStatus = $order->status;

            // 5. Stage-Aware Inventory & Reservation Handling
            $this->handleInventoryForStage(
                order: $order,
                previousStatus: $previousStatus,
                materialConsumed: (bool) $materialConsumed,
                scrapIncurred: (bool) $scrapIncurred,
            );

            // 6. Create OrderCancellation Record
            $cancellation = OrderCancellation::create([
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'reason_code' => $reasonCode,
                'reason_note' => $reasonNote,
                'cancelled_by_user_id' => $actor->id,
                'material_consumed' => (bool) $materialConsumed,
                'customization_applied' => (bool) $customizationApplied,
                'scrap_incurred' => (bool) $scrapIncurred,
                'affected_quantity' => $affectedQuantity,
                'scrap_quantity' => $scrapQuantity,
                'scrap_amount_minor' => $scrapAmountMinor,
                'production_impact_notes' => $productionImpactNotes,
                'physical_interception_confirmed' => (bool) $physicalInterceptionConfirmed,
                'shipping_intercept_confirmed_by_user_id' => $shippingInterceptConfirmedByUserId,
                'shipping_intercept_confirmed_at' => $shippingInterceptConfirmedAt,
                'shipping_status_at_cancellation' => $shippingStatusAtCancellation,
            ]);

            // 7. Update Order to Terminal Cancelled State
            $reasonSummary = $reasonCode . ($reasonNote ? ": {$reasonNote}" : '');
            $order->update([
                'status' => OrderStatus::Cancelled->value,
                'cancelled_at' => now(),
                'cancellation_reason' => $reasonSummary,
            ]);

            // 8. Multi-Payment Sequential Refund Request Allocation
            if ($createRefundRequest && $requestedRefundAmountMinor !== null && $requestedRefundAmountMinor > 0) {
                $availableToRequest = $order->getAvailableRefundRequestAmountMinor();

                if ($requestedRefundAmountMinor > $availableToRequest) {
                    $maxRupees = number_format($availableToRequest / 100, 2);
                    throw ValidationException::withMessages([
                        'refund_amount' => "Requested refund amount exceeds available refundable balance of ₹{$maxRupees}.",
                    ]);
                }

                $this->allocateRefundAcrossPayments(
                    order: $order,
                    amountMinor: $requestedRefundAmountMinor,
                    reasonCode: $reasonCode,
                    reasonNote: $reasonNote,
                    actor: $actor,
                );
            }

            // 9. Dispatch Audit Event & Notifications Post-Commit
            DB::afterCommit(function () use ($order, $actor, $cancellation): void {
                event(new AuditEvent('orders.order_cancelled', $actor, [
                    'order_id' => $order->id,
                    'order_public_id' => $order->public_id,
                    'customer_id' => $order->customer_id,
                    'previous_status' => $cancellation->previous_status,
                    'reason_code' => $cancellation->reason_code,
                    'reason_note' => $cancellation->reason_note,
                    'scrap_amount_minor' => $cancellation->scrap_amount_minor,
                    'scrap_quantity' => $cancellation->scrap_quantity,
                    'affected_quantity' => $cancellation->affected_quantity,
                ]));
            });

            return $cancellation;
        });
    }

    /**
     * Handle stage-specific inventory or reservation consequences.
     */
    protected function handleInventoryForStage(
        Order $order,
        string $previousStatus,
        bool $materialConsumed,
        bool $scrapIncurred,
    ): void {
        switch ($previousStatus) {
            case OrderStatus::PendingPayment->value:
                // Pending payment: held reservations are released. Physical garments were not yet deducted from on-hand stock.
                // Any reserved balances or draft holds are cleared without restocking on-hand items.
                break;

            case OrderStatus::Confirmed->value:
                // Confirmed: blank garments were allocated and deducted from stock. Safely return blanks to available stock.
                $this->inventoryBalanceService->reverseOrderStock($order);
                break;

            case OrderStatus::InProduction->value:
                // In Production: if material was consumed or scrap incurred, custom goods cannot be returned to saleable stock.
                // Blanks are only reversed if production had NOT consumed material or created scrap.
                if (! $materialConsumed && ! $scrapIncurred) {
                    $this->inventoryBalanceService->reverseOrderStock($order);
                }
                break;

            case OrderStatus::ReadyToShip->value:
                // Ready to Ship: customized finished goods are not returned to normal saleable stock.
                break;
        }
    }

    /**
     * Sequentially allocate a refund request across the order's succeeded payments.
     */
    protected function allocateRefundAcrossPayments(
        Order $order,
        int $amountMinor,
        string $reasonCode,
        ?string $reasonNote,
        User $actor,
    ): void {
        $succeededPayments = $order->payments()
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingToRefund = $amountMinor;

        foreach ($succeededPayments as $payment) {
            if ($remainingToRefund <= 0) {
                break;
            }

            $existingRefundsSum = (int) Refund::query()
                ->where('payment_id', $payment->id)
                ->whereIn('status', [
                    Refund::STATUS_REQUESTED,
                    Refund::STATUS_APPROVED,
                    Refund::STATUS_PROCESSING,
                    Refund::STATUS_SUCCEEDED,
                ])
                ->sum('amount_minor');

            $paymentMaxRefundable = max(0, $payment->amount_minor - $existingRefundsSum);

            if ($paymentMaxRefundable <= 0) {
                continue;
            }

            $allocationForPayment = min($remainingToRefund, $paymentMaxRefundable);

            $this->refundService->requestRefund(
                payment: $payment,
                amountMinor: $allocationForPayment,
                reasonCode: $reasonCode,
                reasonNote: 'Cancellation: ' . ($reasonNote ?: $reasonCode),
                actor: $actor,
            );

            $remainingToRefund -= $allocationForPayment;
        }

        if ($remainingToRefund > 0) {
            throw ValidationException::withMessages([
                'refund_amount' => 'Unable to allocate entire refund request across existing succeeded payments.',
            ]);
        }
    }
}
