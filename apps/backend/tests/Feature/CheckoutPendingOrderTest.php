<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPendingOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_pending_order_from_valid_cart_and_addresses(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount, $customer] = $this->createCustomerAccount();
        $shippingAddress = $this->createShippingAddress($customer, [
            'label' => 'Home',
            'contact_name' => 'Asha Sharma',
            'phone' => '9123456789',
            'address_line_1' => '12 Market Road',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'postal_code' => '302001',
        ]);
        $billingAddress = $this->createBillingAddress($customer, [
            'label' => 'Office',
            'contact_name' => 'Asha Sharma',
            'phone' => '9123456789',
            'address_line_1' => '42 Business Park',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'postal_code' => '302017',
        ]);

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 2))
            ->assertOk();

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout', [
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.pending_order.status', 'pending_payment')
            ->assertJsonPath('data.pending_order.order_type', 'website_order')
            ->assertJsonPath('data.pending_order.order_source', 'website')
            ->assertJsonPath('data.pending_order.total_amount_minor', 3798)
            ->assertJsonPath('data.pending_order.customer.public_id', $customer->public_id)
            ->assertJsonPath('data.pending_order.shipping_address.label', 'Home')
            ->assertJsonPath('data.pending_order.billing_address.label', 'Office')
            ->assertJsonPath('data.pending_order.payment_attempt_public_id', $response->json('data.payment_attempt.id'))
            ->assertJsonPath('data.pending_order.items.0.product.slug', 'custom-tee')
            ->assertJsonPath('data.pending_order.items.0.sku.code', 'SKU-CUSTOM-TEE-M')
            ->assertJsonPath('data.pending_order.items.0.quantity', 2)
            ->assertJsonPath('data.pending_order.items.0.pricing.currency', 'INR')
            ->assertJsonPath('data.pending_order.items.0.pricing.unit_price_minor', 1899)
            ->assertJsonPath('data.pending_order.items.0.pricing.line_total_minor', 3798)
            ->assertJsonPath('data.pending_order.items.0.pricing.price_source', 'sku_price')
            ->assertJsonPath('data.pending_order.items.0.customization.print_method', 'dtf')
            ->assertJsonPath('data.pending_order.items.0.customization.product.slug', 'custom-tee')
            ->assertJsonPath('data.payment_attempt.order_public_id', $response->json('data.pending_order.public_id'))
            ->assertJsonPath('data.payment_attempt.status', 'initiated')
            ->assertJsonPath('data.payment_attempt.provider', 'cashfree')
            ->assertJsonPath('data.payment_attempt.attempt_type', 'website_checkout')
            ->assertJsonPath('data.payment_attempt.amount_minor', 3798)
            ->assertJsonPath('data.payment_attempt.currency', 'INR')
            ->assertJsonPath('data.checkout_state', 'payment_initiated')
            ->assertJsonPath('data.payment_attempt.next_step', 'payment_gateway')
            ->assertJsonPath('data.pending_order.next_step', 'payment_gateway')
            ->assertJsonPath('data.cart_validation.valid', true)
            ->assertJsonPath('data.bulk_handoff.required', false)
            ->assertJsonMissingPath('data.pending_order.id')
            ->assertJsonMissingPath('data.pending_order.customer.id')
            ->assertJsonMissingPath('data.pending_order.shipping_address.id')
            ->assertJsonMissingPath('data.pending_order.billing_address.id')
            ->assertJsonPath('data.payment_attempt.gateway_payment_id', null);

        $this->assertMatchesRegularExpression('/^cf_order_[A-F0-9]{16}$/', (string) $response->json('data.payment_attempt.gateway_order_id'));
        $this->assertMatchesRegularExpression('/^cf_order_[A-F0-9]{16}$/', (string) $response->json('data.payment_attempt.gateway_reference'));
        $this->assertMatchesRegularExpression('#^https://cashfree\.test/checkout/cf_order_[A-F0-9]{16}$#', (string) $response->json('data.payment_attempt.checkout_url'));
        $this->assertNotNull($response->json('data.payment_attempt.initiated_at'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payment_attempts', 1);

        $order = Order::query()->with(['items', 'paymentAttempts'])->firstOrFail();
        $orderItem = $order->items->firstOrFail();
        $paymentAttempt = $order->paymentAttempts->firstOrFail();

        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame('website_order', $order->order_type);
        $this->assertSame('website', $order->order_source);
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame(3798, $order->total_amount_minor);
        $this->assertSame($customer->public_id, $order->customer_snapshot['public_id']);
        $this->assertSame('Home', $order->shipping_address_snapshot['label']);
        $this->assertSame('Office', $order->billing_address_snapshot['label']);

        $this->assertSame($order->id, $orderItem->order_id);
        $this->assertSame($catalog['product']->id, $orderItem->product_id);
        $this->assertSame($catalog['sku']->id, $orderItem->sku_id);
        $this->assertSame(2, $orderItem->quantity);
        $this->assertSame('custom-tee', $orderItem->product_slug_snapshot);
        $this->assertSame('Custom Tee', $orderItem->product_name_snapshot);
        $this->assertSame('SKU-CUSTOM-TEE-M', $orderItem->sku_code_snapshot);
        $this->assertSame(1899, $orderItem->unit_price_minor);
        $this->assertSame(3798, $orderItem->line_subtotal_minor);
        $this->assertSame(3798, $orderItem->line_total_minor);
        $this->assertSame('INR', $orderItem->currency);
        $this->assertSame('sku_price', $orderItem->price_source);
        $this->assertSame('Keep centered', $orderItem->customization_snapshot['customer_note']);
        $this->assertSame('dtf', $orderItem->customization_snapshot['print_method']);
        $this->assertSame('custom-tee', $orderItem->customization_snapshot['product']['slug']);

        $this->assertSame($order->id, $paymentAttempt->order_id);
        $this->assertSame('cashfree', $paymentAttempt->provider);
        $this->assertSame('website_checkout', $paymentAttempt->attempt_type);
        $this->assertSame('initiated', $paymentAttempt->status);
        $this->assertSame(3798, $paymentAttempt->amount_minor);
        $this->assertSame('INR', $paymentAttempt->currency);
        $this->assertMatchesRegularExpression('/^cf_order_[A-F0-9]{16}$/', (string) $paymentAttempt->gateway_order_id);
        $this->assertSame($paymentAttempt->gateway_order_id, $paymentAttempt->gateway_reference);
        $this->assertMatchesRegularExpression('#^https://cashfree\.test/checkout/cf_order_[A-F0-9]{16}$#', (string) $paymentAttempt->checkout_url);
        $this->assertNotNull($paymentAttempt->initiated_at);
        $this->assertStringStartsWith('idempotency:payment_attempt:', $paymentAttempt->idempotency_key);
        $this->assertStringContainsString(':cashfree:', $paymentAttempt->idempotency_key);
        $this->assertStringContainsString(':website_checkout', $paymentAttempt->idempotency_key);
    }

    public function test_checkout_does_not_create_pending_order_for_bulk_cart(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount] = $this->createCustomerAccount();

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 25))
            ->assertOk();

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout', []);

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.bulk_handoff.required', true)
            ->assertJsonPath('data.bulk_handoff.item_count', 25)
            ->assertJsonPath('data.pending_order', null)
            ->assertJsonPath('data.payment_attempt', null);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payment_attempts', 0);
    }

    public function test_repeated_checkout_reuses_the_existing_pending_order_and_payment_attempt(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount, $customer] = $this->createCustomerAccount();
        $shippingAddress = $this->createShippingAddress($customer);

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 2))
            ->assertOk();

        $checkoutPayload = [
            'shipping_address_id' => $shippingAddress->id,
        ];

        $firstResponse = $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/checkout', $checkoutPayload)
            ->assertOk();

        $secondResponse = $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/checkout', $checkoutPayload)
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.checkout_state', 'payment_initiated')
            ->assertJsonPath('data.pending_order.next_step', 'payment_gateway')
            ->assertJsonMissingPath('data.pending_order.id')
            ->assertJsonMissingPath('data.pending_order.customer.id')
            ->assertJsonMissingPath('data.pending_order.shipping_address.id');

        $this->assertMatchesRegularExpression('/^cf_order_[A-F0-9]{16}$/', (string) $secondResponse->json('data.payment_attempt.gateway_order_id'));
        $this->assertMatchesRegularExpression('#^https://cashfree\.test/checkout/cf_order_[A-F0-9]{16}$#', (string) $secondResponse->json('data.payment_attempt.checkout_url'));

        $this->assertSame(
            $firstResponse->json('data.pending_order.public_id'),
            $secondResponse->json('data.pending_order.public_id'),
        );
        $this->assertSame(
            $firstResponse->json('data.payment_attempt.id'),
            $secondResponse->json('data.payment_attempt.id'),
        );
        $this->assertSame(
            $firstResponse->json('data.payment_attempt.order_public_id'),
            $secondResponse->json('data.payment_attempt.order_public_id'),
        );

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payment_attempts', 1);

        $order = Order::query()->firstOrFail();

        $this->assertStringStartsWith('idempotency:checkout_submission:', $order->idempotency_key);
    }

    public function test_failed_checkout_reuses_the_pending_order_and_returns_a_public_safe_failure_response(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount, $customer] = $this->createCustomerAccount();
        $shippingAddress = $this->createShippingAddress($customer);

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 2))
            ->assertOk();

        $checkoutPayload = [
            'shipping_address_id' => $shippingAddress->id,
        ];

        $firstResponse = $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/checkout', $checkoutPayload)
            ->assertOk();

        $paymentAttempt = PaymentAttempt::query()->firstOrFail();
        $paymentAttempt->forceFill([
            'status' => 'failed',
            'completed_at' => now(),
        ])->save();
        $paymentAttempt->refresh();

        $this->assertSame('failed', $paymentAttempt->status);
        $this->assertSame('failed', PaymentAttempt::query()->firstOrFail()->status);

        $failedResponse = $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/checkout', $checkoutPayload)
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.checkout_state', 'payment_failed')
            ->assertJsonPath('data.checkout_message', 'The order was saved, but payment could not be completed. Please retry payment for this order.')
            ->assertJsonPath('data.pending_order.public_id', $firstResponse->json('data.pending_order.public_id'))
            ->assertJsonPath('data.pending_order.next_step', 'retry_payment_attempt')
            ->assertJsonPath('data.payment_attempt.id', $firstResponse->json('data.payment_attempt.id'))
            ->assertJsonPath('data.payment_attempt.status', 'failed')
            ->assertJsonPath('data.payment_attempt.next_step', 'retry_payment_attempt')
            ->assertJsonPath('data.errors.0.field', 'payment_attempt')
            ->assertJsonPath('data.errors.0.code', 'payment_attempt_failed')
            ->assertJsonMissingPath('data.pending_order.id')
            ->assertJsonPath('data.payment_attempt.gateway_order_id', $firstResponse->json('data.payment_attempt.gateway_order_id'))
            ->assertJsonPath('data.payment_attempt.gateway_payment_id', null)
            ->assertJsonPath('data.payment_attempt.gateway_reference', $firstResponse->json('data.payment_attempt.gateway_reference'))
            ->assertJsonPath('data.payment_attempt.checkout_url', $firstResponse->json('data.payment_attempt.checkout_url'));

        $this->assertSame(
            $firstResponse->json('data.pending_order.public_id'),
            $failedResponse->json('data.pending_order.public_id'),
        );
        $this->assertSame(
            $firstResponse->json('data.payment_attempt.id'),
            $failedResponse->json('data.payment_attempt.id'),
        );

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payment_attempts', 1);

        $this->assertSame('failed', PaymentAttempt::query()->firstOrFail()->status);
    }

    private function createCustomerAccount(): array
    {
        $customerAccount = CustomerAccount::factory()->create();

        return [
            $customerAccount,
            $customerAccount->customer()->firstOrFail(),
        ];
    }

    private function createShippingAddress(Customer $customer, array $overrides = []): CustomerAddress
    {
        return CustomerAddress::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'address_type' => CustomerAddress::TYPE_SHIPPING,
            'is_default_shipping' => true,
            'is_default_billing' => false,
            'contact_name' => $customer->display_name,
            'phone' => $customer->phone ?? '9123456789',
            'address_line_1' => '12 Market Road',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'postal_code' => '302001',
            'country_code' => 'IN',
        ], $overrides));
    }

    private function createBillingAddress(Customer $customer, array $overrides = []): CustomerAddress
    {
        return CustomerAddress::factory()->billing()->create(array_merge([
            'customer_id' => $customer->id,
            'label' => 'Billing',
            'contact_name' => $customer->display_name,
            'phone' => $customer->phone ?? '9123456789',
            'address_line_1' => '42 Business Park',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'postal_code' => '302017',
            'country_code' => 'IN',
        ], $overrides));
    }

    private function createCatalog(): array
    {
        $category = ProductCategory::factory()->create([
            'slug' => 'custom-apparel',
        ]);

        $product = Product::factory()->create([
            'slug' => 'custom-tee',
            'name' => 'Custom Tee',
            'primary_category_id' => $category->id,
            'base_price_minor' => 1599,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'published_at' => now(),
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
            'is_required' => true,
        ]);

        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'variant_key' => 'size:m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
            'status' => 'active',
            'direct_checkout_enabled' => true,
            'quote_required' => false,
            'price_minor' => 1899,
        ]);

        return [
            'category' => $category,
            'product' => $product->refresh(),
            'sku' => $sku->refresh(),
        ];
    }

    private function cartPayload(Product $product, ProductSku $sku, int $quantity): array
    {
        return [
            'product_slug' => $product->slug,
            'sku_code' => $sku->sku_code,
            'quantity' => $quantity,
            'customization_snapshot' => [
                'schema_version' => 1,
                'product' => [
                    'slug' => $product->slug,
                    'name' => $product->name,
                ],
                'sku_code' => $sku->sku_code,
                'variant_key' => $sku->variant_key,
                'selected_options_snapshot' => [
                    [
                        'option_code' => 'size',
                        'value_code' => 'm',
                        'value_label' => 'Medium',
                    ],
                ],
                'print_method' => 'dtf',
                'print_position' => 'front',
                'placement' => [
                    'x' => 42,
                    'y' => 58,
                    'scale' => 0.72,
                    'rotation' => 0,
                ],
                'customer_note' => 'Keep centered',
            ],
        ];
    }
}
