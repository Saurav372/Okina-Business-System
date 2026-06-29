<?php

namespace Tests\Feature;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $privilegedStaff;

    private User $unprivilegedUser;

    private Vendor $vendorA;

    private Vendor $vendorB;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // Create permissions
        Permission::query()->updateOrCreate(
            ['slug' => 'vendors.view'],
            [
                'name' => 'View Vendors',
                'group' => 'vendors',
                'guard_name' => 'web',
                'description' => 'Can view vendors',
                'is_sensitive' => false,
            ]
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'purchases.view'],
            [
                'name' => 'View Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can view purchases',
                'is_sensitive' => false,
            ]
        );

        // Create roles
        $staffRole = Role::query()->updateOrCreate(
            ['slug' => Role::INVENTORY_STAFF],
            [
                'name' => 'Inventory Staff',
                'guard_name' => 'web',
                'description' => 'Inventory staff role',
                'is_system' => true,
                'sort_order' => 1,
            ]
        );
        $staffRole->permissions()->sync(
            Permission::query()->whereIn('slug', ['vendors.view', 'purchases.view'])->pluck('id')->all()
        );

        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 2,
            ]
        );
        // Sales role has no permissions here

        // Create users
        $this->privilegedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->privilegedStaff->assignRole($staffRole);

        $this->unprivilegedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unprivilegedUser->assignRole($salesRole);

        // Create vendors
        $this->vendorA = Vendor::create([
            'name' => 'Vendor Alpha',
            'vendor_code' => 'VND-ALPHA',
            'status' => VendorStatus::ACTIVE->value,
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Vendor Beta',
            'vendor_code' => 'VND-BETA',
            'status' => VendorStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test gated access: only vendors.view permission allows viewing history.
     */
    public function test_authorization_gating(): void
    {
        // Guest gets 401
        $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id))
            ->assertStatus(401);

        // Unprivileged staff gets 403
        $this->actingAs($this->unprivilegedUser)
            ->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id))
            ->assertStatus(403);

        // Privileged staff gets 200
        $this->actingAs($this->privilegedStaff)
            ->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id))
            ->assertStatus(200);
    }

    /**
     * Test vendor isolation on history endpoint and global filtering.
     */
    public function test_vendor_isolation(): void
    {
        $this->actingAs($this->privilegedStaff);

        // Create POs for Vendor A
        $poA1 = VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-A1',
            'status' => VendorOrderStatus::ORDERED->value,
            'total_amount_minor' => 1000,
        ]);

        $poA2 = VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-A2',
            'status' => VendorOrderStatus::DRAFT->value,
            'total_amount_minor' => 2000,
        ]);

        // Create PO for Vendor B
        $poB1 = VendorOrder::create([
            'vendor_id' => $this->vendorB->id,
            'public_id' => 'PO-B1',
            'status' => VendorOrderStatus::ORDERED->value,
            'total_amount_minor' => 3000,
        ]);

        // 1. Assert Vendor A's history returns only Vendor A's orders
        $response = $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id))
            ->assertStatus(200);

        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['public_id' => 'PO-A1']);
        $response->assertJsonFragment(['public_id' => 'PO-A2']);
        $response->assertJsonMissing(['public_id' => 'PO-B1']);

        // 2. Assert ignored vendor_id query parameter on Vendor A's endpoint doesn't affect results
        $responseIgnored = $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id).'?vendor_id='.$this->vendorB->id)
            ->assertStatus(200);

        $responseIgnored->assertJsonCount(2, 'data');
        $responseIgnored->assertJsonFragment(['public_id' => 'PO-A1']);
        $responseIgnored->assertJsonFragment(['public_id' => 'PO-A2']);

        // 3. Assert global index filtering by vendor_id works
        $responseGlobal = $this->getJson(route('admin.purchase_orders.index').'?vendor_id='.$this->vendorB->id)
            ->assertStatus(200);

        $responseGlobal->assertJsonCount(1, 'data');
        $responseGlobal->assertJsonFragment(['public_id' => 'PO-B1']);
        $responseGlobal->assertJsonMissing(['public_id' => 'PO-A1']);
    }

    /**
     * Test filtering parity on the history endpoint.
     */
    public function test_filtering_and_searching(): void
    {
        $this->actingAs($this->privilegedStaff);

        VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-A1',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'notes' => 'Urgent replenishment',
        ]);

        VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-A2',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::PAID->value,
            'notes' => 'Stock refill',
        ]);

        VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-A3',
            'status' => VendorOrderStatus::CANCELLED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'notes' => 'Broken supply notes',
        ]);

        // Filter by status
        $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id).'?status=ordered')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['public_id' => 'PO-A1']);

        // Filter by payment_status
        $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id).'?payment_status=paid')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['public_id' => 'PO-A2']);

        // Search
        $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id).'?search=refill')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['public_id' => 'PO-A2']);

        // Combined Filters
        $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id).'?status=cancelled&payment_status=unpaid&search=Broken')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['public_id' => 'PO-A3']);
    }

    /**
     * Test bounded pagination.
     */
    public function test_bounded_pagination(): void
    {
        $this->actingAs($this->privilegedStaff);

        // Create 18 POs for Vendor A
        for ($i = 1; $i <= 18; $i++) {
            VendorOrder::create([
                'vendor_id' => $this->vendorA->id,
                'public_id' => "PO-PAG-{$i}",
                'status' => VendorOrderStatus::ORDERED->value,
                'total_amount_minor' => 1000,
            ]);
        }

        // 1. Default per_page=15 (assert total 18, 15 returned, pagination metadata present)
        $response = $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id))
            ->assertStatus(200);

        $response->assertJsonPath('total', 18);
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('per_page', 15);
        $response->assertJsonPath('last_page', 2);

        // Fetch page 2
        $responsePage2 = $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id).'?page=2')
            ->assertStatus(200);
        $responsePage2->assertJsonCount(3, 'data');

        // 2. per_page capped at 100
        $responseCapped = $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id).'?per_page=150')
            ->assertStatus(200);
        $responseCapped->assertJsonPath('per_page', 100);
    }

    /**
     * Test deterministic ordering (ID descending, newest first).
     */
    public function test_deterministic_descending_ordering(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po1 = VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-ORDER-1',
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $po2 = VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-ORDER-2',
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $po3 = VendorOrder::create([
            'vendor_id' => $this->vendorA->id,
            'public_id' => 'PO-ORDER-3',
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $response = $this->getJson(route('admin.vendors.purchase_orders.index', $this->vendorA->id))
            ->assertStatus(200);

        // Newest (PO-ORDER-3) should be index 0, followed by PO-ORDER-2 and PO-ORDER-1
        $data = $response->json('data');
        $this->assertEquals('PO-ORDER-3', $data[0]['public_id']);
        $this->assertEquals('PO-ORDER-2', $data[1]['public_id']);
        $this->assertEquals('PO-ORDER-1', $data[2]['public_id']);
    }
}
