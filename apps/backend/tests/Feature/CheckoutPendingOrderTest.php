<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Order;
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
            ->assertJsonPath('data.pending_order.next_step', 'payment_attempt')
            ->assertJsonPath('data.cart_validation.valid', true)
            ->assertJsonPath('data.bulk_handoff.required', false)
            ->assertJsonMissingPath('data.pending_order.id')
            ->assertJsonMissingPath('data.pending_order.customer.id')
            ->assertJsonMissingPath('data.pending_order.shipping_address.id')
            ->assertJsonMissingPath('data.pending_order.billing_address.id');

        $this->assertDatabaseCount('orders', 1);

        $order = Order::query()->firstOrFail();

        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame('website_order', $order->order_type);
        $this->assertSame('website', $order->order_source);
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame(3798, $order->total_amount_minor);
        $this->assertSame($customer->public_id, $order->customer_snapshot['public_id']);
        $this->assertSame('Home', $order->shipping_address_snapshot['label']);
        $this->assertSame('Office', $order->billing_address_snapshot['label']);
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
            ->assertJsonPath('data.pending_order', null);

        $this->assertDatabaseCount('orders', 0);
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
