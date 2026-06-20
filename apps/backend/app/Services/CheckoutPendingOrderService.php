<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Support\Idempotency\IdempotencyKeyGenerator;
use App\Support\Payments\PaymentAttemptRules;
use App\Support\Products\CustomizationSnapshotBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutPendingOrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutValidationService $validationService,
        private readonly CartPricingService $pricingService,
        private readonly CustomizationSnapshotBuilder $snapshots,
        private readonly PaymentAttemptRules $paymentAttemptRules,
        private readonly IdempotencyKeyGenerator $idempotencyKeys,
    ) {}

    /**
     * @return array{valid: bool, cart: array<string, mixed>, cart_validation: array<string, mixed>, customer: array<string, mixed>, shipping_address: array<string, mixed>|null, billing_address: array<string, mixed>|null, bulk_handoff: array<string, mixed>, pending_order: array<string, mixed>|null, payment_attempt: array<string, mixed>|null, errors: array<int, array<string, mixed>>}
     */
    public function payload(Request $request, array $input): array
    {
        $validation = $this->validationService->payload($request, $input);

        if (($validation['valid'] ?? false) !== true || ($validation['bulk_handoff']['required'] ?? false)) {
            return $validation + [
                'pending_order' => null,
                'payment_attempt' => null,
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

        if ($cart === null) {
            return $validation + [
                'pending_order' => null,
                'payment_attempt' => null,
            ];
        }

        $cart->loadMissing(['items.product', 'items.sku']);
        $pricing = $validation['cart']['pricing'] ?? $this->pricingService->summary($cart);
        $checkoutIdempotencyKey = $this->checkoutSubmissionIdempotencyKey($cart, $customer, $shippingAddress);

        [$order, $paymentAttempt] = DB::transaction(function () use ($customer, $shippingAddress, $billingAddress, $validation, $pricing, $cart, $checkoutIdempotencyKey): array {
            $order = Order::query()
                ->where('idempotency_key', $checkoutIdempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                $order = Order::create([
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
                    'idempotency_key' => $checkoutIdempotencyKey,
                ]);

                $orderItems = $order->items()->createMany(
                    $cart->items
                        ->sortBy('id')
                        ->values()
                        ->map(fn (CartItem $item): array => $this->orderItemAttributes($item))
                        ->all(),
                );

                $order->setRelation('items', $orderItems);
            } else {
                $order->load('items');
            }

            $paymentAttempt = $order->paymentAttempts()->firstOrCreate(
                ['idempotency_key' => $this->paymentAttemptIdempotencyKey($order)],
                $this->paymentAttemptAttributes($order, $pricing),
            );

            $order->setRelation('paymentAttempts', collect([$paymentAttempt]));

            return [$order, $paymentAttempt];
        });

        return $validation + [
            'pending_order' => $this->pendingOrderPayload($order, $paymentAttempt),
            'payment_attempt' => $this->paymentAttemptPayload($paymentAttempt, $order),
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
    private function pendingOrderPayload(Order $order, PaymentAttempt $paymentAttempt): array
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
            'payment_attempt_public_id' => $paymentAttempt->public_id,
            'items' => $this->orderItemsPayload($order),
            'next_step' => 'payment_attempt',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function orderItemsPayload(Order $order): array
    {
        return $order->items
            ->sortBy('id')
            ->values()
            ->map(fn (OrderItem $item): array => [
                'id' => $item->public_id,
                'product' => [
                    'slug' => $item->product_slug_snapshot,
                    'name' => $item->product_name_snapshot,
                ],
                'sku' => [
                    'code' => $item->sku_code_snapshot,
                ],
                'quantity' => $item->quantity,
                'pricing' => [
                    'currency' => $item->currency,
                    'unit_price_minor' => $item->unit_price_minor,
                    'line_subtotal_minor' => $item->line_subtotal_minor,
                    'line_total_minor' => $item->line_total_minor,
                    'price_source' => $item->price_source,
                ],
                'customization' => $item->customization_snapshot,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function orderItemAttributes(CartItem $item): array
    {
        $pricing = $this->pricingService->lineItem($item);

        return [
            'product_id' => $item->product_id,
            'sku_id' => $item->sku_id,
            'quantity' => $item->quantity,
            'product_name_snapshot' => $item->product_name_snapshot,
            'product_slug_snapshot' => $item->product_slug_snapshot,
            'sku_code_snapshot' => $item->sku_code_snapshot,
            'customization_fingerprint' => $item->customization_fingerprint,
            'customization_snapshot' => $this->snapshots->publicCartSnapshot($item->customization_snapshot ?? []),
            'unit_price_minor' => (int) ($pricing['unit_price_minor'] ?? 0),
            'line_subtotal_minor' => (int) ($pricing['line_subtotal_minor'] ?? 0),
            'line_total_minor' => (int) ($pricing['line_total_minor'] ?? 0),
            'currency' => (string) ($pricing['currency'] ?? 'INR'),
            'price_source' => (string) ($pricing['price_source'] ?? 'unpriced'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAttemptAttributes(Order $order, array $pricing): array
    {
        return [
            'provider' => $this->paymentAttemptRules->provider(),
            'attempt_type' => $this->paymentAttemptRules->attemptType(),
            'status' => 'created',
            'amount_minor' => (int) ($pricing['total_amount_minor'] ?? 0),
            'currency' => (string) ($pricing['currency'] ?? 'INR'),
        ];
    }

    private function paymentAttemptIdempotencyKey(Order $order): string
    {
        return $this->idempotencyKeys->make('payment_attempt', [
            $order->public_id,
            $this->paymentAttemptRules->provider(),
            $this->paymentAttemptRules->attemptType(),
        ]);
    }

    private function checkoutSubmissionIdempotencyKey(
        Cart $cart,
        CustomerAccount $customer,
        CustomerAddress $shippingAddress,
    ): string {
        return $this->idempotencyKeys->make('checkout_submission', [
            $cart->id,
            $customer->customer_id,
            $shippingAddress->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAttemptPayload(PaymentAttempt $paymentAttempt, Order $order): array
    {
        return [
            'id' => $paymentAttempt->public_id,
            'order_public_id' => $order->public_id,
            'attempt_type' => $paymentAttempt->attempt_type,
            'provider' => $paymentAttempt->provider,
            'status' => $paymentAttempt->status,
            'amount_minor' => $paymentAttempt->amount_minor,
            'currency' => $paymentAttempt->currency,
            'idempotency_key' => $paymentAttempt->idempotency_key,
            'gateway_order_id' => $paymentAttempt->gateway_order_id,
            'gateway_payment_id' => $paymentAttempt->gateway_payment_id,
            'gateway_reference' => $paymentAttempt->gateway_reference,
            'checkout_url' => $paymentAttempt->checkout_url,
        ];
    }
}
