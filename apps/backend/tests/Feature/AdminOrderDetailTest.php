<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Support\Admin\OrderDetailCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_resource_exposes_a_read_only_snapshot_only_detail_definition(): void
    {
        $detail = OrderResource::registration()['detail'];

        $this->assertSame('website_order_detail', $detail['key']);
        $this->assertSame(Order::class, $detail['model']);
        $this->assertTrue($detail['read_only']);
        $this->assertSame(['view'], $detail['allowed_actions']);
        $this->assertSame('stored_snapshots_only', $detail['snapshot_policy']);
        $this->assertSame(['public_id', 'order_type', 'order_source', 'status', 'currency', 'amounts', 'design_approved', 'design_approved_at', 'placed_at'], $detail['sections']['summary']['fields']);
        $this->assertSame(['customer_snapshot'], $detail['sections']['customer']['fields']);
        $this->assertSame(['shipping_address_snapshot'], $detail['sections']['shipping_address']['fields']);
        $this->assertSame(['billing_address_snapshot'], $detail['sections']['billing_address']['fields']);
        $this->assertSame(['payment_attempts'], $detail['sections']['payment_attempts']['fields']);
        $this->assertSame(['payments'], $detail['sections']['payments']['fields']);
        $this->assertSame(['refunds'], $detail['sections']['refunds']['fields']);
        $this->assertStringContainsString('stored customer, address, payment, refund, and payment-attempt records', $detail['safety_note']);

        foreach (['create', 'edit', 'delete', 'forceDelete', 'restore', 'replicate', 'status', 'payment', 'refund', 'shipping'] as $blockedAction) {
            $this->assertContains($blockedAction, $detail['blocked_actions']);
        }
    }

    public function test_order_resource_detail_access_is_gated_by_the_order_view_permission(): void
    {
        $orderViewer = $this->makeStaffUser('order_viewer', ['orders.view']);
        $dashboardOnly = $this->makeStaffUser('dashboard_only', ['dashboard.access']);

        $this->assertTrue(OrderResource::canAccess($orderViewer));
        $this->assertFalse(OrderResource::canAccess($dashboardOnly));
    }

    public function test_detail_summary_renders_stored_customer_address_and_payment_histories_without_live_relation_labels(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Live Customer Name',
            'display_name' => 'Live Customer Display',
            'email' => 'live.customer@example.test',
            'phone' => '9999999999',
            'customer_type' => 'company',
        ]);

        $shippingAddress = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Live Shipping Label',
            'contact_name' => 'Live Shipping Contact',
            'city' => 'Live City',
            'state' => 'Live State',
        ]);

        $billingAddress = CustomerAddress::factory()->billing()->create([
            'customer_id' => $customer->id,
            'label' => 'Live Billing Label',
            'contact_name' => 'Live Billing Contact',
            'city' => 'Live Billing City',
            'state' => 'Live Billing State',
        ]);

        $order = Order::query()->create([
            'public_id' => 'OD-DETAILED',
            'order_type' => 'website_order',
            'order_source' => 'website',
            'status' => OrderStatus::Confirmed->value(),
            'customer_id' => $customer->id,
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
            'customer_snapshot' => [
                'public_id' => 'CUS-SNAPSHOT-1',
                'name' => 'Snapshot Customer Name',
                'email' => 'snapshot.customer@example.test',
                'phone' => '8888888888',
                'company_name' => 'Snapshot Co',
                'customer_type' => 'individual',
            ],
            'shipping_address_snapshot' => [
                'address_type' => 'shipping',
                'label' => 'Snapshot Shipping Label',
                'contact_name' => 'Snapshot Shipping Contact',
                'phone' => '9000000001',
                'company_name' => 'Snapshot Co',
                'gstin' => 'GSTIN-SHIPPING',
                'address_line_1' => '12 Snapshot Street',
                'address_line_2' => 'Suite 3',
                'landmark' => 'Near Snapshot Park',
                'city' => 'Snapshot City',
                'state' => 'Snapshot State',
                'postal_code' => '110001',
                'country_code' => 'IN',
                'delivery_notes' => 'Leave at reception',
            ],
            'billing_address_snapshot' => [
                'address_type' => 'billing',
                'label' => 'Snapshot Billing Label',
                'contact_name' => 'Snapshot Billing Contact',
                'phone' => '9000000002',
                'company_name' => 'Snapshot Billing Co',
                'gstin' => 'GSTIN-BILLING',
                'address_line_1' => '44 Billing Avenue',
                'address_line_2' => null,
                'landmark' => 'Opposite Snapshot Tower',
                'city' => 'Billing City',
                'state' => 'Billing State',
                'postal_code' => '400001',
                'country_code' => 'IN',
                'delivery_notes' => 'Billing desk only',
            ],
            'subtotal_amount_minor' => 125000,
            'discount_amount_minor' => 5000,
            'shipping_amount_minor' => 2500,
            'tax_amount_minor' => 22500,
            'total_amount_minor' => 145000,
            'currency' => 'INR',
            'design_approved' => true,
            'design_approved_at' => now(),
            'placed_at' => now()->subDay(),
        ]);

        // Create two stored order items to be rendered in the detail snapshot.
        $product1 = Product::factory()->create([
            'name' => 'Snapshot Product One',
            'visibility' => Product::VISIBILITY_PUBLIC,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $sku1 = ProductSku::factory()->create([
            'product_id' => $product1->id,
            'sku_code' => 'SKU-ONE',
            'price_minor' => 50000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'sku_id' => $sku1->id,
            'quantity' => 1,
            'product_name_snapshot' => 'Snapshot Product One',
            'product_slug_snapshot' => 'snapshot-product-one',
            'sku_code_snapshot' => 'SKU-ONE',
            'customization_fingerprint' => 'FINGERPRINT1',
            'customization_snapshot' => [
                'schema_version' => 1,
                'product' => ['slug' => 'snapshot-product-one', 'name' => 'Snapshot Product One'],
                'sku_code' => 'SKU-ONE',
                'selected_options_snapshot' => [
                    ['option_code' => 'color', 'value_code' => 'red', 'value_label' => 'Red'],
                ],
                'print_method' => 'screen',
                'placement' => ['x' => 50, 'y' => 50, 'scale' => 1.0, 'rotation' => 0],
                'files' => [],
                'customer_note' => 'Note',
            ],
            'unit_price_minor' => 50000,
            'line_subtotal_minor' => 50000,
            'line_total_minor' => 50000,
            'currency' => 'INR',
            'price_source' => 'order_snapshot',
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Snapshot Product Two',
            'visibility' => Product::VISIBILITY_PUBLIC,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $sku2 = ProductSku::factory()->create([
            'product_id' => $product2->id,
            'sku_code' => 'SKU-TWO',
            'price_minor' => 25000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'sku_id' => $sku2->id,
            'quantity' => 2,
            'product_name_snapshot' => 'Snapshot Product Two',
            'product_slug_snapshot' => 'snapshot-product-two',
            'sku_code_snapshot' => 'SKU-TWO',
            'customization_fingerprint' => 'FINGERPRINT2',
            'customization_snapshot' => [
                'schema_version' => 1,
                'product' => ['slug' => 'snapshot-product-two', 'name' => 'Snapshot Product Two'],
                'sku_code' => 'SKU-TWO',
                'selected_options_snapshot' => [
                    ['option_code' => 'size', 'value_code' => 'm', 'value_label' => 'M'],
                ],
                'print_method' => 'dtg',
                'placement' => ['x' => 40, 'y' => 60, 'scale' => 0.9, 'rotation' => 5],
                'files' => [],
                'customer_note' => null,
            ],
            'unit_price_minor' => 25000,
            'line_subtotal_minor' => 50000,
            'line_total_minor' => 50000,
            'currency' => 'INR',
            'price_source' => 'order_snapshot',
        ]);

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 145000,
            'currency' => 'INR',
            'idempotency_key' => 'idempotency:payment_attempt:OD-DETAILED',
            'gateway_order_id' => 'cf_order_DETAIL001',
            'gateway_payment_id' => 'cf_pay_DETAIL001',
            'gateway_reference' => 'cf_order_DETAIL001',
            'initiated_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(50),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'status' => 'succeeded',
            'amount_minor' => 145000,
            'currency' => 'INR',
            'provider_payment_id' => 'cf_pay_DETAIL001',
            'provider_order_id' => 'cf_order_DETAIL001',
            'provider_reference' => 'cf_pay_DETAIL001',
            'receipt_number' => 'RCPT-145000',
            'paid_at' => now()->subMinutes(45),
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => 'full',
            'status' => 'succeeded',
            'amount_minor' => 145000,
            'currency' => 'INR',
            'reason_code' => 'customer_request',
            'provider_refund_id' => 'cf_ref_DETAIL001',
            'provider_payment_id' => 'cf_pay_DETAIL001',
            'provider_reference' => 'cf_ref_DETAIL001',
            'requested_at' => now()->subMinutes(20),
            'approved_at' => now()->subMinutes(15),
            'processed_at' => now()->subMinutes(10),
        ]);

        $summary = app(OrderDetailCatalog::class)->summarize($order);

        $this->assertSame('OD-DETAILED', $summary['public_id']);
        $this->assertSame('confirmed', $summary['status']);
        $this->assertSame(145000, $summary['amounts']['total_amount_minor']);
        $this->assertSame('Snapshot Customer Name', $summary['customer_snapshot']['name']);
        $this->assertSame('snapshot.customer@example.test', $summary['customer_snapshot']['email']);
        $this->assertSame('Snapshot Shipping Label', $summary['shipping_address_snapshot']['label']);
        $this->assertSame('Snapshot Billing Label', $summary['billing_address_snapshot']['label']);
        $this->assertSame('Snapshot State', $summary['shipping_address_snapshot']['state']);
        $this->assertSame('Billing State', $summary['billing_address_snapshot']['state']);
        $this->assertNotSame($customer->name, $summary['customer_snapshot']['name']);
        $this->assertNotSame($shippingAddress->label, $summary['shipping_address_snapshot']['label']);
        $this->assertNotSame($billingAddress->label, $summary['billing_address_snapshot']['label']);

        $this->assertCount(1, $summary['payment_attempts']);
        $this->assertCount(1, $summary['payments']);
        $this->assertCount(1, $summary['refunds']);

        $this->assertSame('cf_order_DETAIL001', $summary['payment_attempts'][0]['gateway_order_id']);
        $this->assertSame('cf_pay_DETAIL001', $summary['payment_attempts'][0]['gateway_payment_id']);
        $this->assertSame('succeeded', $summary['payment_attempts'][0]['status']);
        $this->assertSame('cf_pay_DETAIL001', $summary['payments'][0]['provider_payment_id']);
        $this->assertSame('RCPT-145000', $summary['payments'][0]['receipt_number']);
        $this->assertSame('cf_ref_DETAIL001', $summary['refunds'][0]['provider_refund_id']);
        $this->assertSame('customer_request', $summary['refunds'][0]['reason_code']);
        $this->assertSame($attempt->public_id, $summary['payments'][0]['payment_attempt_public_id']);
        $this->assertSame($attempt->public_id, $summary['refunds'][0]['payment_attempt_public_id']);

        // Items snapshot assertions
        $this->assertArrayHasKey('items', $summary);
        $this->assertCount(2, $summary['items']);
        $this->assertSame('SKU-ONE', $summary['items'][0]['sku_code']);
        $this->assertSame(1, $summary['items'][0]['quantity']);
        $this->assertSame(50000, $summary['items'][0]['unit_price_minor']);
        $this->assertArrayHasKey('customization_snapshot', $summary['items'][0]);
        $this->assertSame('snapshot-product-one', $summary['items'][0]['customization_snapshot']['product']['slug']);
        $this->assertSame('Snapshot Product One', $summary['items'][0]['customization_snapshot']['product']['name']);
        $this->assertArrayNotHasKey('raw_payload', $summary['items'][0]['customization_snapshot']);

        $this->assertArrayNotHasKey('gateway_fee_minor', $summary['payments'][0]);
        $this->assertArrayNotHasKey('net_amount_minor', $summary['payments'][0]);
        $this->assertArrayNotHasKey('metadata', $summary['payments'][0]);
        $this->assertArrayNotHasKey('notes', $summary['payments'][0]);
        $this->assertArrayNotHasKey('metadata', $summary['refunds'][0]);
        $this->assertArrayNotHasKey('gateway_fee_minor', $summary['refunds'][0]);
        $this->assertArrayNotHasKey('raw_payload', $summary);
        $this->assertArrayNotHasKey('customer', $summary);
        $this->assertArrayNotHasKey('shippingAddress', $summary);
        $this->assertArrayNotHasKey('billingAddress', $summary);
    }

    private function makeStaffUser(string $roleSlug, array $permissionSlugs): User
    {
        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->headline()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                    'guard_name' => 'web',
                    'description' => str($slug)->headline()->toString(),
                    'is_sensitive' => false,
                ],
            );
        }

        $role = Role::query()->updateOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'guard_name' => 'web',
                'description' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
