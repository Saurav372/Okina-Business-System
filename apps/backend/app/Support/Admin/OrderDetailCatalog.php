<?php

namespace App\Support\Admin;

use App\Models\Order;

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
            ],
            'safety_note' => 'The detail surface renders only stored customer and address snapshots from the order record and does not look up live customer or address relations. Payment, refund, shipping, inventory, CRM, and finance histories remain out of scope.',
            'references' => ['C1.1.1', 'C1.1.2', 'C1.1.3', 'B3.1.6', 'B3.3.6'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(Order $order): array
    {
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
}
