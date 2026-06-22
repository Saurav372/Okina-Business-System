<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private CustomerAccount $account;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = CustomerAccount::factory()->create();
        $this->customer = $this->account->customer;
    }

    public function test_guests_cannot_access_customer_api_routes(): void
    {
        $this->getJson('/api/customer/profile')->assertStatus(401);
        $this->getJson('/api/customer/addresses')->assertStatus(401);
        $this->getJson('/api/customer/orders')->assertStatus(401);
        $this->getJson('/api/customer/orders/some-id')->assertStatus(401);
        $this->postJson('/api/customer/orders/some-id/reorder')->assertStatus(401);
    }

    public function test_customer_can_retrieve_session_and_profile(): void
    {
        $this->actingAs($this->account, 'customer');

        $this->getJson('/api/customer/session')
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('customer.name', $this->customer->name);

        $this->getJson('/api/customer/profile')
            ->assertOk()
            ->assertJsonPath('data.name', $this->customer->name)
            ->assertJsonPath('data.email', $this->customer->email);
    }

    public function test_customer_can_retrieve_their_addresses(): void
    {
        $this->actingAs($this->account, 'customer');

        $address = CustomerAddress::factory()->create(['customer_id' => $this->customer->id]);

        $this->getJson('/api/customer/addresses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', $address->label);
    }

    public function test_customer_can_create_address(): void
    {
        $this->actingAs($this->account, 'customer');

        $this->postJson('/api/customer/addresses', [
            'address_type' => 'both',
            'label' => 'Office',
            'contact_name' => 'John Doe',
            'phone' => '1234567890',
            'address_line_1' => '123 Business Rd',
            'city' => 'Metropolis',
            'state' => 'New York',
            'postal_code' => '10001',
            'country_code' => 'IN',
            'is_default_shipping' => true,
        ])->assertStatus(211);

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'label' => 'Office',
            'is_default_shipping' => 1,
        ]);
    }

    public function test_customer_can_update_address(): void
    {
        $this->actingAs($this->account, 'customer');

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'label' => 'Home',
        ]);

        $this->putJson("/api/customer/addresses/{$address->id}", [
            'address_type' => 'shipping',
            'label' => 'My Main Home',
            'contact_name' => 'Jane Doe',
            'phone' => '0987654321',
            'address_line_1' => '456 Residential St',
            'city' => 'Metropolis',
            'state' => 'New York',
            'postal_code' => '10002',
            'country_code' => 'IN',
            'is_default_shipping' => false,
        ])->assertOk();

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
            'label' => 'My Main Home',
        ]);
    }

    public function test_customer_can_delete_address(): void
    {
        $this->actingAs($this->account, 'customer');

        $address = CustomerAddress::factory()->create(['customer_id' => $this->customer->id]);

        $this->deleteJson("/api/customer/addresses/{$address->id}")->assertOk();

        $this->assertSoftDeleted('customer_addresses', ['id' => $address->id]);
    }

    public function test_customer_can_toggle_default_address(): void
    {
        $this->actingAs($this->account, 'customer');

        $address1 = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'is_default_shipping' => true,
        ]);

        $address2 = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'is_default_shipping' => false,
        ]);

        $this->postJson("/api/customer/addresses/{$address2->id}/default", [
            'type' => 'shipping',
        ])->assertOk();

        $this->assertTrue($address2->refresh()->is_default_shipping);
        $this->assertFalse($address1->refresh()->is_default_shipping);
    }

    public function test_customer_cannot_access_other_customer_address(): void
    {
        $otherAccount = CustomerAccount::factory()->create();
        $otherAddress = CustomerAddress::factory()->create(['customer_id' => $otherAccount->customer->id]);

        $this->actingAs($this->account, 'customer');

        $this->putJson("/api/customer/addresses/{$otherAddress->id}", [
            'address_type' => 'shipping',
            'label' => 'Hacked label',
            'contact_name' => 'Jane Doe',
            'phone' => '0987654321',
            'address_line_1' => '456 Residential St',
            'city' => 'Metropolis',
            'state' => 'New York',
            'postal_code' => '10002',
            'country_code' => 'IN',
        ])->assertStatus(404);

        $this->deleteJson("/api/customer/addresses/{$otherAddress->id}")->assertStatus(404);
        $this->postJson("/api/customer/addresses/{$otherAddress->id}/default", ['type' => 'shipping'])->assertStatus(404);
    }

    public function test_customer_can_retrieve_their_orders(): void
    {
        $this->actingAs($this->account, 'customer');

        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $this->getJson('/api/customer/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $order->public_id);
    }

    public function test_customer_can_retrieve_their_order_details(): void
    {
        $this->actingAs($this->account, 'customer');

        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $this->getJson("/api/customer/orders/{$order->public_id}")
            ->assertOk()
            ->assertJsonPath('data.public_id', $order->public_id);
    }

    public function test_customer_cannot_view_other_customer_order_details(): void
    {
        $otherAccount = CustomerAccount::factory()->create();
        $otherOrder = Order::factory()->create([
            'customer_id' => $otherAccount->customer->id,
        ]);

        $this->actingAs($this->account, 'customer');

        $this->getJson("/api/customer/orders/{$otherOrder->public_id}")->assertStatus(404);
    }

    public function test_customer_can_reorder_past_order_items(): void
    {
        $this->actingAs($this->account, 'customer');

        $product = Product::factory()->create([
            'status' => 'active',
            'visibility' => 'public',
            'product_type' => Product::TYPE_SIMPLE,
            'customization_mode' => Product::CUSTOMIZATION_NONE,
        ]);
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'status' => 'active',
            'variant_key' => 'default',
            'direct_checkout_enabled' => true,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'product_name_snapshot' => $product->name,
            'product_slug_snapshot' => $product->slug,
            'sku_code_snapshot' => $sku->sku_code,
            'quantity' => 2,
            'unit_price_minor' => 1000,
            'line_subtotal_minor' => 2000,
            'line_total_minor' => 2000,
            'currency' => 'INR',
            'customization_fingerprint' => md5('dummy'),
            'customization_snapshot' => [],
        ]);

        // Trigger reorder
        $response = $this->postJson("/api/customer/orders/{$order->public_id}/reorder");
        $response->assertOk()
            ->assertJsonPath('success', true);

        // Verify active cart has item
        $cart = Cart::query()->where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->items);
        $this->assertSame($sku->sku_code, $cart->items->first()->sku_code_snapshot);
        $this->assertSame(2, $cart->items->first()->quantity);
    }
}
