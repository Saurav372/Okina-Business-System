<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Support\Admin\OrderDetailCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FinanceAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_policy_gates_correctly(): void
    {
        $viewer = $this->makeStaffWithPermissions('payments.view');
        $recorder = $this->makeStaffWithPermissions('payments.record');
        $editor = $this->makeStaffWithPermissions('payments.edit');
        $financeCostViewer = $this->makeStaffWithPermissions('finance.view_cost');

        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'provider' => 'manual',
            'status' => 'succeeded',
            'amount_minor' => 1000,
            'currency' => 'INR',
            'provider_payment_id' => 'PAY-TEST-123',
            'provider_order_id' => $order->public_id,
            'paid_at' => now(),
        ]);

        // viewAny
        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', Payment::class));
        $this->assertFalse(Gate::forUser($recorder)->allows('viewAny', Payment::class));

        // view
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $payment));
        $this->assertFalse(Gate::forUser($recorder)->allows('view', $payment));

        // create
        $this->assertTrue(Gate::forUser($recorder)->allows('create', Payment::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Payment::class));

        // update
        $this->assertTrue(Gate::forUser($editor)->allows('update', $payment));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $payment));

        // delete - always false
        $this->assertFalse(Gate::forUser($editor)->allows('delete', $payment));

        // viewSensitive
        $this->assertTrue(Gate::forUser($financeCostViewer)->allows('viewSensitive', $payment));
        $this->assertFalse(Gate::forUser($viewer)->allows('viewSensitive', $payment));
    }

    public function test_refund_policy_gates_correctly(): void
    {
        $viewer = $this->makeStaffWithPermissions('payments.view');
        $approver = $this->makeStaffWithPermissions('refunds.approve');

        $order = Order::factory()->create();
        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => null,
            'provider' => 'manual',
            'refund_type' => 'full',
            'status' => 'succeeded',
            'amount_minor' => 500,
            'currency' => 'INR',
            'provider_refund_id' => 'REF-TEST-123',
            'requested_at' => now(),
        ]);

        // viewAny
        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', Refund::class));
        $this->assertFalse(Gate::forUser($approver)->allows('viewAny', Refund::class));

        // view
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $refund));
        $this->assertFalse(Gate::forUser($approver)->allows('view', $refund));

        // create
        $this->assertTrue(Gate::forUser($approver)->allows('create', Refund::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Refund::class));

        // update - always false
        $this->assertFalse(Gate::forUser($approver)->allows('update', $refund));

        // delete - always false
        $this->assertFalse(Gate::forUser($approver)->allows('delete', $refund));
    }

    public function test_order_detail_catalog_hides_sensitive_fields_from_unauthorized_users(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'provider' => 'manual',
            'status' => 'succeeded',
            'amount_minor' => 1000,
            'currency' => 'INR',
            'provider_payment_id' => 'PAY-TEST-456',
            'provider_order_id' => $order->public_id,
            'gateway_fee_minor' => 50,
            'net_amount_minor' => 950,
            'paid_at' => now(),
        ]);

        // 1. Staff without finance.view_cost permission
        $normalStaff = $this->makeStaffWithPermissions('payments.view');

        $this->actingAs($normalStaff);
        $summary = app(OrderDetailCatalog::class)->summarize($order);

        $this->assertCount(1, $summary['payments']);
        $paymentSummary = $summary['payments'][0];
        $this->assertArrayNotHasKey('gateway_fee_minor', $paymentSummary);
        $this->assertArrayNotHasKey('net_amount_minor', $paymentSummary);

        // 2. Staff with finance.view_cost permission
        $financeStaff = $this->makeStaffWithPermissions('finance.view_cost');

        $this->actingAs($financeStaff);
        $summary = app(OrderDetailCatalog::class)->summarize($order);

        $paymentSummary = $summary['payments'][0];
        $this->assertArrayHasKey('gateway_fee_minor', $paymentSummary);
        $this->assertArrayHasKey('net_amount_minor', $paymentSummary);
        $this->assertEquals(50, $paymentSummary['gateway_fee_minor']);
        $this->assertEquals(950, $paymentSummary['net_amount_minor']);
    }

    private function makeStaffWithPermissions(string|array $permissionSlugs): User
    {
        $permissionSlugs = is_array($permissionSlugs) ? $permissionSlugs : [$permissionSlugs];

        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->headline()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                    'guard_name' => 'web',
                    'description' => str($slug)->headline()->toString(),
                    'is_sensitive' => true,
                ],
            );
        }

        $adminRole = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'description' => 'Admin',
                'is_system' => true,
                'sort_order' => 1,
            ],
        );

        $customSlug = 'custom_'.implode('_', $permissionSlugs);
        $customRole = Role::query()->updateOrCreate(
            ['slug' => $customSlug],
            [
                'name' => 'Custom Role '.$customSlug,
                'guard_name' => 'web',
                'description' => 'Custom Role',
                'is_system' => false,
                'sort_order' => 2,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $customRole->permissions()->sync($permissionIds);

        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($adminRole);
        $user->assignRole($customRole);

        return $user;
    }
}
