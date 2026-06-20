<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Permission;
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
        $this->assertStringContainsString('stored customer and address snapshots', $detail['safety_note']);

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

    public function test_detail_summary_renders_stored_customer_and_address_snapshots_without_live_relation_labels(): void
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
        $this->assertArrayNotHasKey('payments', $summary);
        $this->assertArrayNotHasKey('refunds', $summary);
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
