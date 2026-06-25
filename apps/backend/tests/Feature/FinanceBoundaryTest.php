<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FinanceBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $financeStaff;

    private User $salesStaff;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);

        // Users
        $this->superAdmin = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole(Role::SUPER_ADMIN);

        $this->financeStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->financeStaff->assignRole(Role::FINANCE_STAFF);

        $this->salesStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->salesStaff->assignRole(Role::SALES_STAFF);

        // Grant payments.view to sales_staff role in test database
        $salesRole = Role::where('slug', Role::SALES_STAFF)->first();
        $viewPermission = Permission::where('slug', 'payments.view')->first();
        $salesRole->permissions()->attach($viewPermission->id);

        $this->unauthorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedUser->assignRole(Role::PRODUCTION_STAFF);
    }

    public function test_direct_policy_checks_work_correctly(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'gateway_fee_minor' => 200,
            'net_amount_minor' => 9800,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => 'full',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // Super Admin
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', Payment::class));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('view', $payment));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewSensitive', $payment));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', Refund::class));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('view', $refund));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewSensitive', $refund));

        // Finance Staff
        $this->assertTrue(Gate::forUser($this->financeStaff)->allows('viewAny', Payment::class));
        $this->assertTrue(Gate::forUser($this->financeStaff)->allows('view', $payment));
        $this->assertTrue(Gate::forUser($this->financeStaff)->allows('viewSensitive', $payment));
        $this->assertTrue(Gate::forUser($this->financeStaff)->allows('viewAny', Refund::class));
        $this->assertTrue(Gate::forUser($this->financeStaff)->allows('view', $refund));
        $this->assertTrue(Gate::forUser($this->financeStaff)->allows('viewSensitive', $refund));

        // Sales Staff (can view but NOT sensitive)
        $this->assertTrue(Gate::forUser($this->salesStaff)->allows('viewAny', Payment::class));
        $this->assertTrue(Gate::forUser($this->salesStaff)->allows('view', $payment));
        $this->assertFalse(Gate::forUser($this->salesStaff)->allows('viewSensitive', $payment));
        $this->assertTrue(Gate::forUser($this->salesStaff)->allows('viewAny', Refund::class));
        $this->assertTrue(Gate::forUser($this->salesStaff)->allows('view', $refund));
        $this->assertFalse(Gate::forUser($this->salesStaff)->allows('viewSensitive', $refund));

        // Unauthorized
        $this->assertFalse(Gate::forUser($this->unauthorizedUser)->allows('viewAny', Payment::class));
        $this->assertFalse(Gate::forUser($this->unauthorizedUser)->allows('view', $payment));
        $this->assertFalse(Gate::forUser($this->unauthorizedUser)->allows('viewSensitive', $payment));
        $this->assertFalse(Gate::forUser($this->unauthorizedUser)->allows('viewAny', Refund::class));
        $this->assertFalse(Gate::forUser($this->unauthorizedUser)->allows('view', $refund));
        $this->assertFalse(Gate::forUser($this->unauthorizedUser)->allows('viewSensitive', $refund));
    }

    public function test_endpoints_reject_unauthorized_users(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'payment_type' => 'full',
            'provider' => 'cashfree',
        ]);
        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => 'full',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $this->actingAs($this->unauthorizedUser);

        $this->getJson(route('admin.payments.index'))->assertStatus(403);
        $this->getJson(route('admin.payments.show', $payment->id))->assertStatus(403);
        $this->getJson(route('admin.refunds.index'))->assertStatus(403);
        $this->getJson(route('admin.refunds.show', $refund->id))->assertStatus(403);
    }

    public function test_sales_staff_cannot_see_sensitive_fields_in_collection_or_detail(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-123456']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'gateway_fee_minor' => 200,
            'net_amount_minor' => 9800,
            'receipt_number' => 'RC-999',
        ]);

        $this->actingAs($this->salesStaff);

        // Test Collection Endpoint
        $responseIndex = $this->getJson(route('admin.payments.index'));
        $responseIndex->assertStatus(200)
            ->assertJsonPath('data.0.amount_minor', 10000)
            ->assertJsonPath('data.0.order_public_id', 'ORD-123456')
            ->assertJsonMissingPath('data.0.gateway_fee_minor')
            ->assertJsonMissingPath('data.0.net_amount_minor')
            ->assertJsonMissingPath('data.0.order_id')
            ->assertJsonMissingPath('data.0.recorded_by_user_id');

        // Test Detail Endpoint
        $responseShow = $this->getJson(route('admin.payments.show', $payment->id));
        $responseShow->assertStatus(200)
            ->assertJsonPath('data.amount_minor', 10000)
            ->assertJsonPath('data.order_public_id', 'ORD-123456')
            ->assertJsonMissingPath('data.gateway_fee_minor')
            ->assertJsonMissingPath('data.net_amount_minor')
            ->assertJsonMissingPath('data.order_id')
            ->assertJsonMissingPath('data.recorded_by_user_id');
    }

    public function test_finance_staff_can_see_sensitive_fields_in_collection_and_detail(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-123456']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'gateway_fee_minor' => 200,
            'net_amount_minor' => 9800,
            'receipt_number' => 'RC-999',
        ]);

        $this->actingAs($this->financeStaff);

        // Test Collection Endpoint
        $responseIndex = $this->getJson(route('admin.payments.index'));
        $responseIndex->assertStatus(200)
            ->assertJsonPath('data.0.amount_minor', 10000)
            ->assertJsonPath('data.0.gateway_fee_minor', 200)
            ->assertJsonPath('data.0.net_amount_minor', 9800)
            ->assertJsonMissingPath('data.0.order_id');

        // Test Detail Endpoint
        $responseShow = $this->getJson(route('admin.payments.show', $payment->id));
        $responseShow->assertStatus(200)
            ->assertJsonPath('data.amount_minor', 10000)
            ->assertJsonPath('data.gateway_fee_minor', 200)
            ->assertJsonPath('data.net_amount_minor', 9800)
            ->assertJsonMissingPath('data.order_id');
    }

    public function test_refund_resources_omit_internal_keys(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-555']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'receipt_number' => 'RC-888',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => 'full',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $this->actingAs($this->salesStaff);

        $responseIndex = $this->getJson(route('admin.refunds.index'));
        $responseIndex->assertStatus(200)
            ->assertJsonPath('data.0.order_public_id', 'ORD-555')
            ->assertJsonPath('data.0.payment_receipt_number', 'RC-888')
            ->assertJsonMissingPath('data.0.order_id')
            ->assertJsonMissingPath('data.0.payment_id')
            ->assertJsonMissingPath('data.0.requested_by_user_id');

        $responseShow = $this->getJson(route('admin.refunds.show', $refund->id));
        $responseShow->assertStatus(200)
            ->assertJsonPath('data.order_public_id', 'ORD-555')
            ->assertJsonPath('data.payment_receipt_number', 'RC-888')
            ->assertJsonMissingPath('data.order_id')
            ->assertJsonMissingPath('data.payment_id')
            ->assertJsonMissingPath('data.requested_by_user_id');
    }
}
