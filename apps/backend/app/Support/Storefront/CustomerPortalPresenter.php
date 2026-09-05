<?php

namespace App\Support\Storefront;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\StoredFile;
use App\Services\FileUploadService;
use App\Services\OrderTimelineService;
use App\Support\Payments\PaymentStateRecalculationRules;
use App\Support\Products\CustomizationSnapshotBuilder;

class CustomerPortalPresenter
{
    public function __construct(
        private readonly PaymentStateRecalculationRules $stateRules,
        private readonly CustomizationSnapshotBuilder $snapshots,
        private readonly OrderTimelineService $timelineService,
        private readonly FileUploadService $files,
    ) {}

    /** @return array<string, mixed> */
    public function profile(Customer $customer): array
    {
        return [
            'public_id' => $customer->public_id,
            'customer_type' => $customer->customer_type,
            'display_name' => $customer->display_name,
            'company_name' => $customer->company_name,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'whatsapp_phone' => $customer->whatsapp_phone,
            'status' => $customer->status,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function addresses(Customer $customer): array
    {
        return $customer->addresses()->get()->map(fn (CustomerAddress $address): array => $this->address($address))->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function orders(Customer $customer): array
    {
        return Order::query()
            ->where('customer_id', $customer->id)
            ->with(['payments', 'refunds'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Order $order): array => $this->orderSummary($order))
            ->all();
    }

    /** @return array<string, mixed> */
    public function order(Customer $customer, string $publicId): array
    {
        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('public_id', $publicId)
            ->with(['items', 'paymentAttempts', 'payments', 'refunds', 'mockups.file'])
            ->firstOrFail();

        return $this->orderDetail($order);
    }

    /** @return array<string, mixed> */
    public function address(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'address_type' => $address->address_type,
            'label' => $address->label,
            'contact_name' => $address->contact_name,
            'phone' => $address->phone,
            'company_name' => $address->company_name,
            'gstin' => $address->gstin,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country_code' => $address->country_code,
            'is_default_shipping' => (bool) $address->is_default_shipping,
            'is_default_billing' => (bool) $address->is_default_billing,
            'delivery_notes' => $address->delivery_notes,
        ];
    }

    /** @return array<string, mixed> */
    private function orderSummary(Order $order): array
    {
        return [
            'public_id' => $order->public_id,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'payment_status' => $this->paymentStatus($order),
            'total_amount_minor' => $order->total_amount_minor,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function orderDetail(Order $order): array
    {
        return [
            ...$this->orderSummary($order),
            'subtotal_amount_minor' => $order->subtotal_amount_minor,
            'discount_amount_minor' => $order->discount_amount_minor,
            'shipping_amount_minor' => $order->shipping_amount_minor,
            'tax_amount_minor' => $order->tax_amount_minor,
            'design_approved' => (bool) $order->design_approved,
            'design_approved_at' => $order->design_approved_at?->toIso8601String(),
            'design_notes' => $order->design_notes,
            'customer_notes' => $order->customer_notes,
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'ready_to_ship_at' => $order->ready_to_ship_at?->toIso8601String(),
            'shipped_at' => $order->shipped_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
            'design_status' => $order->design_status,
            'design_issue_message' => $order->design_issue_message,
            'production_status' => $order->production_status,
            'shipping_status' => $order->shipping_status,
            'courier_name' => $order->courier_name,
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'timeline' => $this->timelineService->generateTimeline($order),
            'shipping_address_snapshot' => $order->shipping_address_snapshot,
            'billing_address_snapshot' => $order->billing_address_snapshot,
            'items' => $order->items->map(fn (OrderItem $item): array => $this->orderItem($item))->all(),
            'proofs' => $order->mockups
                ->filter(fn ($proof): bool => $proof->file instanceof StoredFile
                    && $proof->file->status === StoredFile::STATUS_ACTIVE
                    && $proof->file->visibility === StoredFile::VISIBILITY_CUSTOMER_VISIBLE
                    && $proof->file->customer_id === $order->customer_id)
                ->map(fn ($proof): array => [
                    'display_name' => $proof->display_name,
                    'notes' => $proof->notes,
                    'is_featured' => (bool) $proof->is_featured,
                    'mime_type' => $proof->file->mime_type,
                    'size_bytes' => (int) $proof->file->size_bytes,
                    'preview_url' => $this->files->temporaryPreviewUrl($proof->file, 60),
                    'download_url' => $this->files->temporaryDownloadUrl($proof->file, 60),
                    'shared_at' => $proof->created_at?->toIso8601String(),
                ])->values()->all(),
            'payments' => $order->payments->map(fn (Payment $payment): array => [
                'provider' => $payment->provider,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
                'provider_payment_id' => $payment->provider_payment_id,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ])->all(),
            'refunds' => $order->refunds->map(fn (Refund $refund): array => [
                'provider' => $refund->provider,
                'refund_type' => $refund->refund_type,
                'status' => $refund->status,
                'amount_minor' => $refund->amount_minor,
                'currency' => $refund->currency,
                'processed_at' => $refund->processed_at?->toIso8601String(),
            ])->all(),
        ];
    }

    private function paymentStatus(Order $order): string
    {
        $paid = (int) $order->payments->where('status', 'succeeded')->sum('amount_minor');
        $refunded = (int) $order->refunds->where('status', 'succeeded')->sum('amount_minor');

        return $this->stateRules->calculate($order->total_amount_minor, $paid, $refunded, $order->getExpectedAdvanceAmount());
    }

    /** @return array<string, mixed> */
    private function orderItem(OrderItem $item): array
    {
        $customization = $item->customization_snapshot === null
            ? null
            : $this->snapshots->publicCartSnapshot($item->customization_snapshot);
        if (is_array($customization)) {
            unset($customization['mockup_preview'], $customization['mockup_preview_url']);
        }

        return [
            'public_id' => $item->public_id,
            'product_name' => $item->product_name_snapshot,
            'product_slug' => $item->product_slug_snapshot,
            'sku_code' => $item->sku_code_snapshot,
            'quantity' => $item->quantity,
            'unit_price_minor' => $item->unit_price_minor,
            'line_total_minor' => $item->line_total_minor,
            'customization_snapshot' => $customization,
        ];
    }
}
