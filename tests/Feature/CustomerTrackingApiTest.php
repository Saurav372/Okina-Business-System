<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    private CustomerAccount $customerAccount;

    private Customer $customer;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up customer
        $this->customerAccount = CustomerAccount::factory()->create();
        $this->customer = $this->customerAccount->customer;

        // Set up authorized super admin
        $role = Role::query()->updateOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
                'description' => 'Super Admin',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $this->adminUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($role);
    }

    /**
     * Test OrderTimelineService calculations for Website (direct) orders.
     */
    public function test_timeline_service_computes_correct_website_order_steps(): void
    {
        /** @var OrderTimelineService $service */
        $service = app(OrderTimelineService::class);

        // 1. Pending payment website order
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'order_type' => 'website_order',
            'status' => 'pending_payment',
            'total_amount_minor' => 10000,
        ]);

        $timeline = $service->generateTimeline($order);
        $this->assertCount(7, $timeline);
        $this->assertSame('placed', $timeline[0]['key']);
        $this->assertSame('completed', $timeline[0]['status']);
        $this->assertSame('paid', $timeline[1]['key']);
        $this->assertSame('active', $timeline[1]['status']); // cascading status
        $this->assertSame('design', $timeline[2]['key']);
        $this->assertSame('pending', $timeline[2]['status']);

        // 2. Confirmed & Paid
        $order->update([
            'status' => 'confirmed',
        ]);
        // Simulate a payment of full amount
        $order->payments()->create([
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'method' => 'upi',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'paid_at' => now(),
        ]);

        $timeline = $service->generateTimeline($order->fresh());
        $this->assertSame('completed', $timeline[1]['status']); // Payment received
        $this->assertSame('active', $timeline[2]['status']); // Design Review is active

        // 3. Design status issue found
        $order->update([
            'design_status' => 'issue_found',
            'design_issue_message' => 'Vector logo resolution is too low.',
        ]);

        $timeline = $service->generateTimeline($order->fresh());
        $this->assertSame('warning', $timeline[2]['status']); // Design Review shows warning
        $this->assertSame('Vector logo resolution is too low.', $timeline[2]['detail_message']);
        $this->assertSame('pending', $timeline[3]['status']); // Next step is pending

        // 4. Design status approved, in production
        $order->update([
            'design_status' => 'approved',
            'production_status' => 'in_production',
            'status' => 'in_production',
        ]);

        $timeline = $service->generateTimeline($order->fresh());
        $this->assertSame('completed', $timeline[2]['status']); // Design Review complete
        $this->assertSame('active', $timeline[3]['status']); // printing/production process is active

        // 5. Production completed -> Ready to ship
        $order->update([
            'production_status' => 'completed',
            'status' => 'ready_to_ship',
            'ready_to_ship_at' => now(),
        ]);

        $timeline = $service->generateTimeline($order->fresh());
        $this->assertSame('completed', $timeline[3]['status']); // printing/production process complete
        $this->assertSame('completed', $timeline[4]['status']); // ready to ship complete
        $this->assertSame('active', $timeline[5]['status']); // Shipped is now active
    }

    /**
     * Test OrderTimelineService calculations for Sales-team orders.
     */
    public function test_timeline_service_computes_correct_sales_order_steps(): void
    {
        /** @var OrderTimelineService $service */
        $service = app(OrderTimelineService::class);

        // Confirmed sales order with advance schedule
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'order_type' => 'sales_order',
            'status' => 'confirmed',
            'total_amount_minor' => 20000,
        ]);

        // Advance paid (partially paid)
        $order->payments()->create([
            'payment_type' => 'advance',
            'provider' => 'manual',
            'method' => 'bank_transfer',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'paid_at' => now(),
        ]);

        $timeline = $service->generateTimeline($order->fresh());
        $this->assertCount(8, $timeline);
        $this->assertSame('placed', $timeline[0]['key']);
        $this->assertSame('completed', $timeline[0]['status']);
        $this->assertSame('advance_paid', $timeline[1]['key']);
        $this->assertSame('completed', $timeline[1]['status']); // Advance Payment Received
        $this->assertSame('design', $timeline[2]['key']);
        $this->assertSame('active', $timeline[2]['status']); // Design Review is active
        $this->assertSame('balance_paid', $timeline[4]['key']);
        $this->assertSame('pending', $timeline[4]['status']); // Balance Payment pending
        $this->assertSame('Balance Payment Pending', $timeline[4]['label']);

        // After design approved and balance fully paid
        $order->update([
            'design_status' => 'approved',
        ]);
        $order->payments()->create([
            'payment_type' => 'balance',
            'provider' => 'manual',
            'method' => 'bank_transfer',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'paid_at' => now(),
        ]);

        $timeline = $service->generateTimeline($order->fresh());
        $this->assertSame('completed', $timeline[4]['status']); // Balance Paid
        $this->assertSame('Balance Payment Received', $timeline[4]['label']);
    }

    /**
     * Test OrderTimelineService calculations for Cancelled orders.
     */
    public function test_timeline_service_computes_correct_cancelled_order_steps(): void
    {
        /** @var OrderTimelineService $service */
        $service = app(OrderTimelineService::class);

        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer requested cancellation.',
        ]);

        $timeline = $service->generateTimeline($order);
        $this->assertCount(2, $timeline);
        $this->assertSame('placed', $timeline[0]['key']);
        $this->assertSame('cancelled', $timeline[1]['key']);
        $this->assertSame('completed', $timeline[1]['status']);
        $this->assertSame('Customer requested cancellation.', $timeline[1]['detail_message']);
    }

    /**
     * Test Customer API order detail endpoint returns new attributes and timeline.
     */
    public function test_customer_api_exposes_tracking_attributes_and_timeline(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'courier_name' => 'BlueDart',
            'tracking_number' => 'BD12345678',
            'tracking_url' => 'https://bluedart.com/track',
            'estimated_delivery_at' => now()->addDays(5),
            'design_status' => 'issue_found',
            'design_issue_message' => 'Incorrect dimensions.',
        ]);

        $this->actingAs($this->customerAccount, 'customer');

        $this->getJson("/api/customer/orders/{$order->public_id}")
            ->assertOk()
            ->assertJsonPath('data.courier_name', 'BlueDart')
            ->assertJsonPath('data.tracking_number', 'BD12345678')
            ->assertJsonPath('data.tracking_url', 'https://bluedart.com/track')
            ->assertJsonPath('data.design_status', 'issue_found')
            ->assertJsonPath('data.design_issue_message', 'Incorrect dimensions.')
            ->assertJsonStructure([
                'data' => [
                    'timeline',
                    'ready_to_ship_at',
                    'shipped_at',
                    'delivered_at',
                    'estimated_delivery_at',
                ],
            ]);
    }

    /**
     * Test Admin status action validation and timestamps logic.
     */
    public function test_admin_can_update_order_status_fields_and_timestamps(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending_payment',
            'design_status' => 'under_review',
            'production_status' => 'not_started',
            'shipping_status' => 'not_shipped',
        ]);

        $this->actingAs($this->adminUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'approved',
                'production_status' => 'completed',
                'shipping_status' => 'shipped',
                'cancellation_reason' => null,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('approved', $order->design_status);
        $this->assertSame('completed', $order->production_status);
        $this->assertSame('shipped', $order->shipping_status);

        // Verify timestamps auto-injected
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->ready_to_ship_at);
        $this->assertNotNull($order->shipped_at);
    }

    /**
     * Test Admin shipping details action.
     */
    public function test_admin_can_update_order_shipping_details(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->adminUser)
            ->postJson("/admin/orders/{$order->public_id}/shipping", [
                'courier_name' => 'BlueDart',
                'tracking_number' => 'BD987654',
                'tracking_url' => 'https://track.bluedart.com',
                'estimated_delivery_at' => '2026-07-01 12:00:00',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('BlueDart', $order->courier_name);
        $this->assertSame('BD987654', $order->tracking_number);
        $this->assertSame('https://track.bluedart.com', $order->tracking_url);
        $this->assertSame('2026-07-01 12:00:00', $order->estimated_delivery_at->toDateTimeString());
    }

    /**
     * Test non-authorized users cannot hit administrative endpoints.
     */
    public function test_non_authorized_users_cannot_update_order_tracking_fields(): void
    {
        $order = Order::factory()->create();

        // 1. Guest client gets 401 Unauthorized
        $this->postJson("/admin/orders/{$order->public_id}/status", [
            'status' => 'confirmed',
            'design_status' => 'approved',
            'production_status' => 'completed',
            'shipping_status' => 'shipped',
        ])->assertStatus(401);

        // 2. Customer client gets redirected to login (302) since they aren't authenticated under admin guard
        $this->actingAs($this->customerAccount, 'customer')
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'approved',
                'production_status' => 'completed',
                'shipping_status' => 'shipped',
            ])->assertStatus(302);

        // 3. User with dashboard role but without orders.manage permission (gets forbidden 403)
        $unauthorizedAdmin = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales Staff',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
        $unauthorizedAdmin->assignRole($salesRole);

        $this->actingAs($unauthorizedAdmin)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'approved',
                'production_status' => 'completed',
                'shipping_status' => 'shipped',
            ])->assertStatus(403);
    }
}
