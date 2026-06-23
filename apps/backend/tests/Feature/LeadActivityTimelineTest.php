<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadActivityTimelineTest extends TestCase
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
     * Helper to set up an unauthorized staff user (has dashboard access, lacks leads.manage).
     */
    protected function createUnauthorizedStaffUser(): User
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

        return $user;
    }

    /**
     * Test that guests cannot create lead activities.
     */
    public function test_guest_cannot_create_activity(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->postJson(route('admin.leads.activities.store', $lead), [
            'activity_type' => 'note',
            'body' => 'Test note body',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test that unauthorized staff cannot create lead activities.
     */
    public function test_unauthorized_staff_cannot_create_activity(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createUnauthorizedStaffUser();

        $response = $this->actingAs($staff)
            ->postJson(route('admin.leads.activities.store', $lead), [
                'activity_type' => 'note',
                'body' => 'Test note body',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that authorized staff can create valid activity types.
     */
    public function test_authorized_staff_can_create_valid_activities(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createAuthorizedStaffUser();

        $validTypes = ['note', 'call', 'email', 'whatsapp'];

        foreach ($validTypes as $type) {
            $response = $this->actingAs($staff)
                ->postJson(route('admin.leads.activities.store', $lead), [
                    'activity_type' => $type,
                    'subject' => "Subject for {$type}",
                    'body' => "Body for {$type}",
                ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('lead_activities', [
                'lead_id' => $lead->id,
                'activity_type' => $type,
                'subject' => "Subject for {$type}",
                'body' => "Body for {$type}",
                'created_by_user_id' => $staff->id,
                'customer_visible' => false,
            ]);
        }
    }

    /**
     * Test that activity creation defaults to 'note'.
     */
    public function test_activity_creation_defaults_to_note(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createAuthorizedStaffUser();

        $response = $this->actingAs($staff)
            ->postJson(route('admin.leads.activities.store', $lead), [
                'body' => 'This is a freeform note with no explicit type',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'activity_type' => 'note',
            'body' => 'This is a freeform note with no explicit type',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'note',
            'body' => 'This is a freeform note with no explicit type',
        ]);
    }

    /**
     * Test that system-only types are rejected.
     */
    public function test_system_only_types_are_rejected(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createAuthorizedStaffUser();

        $systemTypes = ['status_change', 'assignment', 'follow_up_created', 'quotation_created'];

        foreach ($systemTypes as $type) {
            $response = $this->actingAs($staff)
                ->postJson(route('admin.leads.activities.store', $lead), [
                    'activity_type' => $type,
                    'body' => 'Trying to forge a system log',
                ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['activity_type']);
        }
    }

    /**
     * Test that created activity response is public safe and does not leak internal database IDs.
     */
    public function test_created_activity_response_is_public_safe(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createAuthorizedStaffUser();

        $response = $this->actingAs($staff)
            ->postJson(route('admin.leads.activities.store', $lead), [
                'activity_type' => 'note',
                'subject' => 'Meeting notes',
                'body' => 'Discussed pricing strategy',
            ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'activity_type' => 'note',
            'subject' => 'Meeting notes',
            'body' => 'Discussed pricing strategy',
            'customer_visible' => false,
            'created_by' => [
                'name' => $staff->name,
                'email' => $staff->email,
            ],
        ]);

        // Hide internal database keys
        $response->assertJsonMissing([
            'id',
            'lead_id',
            'created_by_user_id',
        ]);
    }

    /**
     * Test that guests cannot view timeline.
     */
    public function test_guest_cannot_view_timeline(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->getJson(route('admin.leads.activities.index', $lead));
        $response->assertStatus(401);
    }

    /**
     * Test that unauthorized staff cannot view timeline.
     */
    public function test_unauthorized_staff_cannot_view_timeline(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createUnauthorizedStaffUser();

        $response = $this->actingAs($staff)
            ->getJson(route('admin.leads.activities.index', $lead));

        $response->assertStatus(403);
    }

    /**
     * Test that authorized staff can view timeline in ascending chronological order of occurred_at.
     */
    public function test_authorized_staff_can_view_timeline_in_chronological_order(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createAuthorizedStaffUser();

        // 1. Create a status change activity at occurred_at = 2 hours ago
        $activity1 = LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => 'status_change',
            'subject' => 'Status: new -> contacted',
            'metadata' => ['from' => 'new', 'to' => 'contacted'],
            'customer_visible' => false,
            'created_by_user_id' => $staff->id,
            'occurred_at' => now()->subHours(2),
        ]);

        // 2. Create an assignment activity at occurred_at = 1 hour ago
        $activity2 = LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => 'assignment',
            'subject' => 'Assigned to Agent',
            'metadata' => ['to' => $staff->id],
            'customer_visible' => false,
            'created_by_user_id' => $staff->id,
            'occurred_at' => now()->subHours(1),
        ]);

        // 3. Create a manual note at occurred_at = now
        $activity3 = LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => 'note',
            'body' => 'Spoke to customer on phone',
            'customer_visible' => false,
            'created_by_user_id' => $staff->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($staff)
            ->getJson(route('admin.leads.activities.index', $lead));

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(3, $data);

        // Verify ordering: activity1 -> activity2 -> activity3
        $this->assertSame('status_change', $data[0]['activity_type']);
        $this->assertSame('assignment', $data[1]['activity_type']);
        $this->assertSame('note', $data[2]['activity_type']);

        // Verify public safe structure
        foreach ($data as $item) {
            $this->assertArrayNotHasKey('id', $item);
            $this->assertArrayNotHasKey('lead_id', $item);
            $this->assertArrayNotHasKey('created_by_user_id', $item);
            $this->assertArrayHasKey('activity_type', $item);
            $this->assertArrayHasKey('customer_visible', $item);
            $this->assertArrayHasKey('occurred_at', $item);
            $this->assertArrayHasKey('created_by', $item);
        }
    }

    /**
     * Test that no customer-facing API exposes lead activities.
     */
    public function test_no_customer_api_exposes_lead_activities(): void
    {
        $lead = Lead::factory()->create();

        // Standard non-existent routes for activities in customer namespace
        $response1 = $this->getJson("/api/customer/leads/{$lead->public_id}/activities");
        $response1->assertStatus(404);

        $response2 = $this->getJson("/api/leads/{$lead->public_id}/activities");
        $response2->assertStatus(404);
    }
}
