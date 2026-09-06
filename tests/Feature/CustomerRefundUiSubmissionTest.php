<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRefundUiSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected Customer $customer;
    protected Order $order;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $permPayView = Permission::query()->firstOrCreate(['slug' => 'payments.view'], [
            'name' => 'Payments View',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'View payments',
            'is_sensitive' => false,
        ]);

        $permRefReq = Permission::query()->firstOrCreate(['slug' => 'refunds.request'], [
            'name' => 'Refunds Request',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'Request refunds',
            'is_sensitive' => false,
        ]);

        $financeRole = Role::query()->firstOrCreate(['slug' => 'finance_staff'], [
            'name' => 'Finance Staff',
            'guard_name' => 'web',
        ]);
        $financeRole->permissions()->syncWithoutDetaching([$permPayView->id, $permRefReq->id]);

        $this->financeUser = User::factory()->create();
        $this->financeUser->roles()->attach($financeRole);

        $this->customer = Customer::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'public_id' => 'ORD-UI-TEST-101',
            'total_amount_minor' => 50000, // ₹500.00
        ]);

        $this->payment = Payment::create([
            'order_id' => $this->order->id,
            'payment_type' => 'full',
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 50000, // ₹500.00
            'currency' => 'INR',
        ]);
    }

    public function test_ui_form_submits_amount_rupees_and_successfully_creates_refund(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->post(route('admin.refunds.store'), [
                'payment_id' => $this->payment->id,
                'amount_rupees' => '250.00',
                'amount_minor' => '', // As happens when JS hasn't populated or user enters rupees
                'reason_code' => 'customer_cancellation',
                'reason_note' => 'Customer requested cancellation before shipment',
            ]);

        $refund = Refund::where('payment_id', $this->payment->id)->first();
        $this->assertNotNull($refund);
        $this->assertEquals(25000, $refund->amount_minor);
        $this->assertEquals('customer_cancellation', $refund->reason_code);
        $this->assertEquals(Refund::STATUS_REQUESTED, $refund->status);

        $response->assertRedirect(route('admin.refunds.show', $refund));
        $response->assertSessionHas('success');
    }

    public function test_ui_form_submits_full_amount_rupees(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->post(route('admin.refunds.store'), [
                'payment_id' => $this->payment->id,
                'amount_rupees' => '500.00',
                'amount_minor' => '',
                'reason_code' => 'damaged_goods',
                'reason_note' => 'Damaged in transit',
            ]);

        $refund = Refund::where('payment_id', $this->payment->id)->first();
        $this->assertNotNull($refund);
        $this->assertEquals(50000, $refund->amount_minor);
        $this->assertEquals(Refund::TYPE_FULL, $refund->refund_type);

        $response->assertRedirect(route('admin.refunds.show', $refund));
    }

    public function test_refund_amount_exceeding_balance_fails_validation(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->from(route('admin.refunds.index'))
            ->post(route('admin.refunds.store'), [
                'payment_id' => $this->payment->id,
                'amount_rupees' => '600.00', // Exceeds 500.00
                'amount_minor' => '',
                'reason_code' => 'customer_cancellation',
            ]);

        $response->assertRedirect(route('admin.refunds.index'));
        $response->assertSessionHasErrors(['amount_minor']);
        $this->assertEquals(0, Refund::count());
    }

    public function test_refund_index_page_loads_succeeded_payments_with_remaining_balances(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->get(route('admin.refunds.index'));

        $response->assertOk();
        $response->assertSee('ORD-UI-TEST-101');
        $response->assertSee('Request Customer Refund');
    }
}
