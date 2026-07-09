<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Payments\PaymentStateRecalculationRules;

class OrderTimelineService
{
    public function __construct(
        private readonly PaymentStateRecalculationRules $stateRules
    ) {}

    /**
     * Generate customer-friendly timeline steps for an order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generateTimeline(Order $order): array
    {
        $paidTotal = (int) $order->payments->where('status', 'succeeded')->sum('amount_minor');
        $refundTotal = (int) $order->refunds->where('status', 'succeeded')->sum('amount_minor');
        $paymentStatus = $this->stateRules->calculate($order->total_amount_minor, $paidTotal, $refundTotal, $order->getExpectedAdvanceAmount());

        if ($order->status === 'cancelled') {
            return [
                [
                    'key' => 'placed',
                    'label' => 'Order Created',
                    'status' => 'completed',
                    'date' => $order->placed_at?->toIso8601String(),
                    'detail_message' => null,
                ],
                [
                    'key' => 'cancelled',
                    'label' => 'Cancelled',
                    'status' => 'completed',
                    'date' => $order->cancelled_at?->toIso8601String() ?? $order->updated_at?->toIso8601String(),
                    'detail_message' => $order->cancellation_reason,
                ],
            ];
        }

        $isSalesOrder = $order->order_type === 'sales_order';

        if ($isSalesOrder) {
            return $this->getSalesOrderTimeline($order, $paymentStatus);
        }

        return $this->getWebsiteOrderTimeline($order, $paymentStatus);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getWebsiteOrderTimeline(Order $order, string $paymentStatus): array
    {
        $firstSucceededPayment = $order->payments->where('status', 'succeeded')->sortBy('paid_at')->first();
        $lastSucceededPayment = $order->payments->where('status', 'succeeded')->sortByDesc('paid_at')->first();

        $steps = [
            [
                'key' => 'placed',
                'label' => 'Order Placed',
                'status' => 'completed',
                'date' => $order->placed_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'paid',
                'label' => 'Payment Received',
                'status' => $paymentStatus === 'paid' ? 'completed' : '',
                'date' => $lastSucceededPayment?->paid_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'design',
                'label' => 'Design Review',
                'status' => $this->getDesignStepStatus($order),
                'date' => $order->design_approved_at?->toIso8601String(),
                'detail_message' => $order->design_status === 'issue_found' ? $order->design_issue_message : null,
            ],
            [
                'key' => 'production',
                'label' => 'Printing Process',
                'status' => $order->production_status === 'completed' ? 'completed' : '',
                'date' => $order->ready_to_ship_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'ready',
                'label' => 'Ready to Ship',
                'status' => $this->isReadyToShipOrPast($order) ? 'completed' : '',
                'date' => $order->ready_to_ship_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'shipped',
                'label' => 'Shipped',
                'status' => $this->isShippedOrPast($order) ? 'completed' : '',
                'date' => $order->shipped_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'status' => ($order->shipping_status === 'delivered' || $order->status === 'delivered') ? 'completed' : '',
                'date' => $order->delivered_at?->toIso8601String(),
                'detail_message' => null,
            ],
        ];

        return $this->cascadeStatuses($steps);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSalesOrderTimeline(Order $order, string $paymentStatus): array
    {
        $firstSucceededPayment = $order->payments->where('status', 'succeeded')->sortBy('paid_at')->first();
        $lastSucceededPayment = $order->payments->where('status', 'succeeded')->sortByDesc('paid_at')->first();

        $hasPaidSomething = in_array($paymentStatus, ['paid', 'partially_paid', 'advance_paid'], true) || $firstSucceededPayment !== null;

        $steps = [
            [
                'key' => 'placed',
                'label' => 'Order Created',
                'status' => 'completed',
                'date' => $order->placed_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'advance_paid',
                'label' => 'Advance Payment Received',
                'status' => $hasPaidSomething ? 'completed' : '',
                'date' => $firstSucceededPayment?->paid_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'design',
                'label' => 'Design Review',
                'status' => $this->getDesignStepStatus($order),
                'date' => $order->design_approved_at?->toIso8601String(),
                'detail_message' => $order->design_status === 'issue_found' ? $order->design_issue_message : null,
            ],
            [
                'key' => 'production',
                'label' => 'Printing Process',
                'status' => $order->production_status === 'completed' ? 'completed' : '',
                'date' => $order->ready_to_ship_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'balance_paid',
                'label' => $paymentStatus === 'paid' ? 'Balance Payment Received' : 'Balance Payment Pending',
                'status' => $paymentStatus === 'paid' ? 'completed' : '',
                'date' => $lastSucceededPayment?->paid_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'ready',
                'label' => 'Ready to Ship',
                'status' => $this->isReadyToShipOrPast($order) ? 'completed' : '',
                'date' => $order->ready_to_ship_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'shipped',
                'label' => 'Shipped',
                'status' => $this->isShippedOrPast($order) ? 'completed' : '',
                'date' => $order->shipped_at?->toIso8601String(),
                'detail_message' => null,
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'status' => ($order->shipping_status === 'delivered' || $order->status === 'delivered') ? 'completed' : '',
                'date' => $order->delivered_at?->toIso8601String(),
                'detail_message' => null,
            ],
        ];

        return $this->cascadeStatuses($steps);
    }

    private function getDesignStepStatus(Order $order): string
    {
        if ($order->design_status === 'approved' || $order->design_approved) {
            return 'completed';
        }

        if ($order->design_status === 'issue_found') {
            return 'warning';
        }

        return '';
    }

    private function isReadyToShipOrPast(Order $order): bool
    {
        return $order->ready_to_ship_at !== null
            || in_array($order->status, ['ready_to_ship', 'shipped', 'delivered'], true)
            || in_array($order->shipping_status, ['shipped', 'delivered'], true);
    }

    private function isShippedOrPast(Order $order): bool
    {
        return $order->shipped_at !== null
            || in_array($order->status, ['shipped', 'delivered'], true)
            || in_array($order->shipping_status, ['shipped', 'delivered'], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return array<int, array<string, mixed>>
     */
    private function cascadeStatuses(array $steps): array
    {
        $hasActiveOrWarning = false;

        foreach ($steps as &$step) {
            if ($step['status'] === 'completed') {
                continue;
            }

            if ($step['status'] === 'warning') {
                $hasActiveOrWarning = true;

                continue;
            }

            if (! $hasActiveOrWarning) {
                $step['status'] = 'active';
                $hasActiveOrWarning = true;
            } else {
                $step['status'] = 'pending';
            }
        }

        return $steps;
    }
}
