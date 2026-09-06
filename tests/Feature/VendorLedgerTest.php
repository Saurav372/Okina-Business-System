<?php

namespace Tests\Feature;

use App\Enums\VendorOrderStatus;
use App\Enums\VendorPaymentMethod;
use App\Enums\VendorPaymentStatus;
use App\Enums\VendorStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $permLedger = Permission::query()->firstOrCreate(['slug' => 'finance.view_ledgers'], [
            'name' => 'View Ledgers',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'View Ledgers',
            'is_sensitive' => false,
        ]);

        $permDashboard = Permission::query()->firstOrCreate(['slug' => 'dashboard.access'], [
            'name' => 'Dashboard Access',
            'group' => 'settings',
            'guard_name' => 'web',
            'description' => 'Dashboard Access',
            'is_sensitive' => false,
        ]);

        $role = Role::query()->firstOrCreate(['slug' => Role::FINANCE_STAFF], [
            'name' => 'Finance Staff',
            'guard_name' => 'web',
            'description' => 'Finance Staff Role',
            'is_system' => true,
            'sort_order' => 1,
        ]);

        $role->permissions()->sync([$permLedger->id, $permDashboard->id]);

        $this->financeUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->financeUser->roles()->sync([$role->id]);

        $this->vendor = Vendor::create([
            'vendor_code' => 'VND-TEX-001',
            'name' => 'Textile Fabrics Ltd',
            'status' => VendorStatus::ACTIVE,
            'email' => 'sales@textilefabrics.example',
            'phone' => '+91 9876543210',
            'created_by_user_id' => $this->financeUser->id,
            'updated_by_user_id' => $this->financeUser->id,
        ]);
    }

    public function test_vendor_payment_purchase_order_alias_relationship(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->vendor->id,
            'public_id' => 'PO-TEST-001',
            'status' => VendorOrderStatus::ORDERED,
            'subtotal_amount_minor' => 500000,
            'total_amount_minor' => 500000,
            'currency' => 'INR',
            'created_by_user_id' => $this->financeUser->id,
            'updated_by_user_id' => $this->financeUser->id,
        ]);

        $payment = VendorPayment::create([
            'vendor_order_id' => $po->id,
            'status' => VendorPaymentStatus::PAID,
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER,
            'amount_minor' => 300000,
            'currency' => 'INR',
            'reference' => 'TXN-998877',
            'paid_at' => now(),
            'recorded_by_user_id' => $this->financeUser->id,
        ]);

        $this->assertNotNull($payment->purchaseOrder);
        $this->assertEquals($po->id, $payment->purchaseOrder->id);
        $this->assertEquals($po->id, $payment->vendorOrder->id);
    }

    public function test_vendor_ledger_page_loads_and_calculates_balances(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->vendor->id,
            'public_id' => 'PO-TEST-002',
            'status' => VendorOrderStatus::ORDERED,
            'subtotal_amount_minor' => 1000000,
            'total_amount_minor' => 1000000,
            'currency' => 'INR',
            'created_by_user_id' => $this->financeUser->id,
            'updated_by_user_id' => $this->financeUser->id,
        ]);

        VendorPayment::create([
            'vendor_order_id' => $po->id,
            'status' => VendorPaymentStatus::PAID,
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER,
            'amount_minor' => 400000,
            'currency' => 'INR',
            'reference' => 'TXN-445566',
            'paid_at' => now(),
            'recorded_by_user_id' => $this->financeUser->id,
        ]);

        $response = $this->actingAs($this->financeUser)
            ->get(route('admin.accounting.vendor_ledger'));

        $response->assertOk();
        $response->assertSee('Textile Fabrics Ltd');
        $response->assertSee('VND-TEX-001');
        // Total PO: 10000.00, Paid: 4000.00, Outstanding: 6000.00
        $response->assertSee('10,000.00');
        $response->assertSee('4,000.00');
        $response->assertSee('6,000.00');
    }
}
