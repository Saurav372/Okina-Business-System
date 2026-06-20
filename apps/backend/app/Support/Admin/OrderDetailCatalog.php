<?php

namespace App\Support\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Refund;

final class OrderDetailCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'website_order_detail',
            'label' => 'Website Order Detail',
            'model' => Order::class,
            'read_only' => true,
            'allowed_actions' => ['view'],
            'blocked_actions' => [
                'create',
                'edit',
                'delete',
                'forceDelete',
                'restore',
                'replicate',
                'status',
                'payment',
                'refund',
                'shipping',
            ],
            'snapshot_policy' => 'stored_snapshots_only',
            'sections' => [
                'summary' => [
                    'label' => 'Order Summary',
                    'fields' => [
                        'public_id',
                        'order_type',
                        'order_source',
                        'status',
                        'currency',
                        'amounts',
                        'design_approved',
                        'design_approved_at',
                        'placed_at',
                    ],
                ],
                'customer' => [
                    'label' => 'Customer Snapshot',
                    'fields' => [
                        'customer_snapshot',
                    ],
                ],
                'shipping_address' => [
                    'label' => 'Shipping Address Snapshot',
                    'fields' => [
                        'shipping_address_snapshot',
                    ],
                ],
                'billing_address' => [
                    'label' => 'Billing Address Snapshot',
                    'fields' => [
                        'billing_address_snapshot',
                    ],
                ],
                'payment_attempts' => [
                    'label' => 'Payment Attempt History',
                    'fields' => [
                        'payment_attempts',
                    ],
                ],
                'payments' => [
                    'label' => 'Payment History',
                    'fields' => [
                        'payments',
                    ],
                ],
                'refunds' => [
                    'label' => 'Refund History',
                    'fields' => [
                        'refunds',
                    ],
                ],
            ],
            'safety_note' => 'The detail surface renders only stored customer, address, payment, refund, and payment-attempt records from the order database and does not look up live customer/address relations or gateway state. Sensitive finance fields, payment secrets, and raw payloads remain out of scope.',
            'references' => ['C1.1.1', 'C1.1.2', 'C1.1.3', 'C1.1.4', 'B3.1.6', 'B3.3.6'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(Order $order): array
    {
        $order->loadMissing([
            'paymentAttempts',
            'payments.paymentAttempt',
            'refunds.payment.paymentAttempt',
        ]);

        return [
            'public_id' => $order->public_id,
            'order_type' => $order->order_type,
            'order_source' => $order->order_source,
            'status' => $order->status,
            'currency' => $order->currency,
            'amounts' => [
                'subtotal_amount_minor' => $order->subtotal_amount_minor,
                'discount_amount_minor' => $order->discount_amount_minor,
                'shipping_amount_minor' => $order->shipping_amount_minor,
                'tax_amount_minor' => $order->tax_amount_minor,
                'total_amount_minor' => $order->total_amount_minor,
            ],
            'design_approved' => $order->design_approved,
            'design_approved_at' => $order->design_approved_at?->toIso8601String(),
            'placed_at' => $order->placed_at?->toIso8601String(),
            'customer_snapshot' => $this->customerSnapshot($order->customer_snapshot),
            'shipping_address_snapshot' => $this->addressSnapshot($order->shipping_address_snapshot),
            'billing_address_snapshot' => $this->addressSnapshot($order->billing_address_snapshot),
            'payment_attempts' => $order->paymentAttempts
                ->sortByDesc(fn (PaymentAttempt $attempt): int => $attempt->initiated_at?->timestamp ?? $attempt->created_at?->timestamp ?? 0)
                ->values()
                ->map(fn (PaymentAttempt $attempt): array => $this->paymentAttemptSnapshot($attempt))
                ->all(),
            'payments' => $order->payments
                ->sortByDesc(fn (Payment $payment): int => $payment->paid_at?->timestamp ?? $payment->created_at?->timestamp ?? 0)
                ->values()
                ->map(fn (Payment $payment): array => $this->paymentSnapshot($payment))
                ->all(),
            'refunds' => $order->refunds
                ->sortByDesc(fn (Refund $refund): int => $refund->processed_at?->timestamp ?? $refund->created_at?->timestamp ?? 0)
                ->values()
                ->map(fn (Refund $refund): array => $this->refundSnapshot($refund))
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>
     */
    private function customerSnapshot(?array $snapshot): array
    {
        return [
            'public_id' => data_get($snapshot, 'public_id'),
            'name' => data_get($snapshot, 'name'),
            'email' => data_get($snapshot, 'email'),
            'phone' => data_get($snapshot, 'phone'),
            'company_name' => data_get($snapshot, 'company_name'),
            'customer_type' => data_get($snapshot, 'customer_type'),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private function addressSnapshot(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        return [
            'address_type' => data_get($snapshot, 'address_type'),
            'label' => data_get($snapshot, 'label'),
            'contact_name' => data_get($snapshot, 'contact_name'),
            'phone' => data_get($snapshot, 'phone'),
            'company_name' => data_get($snapshot, 'company_name'),
            'gstin' => data_get($snapshot, 'gstin'),
            'address_line_1' => data_get($snapshot, 'address_line_1'),
            'address_line_2' => data_get($snapshot, 'address_line_2'),
            'landmark' => data_get($snapshot, 'landmark'),
            'city' => data_get($snapshot, 'city'),
            'state' => data_get($snapshot, 'state'),
            'postal_code' => data_get($snapshot, 'postal_code'),
            'country_code' => data_get($snapshot, 'country_code'),
            'delivery_notes' => data_get($snapshot, 'delivery_notes'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAttemptSnapshot(PaymentAttempt $attempt): array
    {
        return [
            'public_id' => $attempt->public_id,
            'provider' => $attempt->provider,
            'attempt_type' => $attempt->attempt_type,
            'status' => $attempt->status,
            'amount_minor' => $attempt->amount_minor,
            'currency' => $attempt->currency,
            'gateway_order_id' => $attempt->gateway_order_id,
            'gateway_payment_id' => $attempt->gateway_payment_id,
            'gateway_reference' => $attempt->gateway_reference,
            'initiated_at' => $attempt->initiated_at?->toIso8601String(),
            'completed_at' => $attempt->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentSnapshot(Payment $payment): array
    {
        return [
            'provider' => $payment->provider,
            'payment_type' => $payment->payment_type,
            'status' => $payment->status,
            'amount_minor' => $payment->amount_minor,
            'currency' => $payment->currency,
            'provider_payment_id' => $payment->provider_payment_id,
            'provider_order_id' => $payment->provider_order_id,
            'provider_reference' => $payment->provider_reference,
            'receipt_number' => $payment->receipt_number,
            'payment_attempt_public_id' => $payment->paymentAttempt?->public_id,
            'paid_at' => $payment->paid_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function refundSnapshot(Refund $refund): array
    {
        return [
            'provider' => $refund->provider,
            'refund_type' => $refund->refund_type,
            'status' => $refund->status,
            'amount_minor' => $refund->amount_minor,
            'currency' => $refund->currency,
            'reason_code' => $refund->reason_code,
            'provider_refund_id' => $refund->provider_refund_id,
            'provider_payment_id' => $refund->provider_payment_id,
            'provider_reference' => $refund->provider_reference,
            'payment_attempt_public_id' => $refund->payment?->paymentAttempt?->public_id,
            'requested_at' => $refund->requested_at?->toIso8601String(),
            'approved_at' => $refund->approved_at?->toIso8601String(),
            'processed_at' => $refund->processed_at?->toIso8601String(),
        ];
    }
}
