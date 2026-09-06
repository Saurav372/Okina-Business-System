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
use Tests\TestCase;

class FinanceLedgerFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $financeStaff;

    private User $salesStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);

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
        if ($salesRole && $viewPermission) {
            $salesRole->permissions()->syncWithoutDetaching([$viewPermission->id]);
        }
    }

    /**
     * Test payment query filtering (start_date, end_date, provider, method, status, payment_type).
     */
    public function test_payment_index_endpoint_filters_correctly(): void
    {
        $this->actingAs($this->financeStaff);

        $order = Order::factory()->create([
            'subtotal_amount_minor' => 10000,
            'total_amount_minor' => 10000,
        ]);

        // 1. Create payments with different attributes
        $p1 = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'method' => 'upi',
            'status' => 'succeeded',
            'amount_minor' => 1000,
            'currency' => 'INR',
        ]);
        $p1->created_at = '2026-06-01 10:00:00';
        $p1->save();

        $p2 = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'partial',
            'provider' => 'razorpay',
            'method' => 'card',
            'status' => 'failed',
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);
        $p2->created_at = '2026-06-15 10:00:00';
        $p2->save();

        $p3 = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'method' => 'card',
            'status' => 'pending',
            'amount_minor' => 3000,
            'currency' => 'INR',
        ]);
        $p3->created_at = '2026-06-30 10:00:00';
        $p3->save();

        // Individual filters
        $this->getJson(route('admin.payments.index', ['provider' => 'razorpay']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 2000);

        $this->getJson(route('admin.payments.index', ['method' => 'upi']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 1000);

        $this->getJson(route('admin.payments.index', ['status' => 'pending']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 3000);

        $this->getJson(route('admin.payments.index', ['payment_type' => 'partial']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 2000);

        $this->getJson(route('admin.payments.index', ['start_date' => '2026-06-10']))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data'); // 15th and 30th

        $this->getJson(route('admin.payments.index', ['end_date' => '2026-06-20']))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data'); // 1st and 15th

        // Composite filter using AND logic
        $this->getJson(route('admin.payments.index', [
            'provider' => 'cashfree',
            'status' => 'succeeded',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
        ]))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 1000);
    }

    /**
     * Test refund query filtering (start_date, end_date, provider, status, refund_type).
     */
    public function test_refund_index_endpoint_filters_correctly(): void
    {
        $this->actingAs($this->financeStaff);

        $order = Order::factory()->create([
            'subtotal_amount_minor' => 10000,
            'total_amount_minor' => 10000,
        ]);
        $payment1 = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);
        $payment2 = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'provider' => 'razorpay',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $r1 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment1->id,
            'provider' => 'cashfree',
            'refund_type' => 'full',
            'status' => 'succeeded',
            'amount_minor' => 1000,
            'currency' => 'INR',
        ]);
        $r1->created_at = '2026-06-01 10:00:00';
        $r1->save();

        $r2 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment2->id,
            'provider' => 'razorpay',
            'refund_type' => 'partial',
            'status' => 'failed',
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);
        $r2->created_at = '2026-06-15 10:00:00';
        $r2->save();

        // Individual filters
        $this->getJson(route('admin.refunds.index', ['provider' => 'razorpay']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 2000);

        $this->getJson(route('admin.refunds.index', ['status' => 'failed']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 2000);

        $this->getJson(route('admin.refunds.index', ['refund_type' => 'full']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 1000);

        $this->getJson(route('admin.refunds.index', ['start_date' => '2026-06-10']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Composite
        $this->getJson(route('admin.refunds.index', [
            'provider' => 'cashfree',
            'status' => 'succeeded',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
        ]))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 1000);
    }

    /**
     * Test input validation.
     */
    public function test_ledger_endpoints_input_validation(): void
    {
        $this->actingAs($this->financeStaff);

        // Invalid date format
        $this->getJson(route('admin.payments.index', ['start_date' => 'banana']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);

        $this->getJson(route('admin.refunds.index', ['end_date' => 'banana']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        // Invalid status
        $this->getJson(route('admin.payments.index', ['status' => 'banana']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->getJson(route('admin.refunds.index', ['status' => 'banana']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Invalid type
        $this->getJson(route('admin.payments.index', ['payment_type' => 'banana']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_type']);

        $this->getJson(route('admin.refunds.index', ['refund_type' => 'banana']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['refund_type']);
    }

    /**
     * Test aggregate totals across paginated pages.
     */
    public function test_ledger_aggregates_across_paginated_pages(): void
    {
        $this->actingAs($this->financeStaff);

        $order = Order::factory()->create([
            'subtotal_amount_minor' => 100000,
            'total_amount_minor' => 100000,
        ]);

        // Create 25 payment records
        for ($i = 0; $i < 25; $i++) {
            Payment::create([
                'order_id' => $order->id,
                'payment_type' => 'full',
                'provider' => 'cashfree',
                'status' => 'succeeded',
                'amount_minor' => 1000,
                'currency' => 'INR',
                'gateway_fee_minor' => 10,
                'net_amount_minor' => 990,
            ]);
        }

        // Fetch page 1 (per_page = 10)
        $response1 = $this->getJson(route('admin.payments.index', ['per_page' => 10, 'page' => 1]));
        $response1->assertStatus(200)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total_amount_minor', 25000)
            ->assertJsonPath('meta.total_gateway_fee_minor', 250)
            ->assertJsonPath('meta.total_net_amount_minor', 24750);

        // Fetch page 2 (per_page = 10)
        $response2 = $this->getJson(route('admin.payments.index', ['per_page' => 10, 'page' => 2]));
        $response2->assertStatus(200)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total_amount_minor', 25000)
            ->assertJsonPath('meta.total_gateway_fee_minor', 250)
            ->assertJsonPath('meta.total_net_amount_minor', 24750);
    }

    /**
     * Test visibility policy for sensitive aggregates.
     */
    public function test_sensitive_aggregates_visibility_policy(): void
    {
        $order = Order::factory()->create([
            'subtotal_amount_minor' => 10000,
            'total_amount_minor' => 10000,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'status' => 'succeeded',
            'amount_minor' => 1000,
            'currency' => 'INR',
            'gateway_fee_minor' => 10,
            'net_amount_minor' => 990,
        ]);

        // Case 1: Authorized User (with finance.view_cost)
        $this->actingAs($this->financeStaff);
        $this->getJson(route('admin.payments.index'))
            ->assertStatus(200)
            ->assertJsonPath('meta.total_amount_minor', 1000)
            ->assertJsonPath('meta.total_gateway_fee_minor', 10)
            ->assertJsonPath('meta.total_net_amount_minor', 990);

        // Case 2: Unauthorized User (without finance.view_cost but can view payments)
        $this->actingAs($this->salesStaff);
        $this->getJson(route('admin.payments.index'))
            ->assertStatus(200)
            ->assertJsonPath('meta.total_amount_minor', 1000)
            ->assertJsonMissingPath('meta.total_gateway_fee_minor')
            ->assertJsonMissingPath('meta.total_net_amount_minor');
    }

    /**
     * Test aggregate contract remains stable when filtered dataset is empty.
     */
    public function test_empty_filtered_result_ledger_aggregates(): void
    {
        $this->actingAs($this->financeStaff);

        // No payments matching or existing in the database
        $response = $this->getJson(route('admin.payments.index', ['provider' => 'nonexistent']));
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total_amount_minor', 0)
            ->assertJsonPath('meta.total_gateway_fee_minor', 0)
            ->assertJsonPath('meta.total_net_amount_minor', 0);

        // No refunds matching or existing in the database
        $responseRefunds = $this->getJson(route('admin.refunds.index', ['provider' => 'nonexistent']));
        $responseRefunds->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total_amount_minor', 0);
    }
}
