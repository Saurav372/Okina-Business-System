<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RefundApprovalTest extends TestCase
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

        // Sales staff has refunds.request but not refunds.approve
        $salesRole = Role::where('slug', Role::SALES_STAFF)->first();
        $requestPermission = Permission::query()->updateOrCreate(
            ['slug' => 'refunds.request'],
            [
                'name' => 'Request Refunds',
                'group' => 'refunds',
                'guard_name' => 'web',
                'description' => 'Can request refunds',
                'is_sensitive' => true,
            ]
        );
        $salesRole->permissions()->syncWithoutDetaching([$requestPermission->id]);
    }

    public function test_authorized_user_can_approve_requested_refund(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create(['public_id' => 'ORD-APP-201']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 4000,
            'currency' => 'INR',
            'requested_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.approve', $refund->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $refund->id)
            ->assertJsonPath('data.status', Refund::STATUS_APPROVED)
            ->assertJsonPath('data.order_public_id', 'ORD-APP-201');

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_APPROVED,
            'approved_by_user_id' => $this->financeStaff->id,
        ]);

        $this->assertNotNull($response->json('data.approved_at'));

        Event::assertDispatched(AuditEvent::class, function ($event) use ($refund, $order, $payment) {
            return $event->key === 'refunds.refund_approved' &&
                $event->payload['refund_public_id'] === $refund->id &&
                $event->payload['order_public_id'] === $order->public_id &&
                $event->payload['payment_public_id'] === $payment->id &&
                $event->payload['status'] === Refund::STATUS_APPROVED &&
                $event->payload['approved_by_user_id'] === $this->financeStaff->id;
        });
    }

    public function test_unauthorized_user_cannot_approve_refund(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 4000,
            'currency' => 'INR',
        ]);

        $response = $this->actingAs($this->salesStaff)
            ->postJson(route('admin.refunds.approve', $refund->id));

        $response->assertStatus(403);
    }

    public function test_cannot_approve_refund_not_in_requested_status(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // Succeeded status refund
        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 4000,
            'currency' => 'INR',
        ]);

        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.approve', $refund->id));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refund'])
            ->assertJsonFragment([
                'refund' => [Refund::ERROR_ONLY_REQUESTED_CAN_BE_APPROVED],
            ]);
    }

    public function test_double_approval_fails_gracefully_and_keeps_original_state(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 4000,
            'currency' => 'INR',
        ]);

        // First approval
        $response1 = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.approve', $refund->id));
        $response1->assertStatus(200);

        $firstApprovedAt = $response1->json('data.approved_at');
        $this->assertNotNull($firstApprovedAt);

        // Reset Event Fake to clear the first event dispatch trace
        Event::fake([AuditEvent::class]);

        // Second approval attempt should fail
        $response2 = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.approve', $refund->id));

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['refund']);

        // Check original state is preserved
        $refund->refresh();
        $this->assertSame(Refund::STATUS_APPROVED, $refund->status);
        $this->assertSame($firstApprovedAt, $refund->approved_at->toIso8601String());

        // Verify no second audit event was emitted
        Event::assertNotDispatched(AuditEvent::class);
    }
}
