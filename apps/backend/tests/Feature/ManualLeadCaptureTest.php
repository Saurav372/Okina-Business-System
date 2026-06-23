<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to set up an authorized staff user.
     */
    protected function createAuthorizedStaffUser(): User
    {
        Permission::query()->updateOrCreate(
            ['slug' => 'leads.manage'],
            [
                'name' => 'Manage Leads',
                'group' => 'leads',
                'guard_name' => 'web',
                'description' => 'Manage leads',
                'is_sensitive' => false,
            ]
        );

        $role = Role::query()->updateOrCreate(
            ['slug' => 'lead_manager'],
            [
                'name' => 'Lead Manager',
                'guard_name' => 'web',
                'description' => 'Can manage leads',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $permissionIds = Permission::query()->whereIn('slug', ['leads.manage'])->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->assignRole($dashboardRole);

        return $user;
    }

    /**
     * Test that authorized staff can manually create a lead.
     */
    public function test_authorized_staff_can_manually_create_lead(): void
    {
        $user = $this->createAuthorizedStaffUser();

        $payload = [
            'source' => 'phone',
            'source_detail' => 'Inbound inquiry about printing pricing',
            'status' => 'new',
            'priority' => 'high',
            'contact_name' => 'Saurav Sen',
            'company_name' => 'Okina Craft Inc.',
            'email' => 'saurav@example.com',
            'phone' => '+919999999999',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'country_code' => 'IN',
            'interest_summary' => 'Inquiry for 500 custom prints',
            'requirements' => 'Needs express delivery options',
            'product_interest' => ['Custom T-Shirts', 'Printed Hoodies'],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.store'), $payload);

        $response->assertStatus(201);

        // Verify public-safe response structure (and no internal numeric IDs)
        $response->assertJsonStructure([
            'public_id',
            'source',
            'source_detail',
            'status',
            'priority',
            'contact_name',
            'company_name',
            'email',
            'phone',
            'city',
            'state',
            'country_code',
            'interest_summary',
            'requirements',
            'product_interest',
            'created_at',
            'updated_at',
        ]);

        $response->assertJsonMissing([
            'id',
            'created_by_user_id',
            'assigned_to_user_id',
            'customer_id',
        ]);

        // Verify the database record has been created
        $lead = Lead::query()->first();
        $this->assertNotNull($lead);
        $this->assertSame('phone', $lead->source);
        $this->assertSame('Saurav Sen', $lead->contact_name);
        $this->assertSame('Okina Craft Inc.', $lead->company_name);
        $this->assertStringStartsWith('LD-', $lead->public_id);
    }

    /**
     * Test that the creator is recorded from the authenticated staff user and response doesn't leak internal IDs.
     */
    public function test_creator_is_recorded_from_authenticated_staff_user(): void
    {
        $user = $this->createAuthorizedStaffUser();

        $payload = [
            'source' => 'manual',
            'contact_name' => 'Jane Doe',
            'phone' => '1234567890',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.store'), $payload);

        $response->assertStatus(201);

        // Ensure database record has created_by_user_id populated
        $lead = Lead::query()->first();
        $this->assertNotNull($lead);
        $this->assertSame($user->id, $lead->created_by_user_id);

        // Ensure response does not expose internal numeric database ID of user or lead
        $response->assertJsonMissing([
            'id' => $lead->id,
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Test that unauthorized staff cannot create a lead.
     */
    public function test_unauthorized_staff_cannot_create_lead(): void
    {
        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $user = User::factory()->create();
        $user->assignRole($dashboardRole);

        // Note: User has sales_staff role (dashboard access) but does NOT have leads.manage permission

        $payload = [
            'source' => 'manual',
            'contact_name' => 'Jane Doe',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.store'), $payload);

        $response->assertStatus(403);
        $this->assertSame(0, Lead::count());
    }

    /**
     * Test that unauthenticated guest cannot create a lead.
     */
    public function test_guest_cannot_create_lead(): void
    {
        $payload = [
            'source' => 'manual',
            'contact_name' => 'Jane Doe',
        ];

        // Route has 'auth' middleware, posting to it as guest should redirect or return 401
        $response = $this->postJson(route('admin.leads.store'), $payload);

        $response->assertStatus(401);
        $this->assertSame(0, Lead::count());
    }

    /**
     * Test that validation fails if required fields are missing.
     */
    public function test_validation_errors_for_missing_required_fields(): void
    {
        $user = $this->createAuthorizedStaffUser();

        // Empty payload (source is required)
        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source']);
    }

    /**
     * Test that validation fails for invalid enum values.
     */
    public function test_validation_errors_for_invalid_enums(): void
    {
        $user = $this->createAuthorizedStaffUser();

        // Invalid source
        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.store'), [
                'source' => 'invalid_source_value',
            ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source']);

        // Invalid status
        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.store'), [
                'source' => 'manual',
                'status' => 'invalid_status_value',
            ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);

        // Invalid priority
        $response = $this->actingAs($user)
            ->postJson(route('admin.leads.store'), [
                'source' => 'manual',
                'priority' => 'invalid_priority_value',
            ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }
}
