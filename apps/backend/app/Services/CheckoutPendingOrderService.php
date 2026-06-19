<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutPendingOrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutValidationService $validationService,
    ) {}

    /**
     * @return array{valid: bool, cart: array<string, mixed>, cart_validation: array<string, mixed>, customer: array<string, mixed>, shipping_address: array<string, mixed>|null, billing_address: array<string, mixed>|null, bulk_handoff: array<string, mixed>, pending_order: array<string, mixed>|null, errors: array<int, array<string, mixed>>}
     */
    public function payload(Request $request, array $input): array
    {
        $validation = $this->validationService->payload($request, $input);

        if (($validation['valid'] ?? false) !== true || ($validation['bulk_handoff']['required'] ?? false)) {
            return $validation + [
                'pending_order' => null,
            ];
        }

        $customer = $this->currentCustomer($request);

        abort_unless($customer instanceof CustomerAccount, 403);

        $shippingAddress = $this->resolveAddress(
            customerId: $customer->customer_id,
            addressId: $input['shipping_address_id'] ?? null,
            field: 'shipping_address_id',
            required: true,
        );

        $billingAddress = array_key_exists('billing_address_id', $input) && $input['billing_address_id'] !== null
            ? $this->resolveAddress(
                customerId: $customer->customer_id,
                addressId: $input['billing_address_id'],
                field: 'billing_address_id',
                required: false,
            )
            : $shippingAddress;

        $cart = $this->cartService->current($request, false);
        $pricing = $validation['cart']['pricing'] ?? [];

        if ($cart === null) {
            return $validation + [
                'pending_order' => null,
            ];
        }

        $order = DB::transaction(function () use ($customer, $shippingAddress, $billingAddress, $validation, $pricing): Order {
            return Order::create([
                'order_type' => OrderType::WebsiteOrder->value(),
                'order_source' => 'website',
                'status' => OrderStatus::PendingPayment->value(),
                'customer_id' => $customer->customer_id,
                'shipping_address_id' => $shippingAddress?->id,
                'billing_address_id' => $billingAddress?->id,
                'customer_snapshot' => $validation['customer'],
                'shipping_address_snapshot' => $validation['shipping_address'],
                'billing_address_snapshot' => $validation['billing_address'],
                'subtotal_amount_minor' => (int) ($pricing['subtotal_amount_minor'] ?? 0),
                'discount_amount_minor' => (int) ($pricing['discount_amount_minor'] ?? 0),
                'shipping_amount_minor' => (int) ($pricing['shipping_amount_minor'] ?? 0),
                'tax_amount_minor' => (int) ($pricing['tax_amount_minor'] ?? 0),
                'total_amount_minor' => (int) ($pricing['total_amount_minor'] ?? 0),
                'currency' => (string) ($pricing['currency'] ?? 'INR'),
                'placed_at' => now(),
            ]);
        });

        return $validation + [
            'pending_order' => $this->pendingOrderPayload($order),
        ];
    }

    private function currentCustomer(Request $request): ?CustomerAccount
    {
        $customer = $request->user('customer');

        return $customer instanceof CustomerAccount ? $customer : null;
    }

    private function resolveAddress(int $customerId, mixed $addressId, string $field, bool $required): ?CustomerAddress
    {
        if ($addressId === null) {
            if ($required) {
                abort(422, 'A shipping address is required for checkout.');
            }

            return null;
        }

        if (! is_int($addressId) && ! (is_string($addressId) && ctype_digit($addressId))) {
            abort(422, 'The selected address is invalid.');
        }

        $address = CustomerAddress::query()
            ->whereKey((int) $addressId)
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->first();

        if ($address === null) {
            abort(422, 'The selected address is not available for checkout.');
        }

        return $address;
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingOrderPayload(Order $order): array
    {
        return [
            'public_id' => $order->public_id,
            'order_type' => $order->order_type,
            'order_source' => $order->order_source,
            'status' => $order->status,
            'currency' => $order->currency,
            'subtotal_amount_minor' => $order->subtotal_amount_minor,
            'discount_amount_minor' => $order->discount_amount_minor,
            'shipping_amount_minor' => $order->shipping_amount_minor,
            'tax_amount_minor' => $order->tax_amount_minor,
            'total_amount_minor' => $order->total_amount_minor,
            'placed_at' => $order->placed_at?->toISOString(),
            'customer' => $order->customer_snapshot,
            'shipping_address' => $order->shipping_address_snapshot,
            'billing_address' => $order->billing_address_snapshot,
            'next_step' => 'payment_attempt',
        ];
    }
}
