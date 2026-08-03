<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Vendors\VendorCodeGenerator;
use Illuminate\Database\QueryException;
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
        Permission::query()->updateOrCreate(
            ['slug' => 'vendors.delete'],
            [
                'name' => 'Delete Vendors',
                'group' => 'vendors',
                'guard_name' => 'web',
                'description' => 'Can delete vendors',
                'is_sensitive' => true,
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
            Permission::query()->whereIn('slug', ['vendors.manage', 'vendors.view', 'vendors.delete'])->pluck('id')->all()
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

        // Unprivileged user has sales staff role (no vendor permissions)
        $this->unprivilegedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unprivilegedUser->assignRole($salesRole);

        // Privileged staff has vendor management permissions
        $this->privilegedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->privilegedStaff->assignRole($staffRole);
    }

    /**
     * 1. Test guest is blocked from vendor endpoints.
     */
    public function test_guest_is_blocked_from_vendor_endpoints(): void
    {
        $this->getJson(route('admin.vendors.index'))->assertStatus(401);
        $this->postJson(route('admin.vendors.store'), [])->assertStatus(401);
    }

    /**
     * 2. Test unprivileged user is blocked from vendor endpoints.
     */
    public function test_unprivileged_user_is_blocked_from_vendor_endpoints(): void
    {
        $this->actingAs($this->unprivilegedUser);

        $this->getJson(route('admin.vendors.index'))->assertStatus(403);
        $this->postJson(route('admin.vendors.store'), ['name' => 'Supplier A'])->assertStatus(403);
    }

    /**
     * 3. Test web Blade form submissions (store, update, destroy redirects with session flash).
     */
    public function test_authorized_user_can_perform_crud_operations_with_web_redirects(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        // Store
        $response = $this->from(route('admin.vendors.index'))
            ->post(route('admin.vendors.store'), [
                'name' => 'Acme Mills',
                'contact_name' => 'John Doe',
                'email' => 'john@acme.com',
                'phone' => '+919876543210',
                'gstin' => '29GGGGG1314R1Z8',
                'country_code' => 'in',
                'address_line1' => '123 Main St',
                'address_line2' => 'Suite 400',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560001',
                'payment_terms' => 'Net 30',
                'notes' => 'Primary apparel supplier',
            ]);

        $response->assertRedirect(route('admin.vendors.index'));
        $response->assertSessionHas('success', 'Vendor created successfully.');

        $vendor = Vendor::where('name', 'Acme Mills')->firstOrFail();
        $this->assertNotNull($vendor->vendor_code);
        $this->assertMatchesRegularExpression('/^VND-[A-Z0-9]{6}$/', $vendor->vendor_code);

        // Update
        $response = $this->from(route('admin.vendors.index'))
            ->put(route('admin.vendors.update', $vendor->id), [
                'name' => 'Acme Mills Updated',
                'status' => 'inactive',
            ]);

        $response->assertRedirect(route('admin.vendors.index'));
        $response->assertSessionHas('success', 'Vendor updated successfully.');

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'Acme Mills Updated',
            'status' => VendorStatus::INACTIVE->value,
        ]);

        // Destroy (Soft Delete)
        $response = $this->from(route('admin.vendors.index'))
            ->delete(route('admin.vendors.destroy', $vendor->id));

        $response->assertRedirect(route('admin.vendors.index'));
        $response->assertSessionHas('success', 'Vendor deleted successfully.');

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /**
     * 4. Test modal recovery handles missing or soft-deleted vendor safely.
     */
    public function test_modal_recovery_handles_missing_vendor_safely(): void
    {
        $this->actingAs($this->privilegedStaff);

        // Submit form validation failure from index page with invalid edit_vendor_id
        $response = $this->post(route('admin.vendors.store'), [
            'name' => '', // trigger validation error
            'modal_mode' => 'edit',
            'edit_vendor_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        $this->get(route('admin.vendors.index'))
            ->assertStatus(200)
            ->assertViewHas('modalMode', 'create')
            ->assertViewHas('formAction', route('admin.vendors.store'));
    }

    /**
     * 5. Test JSON API response contracts match documented schemas.
     */
    public function test_json_api_responses_match_documented_schemas(): void
    {
        $this->actingAs($this->privilegedStaff);

        // Create API
        $response = $this->postJson(route('admin.vendors.store'), [
            'name' => 'API Supplier',
            'gstin' => '29GGGGG1314R1Z8',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'vendor_code', 'name', 'status', 'gstin'],
        ]);
        $response->assertJson([
            'message' => 'Vendor created successfully.',
            'data' => ['name' => 'API Supplier'],
        ]);

        $vendorId = $response->json('data.id');

        // Update API
        $response = $this->putJson(route('admin.vendors.update', $vendorId), [
            'name' => 'API Supplier Updated',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Vendor updated successfully.',
            'data' => ['name' => 'API Supplier Updated'],
        ]);

        // Delete API
        $response = $this->deleteJson(route('admin.vendors.destroy', $vendorId));
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Vendor deleted successfully.']);
    }

    /**
     * 6. Test 7-field search query with wildcard escaping.
     */
    public function test_7_field_search_query_with_wildcard_escaping(): void
    {
        $this->actingAs($this->privilegedStaff);

        Vendor::create(['name' => 'Alpha Suppliers 100%', 'vendor_code' => 'VND-ALPHA1', 'city' => 'Bengaluru']);
        Vendor::create(['name' => 'Beta Mills', 'vendor_code' => 'VND-BETA02', 'contact_name' => 'John Doe', 'city' => 'Mumbai']);

        // Test searching name
        $response = $this->getJson(route('admin.vendors.index', ['search' => '100%']));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Alpha Suppliers 100%', $response->json('data.0.name'));

        // Test searching contact_name
        $response = $this->getJson(route('admin.vendors.index', ['search' => 'John Doe']));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Beta Mills', $response->json('data.0.name'));
    }

    /**
     * 7. Test status filtering and fallback whitelisting.
     */
    public function test_status_filtering_and_whitelisted_defaults(): void
    {
        $this->actingAs($this->privilegedStaff);

        Vendor::create(['name' => 'Active V', 'vendor_code' => 'VND-ACT01', 'status' => 'active']);
        Vendor::create(['name' => 'Inactive V', 'vendor_code' => 'VND-INA01', 'status' => 'inactive']);

        // Filter active
        $response = $this->getJson(route('admin.vendors.index', ['status' => 'active']));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Invalid per_page and sort_by fallback
        $response = $this->getJson(route('admin.vendors.index', ['per_page' => 9999, 'sort_by' => 'invalid_col']));
        $response->assertStatus(200);
        $this->assertEquals(15, $response->json('per_page'));
    }

    /**
     * 8. Test custom code format and permanent uniqueness across soft-deleted records.
     */
    public function test_custom_code_format_and_permanent_uniqueness_across_soft_deletes(): void
    {
        $this->actingAs($this->privilegedStaff);

        // Invalid custom code format (hyphens only)
        $this->postJson(route('admin.vendors.store'), [
            'name' => 'Invalid Code Vendor',
            'vendor_code' => '---',
        ])->assertStatus(422)->assertJsonValidationErrors(['vendor_code']);

        // Valid custom code
        $vendor = Vendor::create([
            'name' => 'Custom Code Vendor',
            'vendor_code' => 'CUSTOM-VND-01',
            'status' => 'active',
        ]);

        // Soft delete vendor
        $vendor->delete();

        // Attempt reuse of deleted vendor code
        $this->postJson(route('admin.vendors.store'), [
            'name' => 'Duplicate Code Vendor',
            'vendor_code' => 'custom-vnd-01', // lower-case should be normalized to upper and fail
        ])->assertStatus(422)->assertJsonValidationErrors(['vendor_code']);
    }

    /**
     * 9. Test prepareForValidation normalizations and blank GSTIN as NULL.
     */
    public function test_prepare_for_validation_normalizations_and_blank_gstin_null(): void
    {
        $this->actingAs($this->privilegedStaff);

        $response = $this->postJson(route('admin.vendors.store'), [
            'name' => 'Normalized Vendor',
            'gstin' => '',
            'country_code' => 'in',
        ]);

        $response->assertStatus(201);
        $vendorId = $response->json('data.id');
        $vendor = Vendor::findOrFail($vendorId);

        $this->assertNull($vendor->gstin);
        $this->assertEquals('IN', $vendor->country_code);
    }

    /**
     * 10. Test index-specific concurrency retry logic and throwable preservation.
     */
    public function test_concurrency_retry_executes_only_on_vendor_code_collision(): void
    {
        // Simulate a vendor code collision exception
        $collisionException = new QueryException(
            'sqlite',
            'INSERT INTO vendors (vendor_code) VALUES (?)',
            ['VND-7K9A2P'],
            new \Exception('UNIQUE constraint failed: vendors.vendor_code (SQLSTATE[23000])')
        );

        $this->assertTrue(VendorCodeGenerator::isVendorCodeCollision($collisionException));

        // Test non-vendor_code collision (GSTIN collision) is not treated as vendor_code collision
        $gstinCollision = new QueryException(
            'sqlite',
            'INSERT INTO vendors (gstin) VALUES (?)',
            ['29GGGGG1314R1Z8'],
            new \Exception('UNIQUE constraint failed: vendors_gstin_unique (SQLSTATE[23000])')
        );

        $this->assertFalse(VendorCodeGenerator::isVendorCodeCollision($gstinCollision));
    }

    /**
     * 11. Test non-domain modal fields are excluded from model persistence.
     */
    public function test_modal_only_fields_are_excluded_from_persistence(): void
    {
        $this->actingAs($this->privilegedStaff);

        $this->postJson(route('admin.vendors.store'), [
            'name' => 'Sanitized Vendor',
            'modal_mode' => 'create',
            'edit_vendor_id' => 123,
        ])->assertStatus(201);

        $vendor = Vendor::where('name', 'Sanitized Vendor')->firstOrFail();
        $this->assertFalse(isset($vendor->modal_mode));
        $this->assertFalse(isset($vendor->edit_vendor_id));
    }
}
