<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCutoverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_static_and_seo_routes_are_served_by_laravel_without_the_astro_origin(): void
    {
        [$product] = $this->catalog();

        $this->get(route('storefront.how-it-works'))->assertOk()->assertSee('From your idea to finished apparel.');
        $this->get(route('storefront.policy', ['slug' => 'privacy']))->assertOk()->assertSee('Privacy policy');
        $this->get('/policies/not-real')->assertNotFound();
        $this->get(route('storefront.mockup'))->assertOk()->assertSee($product->name)->assertDontSee('127.0.0.1:4321');
        $this->get(route('storefront.robots'))->assertOk()->assertSee('Disallow: /checkout');
        $this->get(route('storefront.sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8')->assertSee($product->slug);
    }

    public function test_cart_is_server_rendered_and_supports_same_origin_updates(): void
    {
        [$product, $sku] = $this->catalog();
        $account = CustomerAccount::factory()->create();
        $this->actingAs($account, 'customer')->postJson(route('storefront.cart.items.store'), $this->cartPayload($product, $sku, 2))->assertOk();

        $this->get(route('storefront.cart'))
            ->assertOk()
            ->assertViewIs('storefront.cart')
            ->assertSee($product->name)
            ->assertSee('₹37.98')
            ->assertSee(route('storefront.checkout'), false)
            ->assertDontSee('127.0.0.1:4321');

        $item = CartItem::query()->firstOrFail();
        $this->put(route('storefront.cart.update', ['cartItem' => $item->public_id]), ['quantity' => 3])
            ->assertRedirect(route('storefront.cart'));
        $this->assertSame(3, $item->refresh()->quantity);

        $this->delete(route('storefront.cart.destroy', ['cartItem' => $item->public_id]))
            ->assertRedirect(route('storefront.cart'));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_customer_account_and_order_pages_are_same_origin_and_customer_scoped(): void
    {
        $account = CustomerAccount::factory()->create();
        $address = CustomerAddress::factory()->both()->create(['customer_id' => $account->customer_id, 'label' => 'Studio']);
        $order = Order::factory()->create([
            'customer_id' => $account->customer_id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'shipping_address_snapshot' => ['contact_name' => 'Studio Team', 'address_line_1' => '12 Market Road', 'city' => 'Jaipur', 'state' => 'Rajasthan', 'postal_code' => '302001', 'phone' => '9000000000'],
            'billing_address_snapshot' => ['contact_name' => 'Studio Team', 'address_line_1' => '12 Market Road', 'city' => 'Jaipur', 'state' => 'Rajasthan', 'postal_code' => '302001', 'phone' => '9000000000'],
            'total_amount_minor' => 49900,
            'placed_at' => now(),
        ]);
        $other = CustomerAccount::factory()->create();
        $otherOrder = Order::factory()->create(['customer_id' => $other->customer_id]);

        $this->actingAs($account, 'customer')->get(route('customer.account'))
            ->assertOk()->assertSee('Saved addresses')->assertSee('Studio')->assertSee($order->public_id)->assertDontSee('127.0.0.1:4321');
        $this->get(route('customer.orders.show', ['order' => $order->public_id]))
            ->assertOk()->assertSee($order->public_id)->assertSee('Order total');
        $this->get(route('customer.orders.show', ['order' => $otherOrder->public_id]))->assertNotFound();
    }

    public function test_checkout_creates_an_owned_order_and_preserves_the_confirmation_route(): void
    {
        [$product, $sku] = $this->catalog();
        $account = CustomerAccount::factory()->create();
        $address = CustomerAddress::factory()->both()->create(['customer_id' => $account->customer_id]);
        $this->actingAs($account, 'customer')->postJson(route('storefront.cart.items.store'), $this->cartPayload($product, $sku, 2))->assertOk();

        $this->get(route('storefront.checkout'))->assertOk()->assertSee('Confirm delivery.')->assertSee($address->label);
        $response = $this->post(route('storefront.checkout.store'), ['shipping_address_id' => $address->id]);
        $response->assertRedirectContains('https://cashfree.test/checkout/');

        $order = Order::query()->where('customer_id', $account->customer_id)->firstOrFail();
        $this->get(route('storefront.order-confirmation', ['order' => $order->public_id]))
            ->assertOk()->assertSee('Thank you. We’ve got it.')->assertSee($order->public_id);
    }

    /** @return array{0: Product, 1: ProductSku} */
    private function catalog(): array
    {
        $category = ProductCategory::factory()->create(['name' => 'Custom tees', 'slug' => 'custom-tees']);
        $product = Product::factory()->create([
            'name' => 'Cutover Tee',
            'slug' => 'cutover-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
            'base_price_minor' => 1999,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'display_type' => 'button',
            'values' => [['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true]],
            'is_required' => true,
        ]);
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUTOVER-M',
            'variant_key' => 'size:m',
            'option_values' => [['code' => 'm', 'label' => 'Medium']],
            'track_stock' => false,
            'price_minor' => 1899,
        ]);

        return [$product, $sku];
    }

    /** @return array<string, mixed> */
    private function cartPayload(Product $product, ProductSku $sku, int $quantity): array
    {
        return [
            'product_slug' => $product->slug,
            'sku_code' => $sku->sku_code,
            'quantity' => $quantity,
            'customization_snapshot' => [
                'schema_version' => 1,
                'product' => ['slug' => $product->slug, 'name' => $product->name],
                'sku_code' => $sku->sku_code,
                'variant_key' => $sku->variant_key,
                'selected_options_snapshot' => [['option_code' => 'size', 'value_code' => 'm', 'value_label' => 'Medium']],
                'print_method' => 'dtf',
                'print_position' => 'front',
                'placement' => ['x' => 50, 'y' => 50, 'scale' => 1, 'rotation' => 0],
            ],
        ];
    }
}
