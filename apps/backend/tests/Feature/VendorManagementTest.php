<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $unprivilegedUser;

    private User $privilegedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::query()->updateOrCreate(
            ['slug' => 'vendors.manage'],
            [
                'name' => 'Manage Vendors',
                'group' => 'vendors',
                'guard_name' => 'web',
                'description' => 'Can manage vendors',
                'is_sensitive' => true,
            ]
        );
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

        // Create roles
        $adminRole = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'description' => 'Admin role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
        $adminRole->permissions()->sync(
            Permission::query()->pluck('id')->all()
        );

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
            Permission::query()->whereIn('slug', ['vendors.manage', 'vendors.view'])->pluck('id')->all()
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

        // Admin user has admin role
        $this->adminUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($adminRole);

        // Unprivileged user has no permissions or roles
        $this->unprivilegedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unprivilegedUser->assignRole($salesRole);

        // Privileged staff has vendors.manage permission/role
        $this->privilegedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->privilegedStaff->assignRole($staffRole);
    }

    /**
     * Test guest is blocked.
     */
    public function test_guest_is_blocked_from_vendor_endpoints(): void
    {
        $this->getJson(route('admin.vendors.index'))->assertStatus(401);
        $this->postJson(route('admin.vendors.store'), [])->assertStatus(401);
    }

    /**
     * Test unprivileged user is blocked.
     */
    public function test_unprivileged_user_is_blocked_from_vendor_endpoints(): void
    {
        $this->actingAs($this->unprivilegedUser);

        $this->getJson(route('admin.vendors.index'))->assertStatus(403);
        $this->postJson(route('admin.vendors.store'), ['name' => 'Supplier A'])->assertStatus(403);
    }

    /**
     * Test CRUD operations for authorized users.
     */
    public function test_authorized_user_can_perform_crud_operations(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        // 1. Create (Store)
        $response = $this->postJson(route('admin.vendors.store'), [
            'name' => 'Supplier A',
            'email' => 'supplier@example.com',
            'phone' => '+919999999999',
            'gstin' => '29GGGGG1314R1Z8', // valid GSTIN pattern
            'country_code' => 'in', // should be normalized to IN
        ])->assertStatus(201);

        $vendorId = $response->json('id');
        $vendorCode = $response->json('vendor_code');

        $this->assertNotNull($vendorCode);
        $this->assertStringStartsWith('VND-', $vendorCode);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendorId,
            'name' => 'Supplier A',
            'email' => 'supplier@example.com',
            'phone' => '+919999999999',
            'gstin' => '29GGGGG1314R1Z8',
            'country_code' => 'IN',
            'status' => VendorStatus::ACTIVE->value,
            'created_by_user_id' => $this->privilegedStaff->id,
        ]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($vendorId, $vendorCode) {
            return $event->key === 'vendors.created'
                && $event->payload['vendor_id'] === $vendorId
                && $event->payload['vendor_code'] === $vendorCode
                && $event->payload['name'] === 'Supplier A';
        });

        // 2. Index (List)
        $response = $this->getJson(route('admin.vendors.index'))->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Search index
        $response = $this->getJson(route('admin.vendors.index', ['search' => 'Supplier A']))->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson(route('admin.vendors.index', ['search' => 'Unknown']))->assertStatus(200);
        $this->assertCount(0, $response->json('data'));

        // 3. Show
        $this->getJson(route('admin.vendors.show', $vendorId))
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Supplier A',
                'vendor_code' => $vendorCode,
            ]);

        // 4. Update
        $this->putJson(route('admin.vendors.update', $vendorId), [
            'name' => 'Supplier A Updated',
            'status' => 'inactive',
        ])->assertStatus(200);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendorId,
            'name' => 'Supplier A Updated',
            'status' => VendorStatus::INACTIVE->value,
            'updated_by_user_id' => $this->privilegedStaff->id,
        ]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($vendorId, $vendorCode) {
            return $event->key === 'vendors.updated'
                && $event->payload['vendor_id'] === $vendorId
                && $event->payload['vendor_code'] === $vendorCode
                && $event->payload['name'] === 'Supplier A Updated';
        });

        // 5. Delete (Soft Delete)
        $this->deleteJson(route('admin.vendors.destroy', $vendorId))->assertStatus(200);

        $this->assertSoftDeleted('vendors', [
            'id' => $vendorId,
        ]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($vendorId, $vendorCode) {
            return $event->key === 'vendors.deleted'
                && $event->payload['vendor_id'] === $vendorId
                && $event->payload['vendor_code'] === $vendorCode;
        });
    }

    /**
     * Test active scope.
     */
    public function test_active_scope_only_returns_active_status_vendors(): void
    {
        Vendor::create([
            'name' => 'Active Vendor',
            'vendor_code' => 'VND-ACTIVE1',
            'status' => VendorStatus::ACTIVE->value,
        ]);

        Vendor::create([
            'name' => 'Inactive Vendor',
            'vendor_code' => 'VND-INACTIVE',
            'status' => VendorStatus::INACTIVE->value,
        ]);

        $activeVendors = Vendor::query()->active()->get();

        $this->assertCount(1, $activeVendors);
        $this->assertEquals('Active Vendor', $activeVendors->first()->name);
    }

    /**
     * Test validation constraints and normalizations.
     */
    public function test_validation_constraints(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Invalid GSTIN format
        $this->postJson(route('admin.vendors.store'), [
            'name' => 'Supplier',
            'gstin' => 'INVALID-GSTIN',
        ])->assertStatus(422)->assertJsonValidationErrors(['gstin']);

        // 2. Custom vendor code uniqueness (permanent, even across soft deleted)
        $vendor = Vendor::create([
            'name' => 'First Vendor',
            'vendor_code' => 'VND-UNIQUE99',
            'status' => VendorStatus::ACTIVE->value,
        ]);

        // Soft delete first vendor
        $vendor->delete();

        // Create new vendor with same code (should fail due to unique constraint, even though soft deleted)
        $this->postJson(route('admin.vendors.store'), [
            'name' => 'Second Vendor',
            'vendor_code' => 'VND-UNIQUE99',
        ])->assertStatus(422)->assertJsonValidationErrors(['vendor_code']);
    }
}
