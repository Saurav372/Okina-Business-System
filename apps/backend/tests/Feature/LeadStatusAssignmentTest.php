<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadStatusAssignmentTest extends TestCase
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
     * Test that guests and unauthorized staff cannot update leads.
     */
    public function test_unauthorized_user_cannot_update_lead(): void
    {
        $lead = Lead::factory()->create(['status' => 'new']);

        // 1. Guest request gets 401
        $response = $this->patchJson(route('admin.leads.update', $lead), [
            'status' => 'assigned',
        ]);
        $response->assertStatus(401);

        // 2. Unauthorized staff gets 403
        $staff = $this->createUnauthorizedStaffUser();
        $response = $this->actingAs($staff)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'assigned',
            ]);
        $response->assertStatus(403);
    }

    /**
     * Test that authorized user can update status and assignee.
     */
    public function test_authorized_user_can_update_status_and_assignee(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new', 'assigned_to_user_id' => null]);
        $otherStaff = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'assigned',
                'assigned_to_user_id' => $otherStaff->id,
            ]);

        $response->assertStatus(200);

        // Verify status and assignment were updated in the database
        $lead->refresh();
        $this->assertSame('assigned', $lead->status);
        $this->assertSame($otherStaff->id, $lead->assigned_to_user_id);
    }

    /**
     * Test that invalid status transitions are rejected with HTTP 422.
     */
    public function test_invalid_status_transitions_are_rejected(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        // Try direct jump from 'new' to 'won'
        $response = $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'won',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);

        // Assert lead status did not change
        $lead->refresh();
        $this->assertSame('new', $lead->status);
    }

    /**
     * Test that reopening a lost or spam lead moves it to 'new' and clears lost_reason.
     */
    public function test_reopening_lost_or_spam_lead_moves_it_to_new_and_clears_lost_reason(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lostLead = Lead::factory()->create([
            'status' => 'lost',
            'lost_reason' => 'Price was too high',
            'lost_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lostLead), [
                'status' => 'new',
            ]);

        $response->assertStatus(200);

        $lostLead->refresh();
        $this->assertSame('new', $lostLead->status);
        $this->assertNull($lostLead->lost_reason);
        $this->assertNull($lostLead->lost_at);

        $spamLead = Lead::factory()->create([
            'status' => 'spam',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $spamLead), [
                'status' => 'new',
            ]);

        $response->assertStatus(200);

        $spamLead->refresh();
        $this->assertSame('new', $spamLead->status);
    }

    /**
     * Test that lost_reason is required when marking a lead as lost.
     */
    public function test_lost_reason_required_when_marking_lead_lost(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        // Try marking as lost without lost_reason
        $response = $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'lost',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lost_reason']);

        // Perform valid lost transition with reason
        $response = $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'lost',
                'lost_reason' => 'Duplicate lead',
            ]);

        $response->assertStatus(200);

        $lead->refresh();
        $this->assertSame('lost', $lead->status);
        $this->assertSame('Duplicate lead', $lead->lost_reason);
        $this->assertNotNull($lead->lost_at);
    }

    /**
     * Test update response is public safe and does not expose internal IDs.
     */
    public function test_update_response_is_public_safe(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        $response = $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'assigned',
                'assigned_to_user_id' => $user->id,
            ]);

        $response->assertStatus(200);

        // Expose CRM fields
        $response->assertJsonFragment([
            'public_id' => $lead->public_id,
            'status' => 'assigned',
            'assigned_to_user_id' => $user->id,
        ]);

        // Hide internal database keys
        $response->assertJsonMissing([
            'id',
            'customer_id',
            'created_by_user_id',
        ]);
    }

    // ------------------------------------------------------------------ activity logging

    /**
     * Test that a status_change activity entry is created when status changes.
     */
    public function test_status_change_creates_lead_activity(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'contacted',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'status_change',
            'created_by_user_id' => $user->id,
        ]);

        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', 'status_change')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('new', $activity->metadata['from_status']);
        $this->assertSame('contacted', $activity->metadata['to_status']);
    }

    /**
     * Test that an assignment activity entry is created when assigned_to_user_id changes.
     */
    public function test_assignment_change_creates_lead_activity(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new', 'assigned_to_user_id' => null]);
        $newAssignee = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'assigned_to_user_id' => $newAssignee->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'assignment',
            'created_by_user_id' => $user->id,
        ]);

        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', 'assignment')
            ->first();

        $this->assertNotNull($activity);
        $this->assertNull($activity->metadata['previous_assigned_to_user_id']);
        $this->assertSame($newAssignee->id, $activity->metadata['new_assigned_to_user_id']);
    }

    /**
     * Test that two activities are created when both status and assignment change at once.
     */
    public function test_simultaneous_status_and_assignment_change_creates_two_activities(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new', 'assigned_to_user_id' => null]);
        $assignee = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'assigned',
                'assigned_to_user_id' => $assignee->id,
            ])
            ->assertStatus(200);

        $this->assertSame(2, LeadActivity::where('lead_id', $lead->id)->count());
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'activity_type' => 'status_change']);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'activity_type' => 'assignment']);
    }

    /**
     * Test that no activity is created when no tracked field changes.
     */
    public function test_no_activity_created_when_no_tracked_field_changes(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        // Patch a non-tracked field like contact_name
        $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'new', // same status
            ])
            ->assertStatus(200);

        $this->assertSame(0, LeadActivity::where('lead_id', $lead->id)->count());
    }

    /**
     * Test that activity metadata is safe and does not expose sensitive data.
     */
    public function test_activity_metadata_is_safe(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'contacted',
            ])
            ->assertStatus(200);

        $activity = LeadActivity::where('lead_id', $lead->id)->first();

        $this->assertNotNull($activity->metadata);
        $this->assertArrayHasKey('from_status', $activity->metadata);
        $this->assertArrayHasKey('to_status', $activity->metadata);
        // Must not contain user secrets or raw internal IDs beyond assignment tracking
        $this->assertArrayNotHasKey('password', $activity->metadata);
        $this->assertArrayNotHasKey('remember_token', $activity->metadata);
    }

    /**
     * Test that invalid status transition does NOT create an activity entry.
     */
    public function test_invalid_transition_does_not_create_activity(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        $this->actingAs($user)
            ->patchJson(route('admin.leads.update', $lead), [
                'status' => 'won', // invalid from 'new'
            ])
            ->assertStatus(422);

        $this->assertSame(0, LeadActivity::where('lead_id', $lead->id)->count());
    }
}
