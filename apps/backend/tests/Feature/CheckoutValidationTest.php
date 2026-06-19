<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_validation_passes_for_owned_customer_and_addresses(): void
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

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout/validation', [
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.errors', [])
            ->assertJsonPath('data.cart_validation.valid', true)
            ->assertJsonPath('data.cart.pricing.total_amount_minor', 3798)
            ->assertJsonPath('data.bulk_handoff.required', false)
            ->assertJsonPath('data.bulk_handoff.item_count', 2)
            ->assertJsonPath('data.bulk_handoff.threshold_quantity', 25)
            ->assertJsonPath('data.customer.public_id', $customer->public_id)
            ->assertJsonPath('data.customer.name', $customer->display_name)
            ->assertJsonPath('data.shipping_address.contact_name', 'Asha Sharma')
            ->assertJsonPath('data.billing_address.label', 'Office')
            ->assertJsonMissingPath('data.customer.id')
            ->assertJsonMissingPath('data.shipping_address.id')
            ->assertJsonMissingPath('data.billing_address.id');
    }

    public function test_checkout_validation_fails_when_shipping_address_is_missing(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount] = $this->createCustomerAccount();

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1))
            ->assertOk();

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout/validation', []);

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.errors.0.field', 'shipping_address_id')
            ->assertJsonPath('data.errors.0.code', 'shipping_address_required')
            ->assertJsonPath('data.shipping_address', null);
    }

    public function test_checkout_validation_fails_when_shipping_address_belongs_to_another_customer(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount, $customer] = $this->createCustomerAccount();
        [$otherAccount, $otherCustomer] = $this->createCustomerAccount();
        $ownBillingAddress = $this->createBillingAddress($customer);
        $otherShippingAddress = $this->createShippingAddress($otherCustomer, [
            'label' => 'Other Home',
            'contact_name' => 'Other Person',
            'phone' => '9000000001',
        ]);

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1))
            ->assertOk();

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout/validation', [
            'shipping_address_id' => $otherShippingAddress->id,
            'billing_address_id' => $ownBillingAddress->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.errors.0.field', 'shipping_address_id')
            ->assertJsonPath('data.errors.0.code', 'shipping_address_id_unavailable')
            ->assertJsonPath('data.shipping_address', null)
            ->assertJsonPath('data.billing_address.label', 'Billing');
    }

    public function test_checkout_validation_fails_when_billing_address_belongs_to_another_customer(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount, $customer] = $this->createCustomerAccount();
        [$_otherAccount, $otherCustomer] = $this->createCustomerAccount();
        $shippingAddress = $this->createShippingAddress($customer, [
            'label' => 'Home',
            'contact_name' => 'Asha Sharma',
            'phone' => '9123456789',
        ]);
        $otherBillingAddress = $this->createBillingAddress($otherCustomer, [
            'label' => 'Other Office',
            'contact_name' => 'Other Person',
            'phone' => '9000000002',
        ]);

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1))
            ->assertOk();

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout/validation', [
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $otherBillingAddress->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.errors.0.field', 'billing_address_id')
            ->assertJsonPath('data.errors.0.code', 'billing_address_id_unavailable')
            ->assertJsonPath('data.shipping_address.label', 'Home')
            ->assertJsonPath('data.billing_address', null);
    }

    public function test_checkout_validation_fails_when_shipping_address_was_soft_deleted(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount, $customer] = $this->createCustomerAccount();
        $shippingAddress = $this->createShippingAddress($customer, [
            'label' => 'Home',
            'contact_name' => 'Asha Sharma',
            'phone' => '9123456789',
        ]);

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1))
            ->assertOk();

        $shippingAddress->delete();

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout/validation', [
            'shipping_address_id' => $shippingAddress->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.errors.0.field', 'shipping_address_id')
            ->assertJsonPath('data.errors.0.code', 'shipping_address_id_unavailable')
            ->assertJsonPath('data.shipping_address', null);
    }

    public function test_checkout_validation_returns_bulk_handoff_response_at_threshold(): void
    {
        $catalog = $this->createCatalog();
        [$customerAccount] = $this->createCustomerAccount();

        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 25))
            ->assertOk();

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/checkout/validation', []);

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.errors.0.field', 'quantity')
            ->assertJsonPath('data.errors.0.code', 'bulk_quantity_threshold_reached')
            ->assertJsonPath('data.bulk_handoff.required', true)
            ->assertJsonPath('data.bulk_handoff.threshold_quantity', 25)
            ->assertJsonPath('data.bulk_handoff.item_count', 25)
            ->assertJsonPath('data.bulk_handoff.next_step', 'bulk_enquiry')
            ->assertJsonPath('data.shipping_address', null)
            ->assertJsonPath('data.billing_address', null)
            ->assertJsonPath('data.cart_validation.valid', true);
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
