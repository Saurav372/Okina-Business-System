<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadFollowUpTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // Setup permissions
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
        $role->permissions()->sync(Permission::query()->whereIn('slug', ['leads.manage'])->pluck('id')->all());

        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
        $this->user->assignRole($salesRole);

        $this->lead = Lead::factory()->create();
    }

    /**
     * Test creating a follow-up records lead activity on timeline.
     */
    public function test_creating_follow_up_records_timeline_activity(): void
    {
        $dueAt = now()->addDays(2)->roundSecond();

        $response = $this->actingAs($this->user)
            ->postJson("/admin/leads/{$this->lead->public_id}/follow-ups", [
                'due_at' => $dueAt->toIso8601String(),
                'subject' => 'Follow up subject',
                'notes' => 'Some notes here',
            ]);

        $response->assertCreated();
        $followUpId = $response->json('id');

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->lead->id,
            'activity_type' => 'follow_up_created',
            'created_by_user_id' => $this->user->id,
        ]);

        $activity = LeadActivity::where('lead_id', $this->lead->id)
            ->where('activity_type', 'follow_up_created')
            ->firstOrFail();

        $this->assertEquals($followUpId, $activity->metadata['follow_up_id']);
        $this->assertEquals($dueAt->toIso8601String(), $activity->metadata['due_at']);
        $this->assertEquals('Follow up subject', $activity->metadata['subject']);

        // Verify it returns in the timeline activities endpoint
        $this->actingAs($this->user)
            ->getJson("/admin/leads/{$this->lead->public_id}/activities")
            ->assertOk()
            ->assertJsonFragment([
                'activity_type' => 'follow_up_created',
                'subject' => 'Follow-up task created',
            ]);
    }

    /**
     * Test rescheduling a follow-up records rescheduled activity.
     */
    public function test_rescheduling_follow_up_records_rescheduled_activity(): void
    {
        $initialDue = now()->addDay()->roundSecond();
        $newDue = now()->addDays(3)->roundSecond();

        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => $initialDue,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/admin/leads/{$this->lead->public_id}/follow-ups/{$followUp->id}", [
                'due_at' => $newDue->toIso8601String(),
                'subject' => 'Changed subject',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->lead->id,
            'activity_type' => 'follow_up_rescheduled',
            'created_by_user_id' => $this->user->id,
        ]);

        $activity = LeadActivity::where('lead_id', $this->lead->id)
            ->where('activity_type', 'follow_up_rescheduled')
            ->firstOrFail();

        $this->assertEquals($followUp->id, $activity->metadata['follow_up_id']);
        $this->assertEquals($initialDue->toIso8601String(), $activity->metadata['previous_due_at']);
        $this->assertEquals($newDue->toIso8601String(), $activity->metadata['new_due_at']);
    }

    /**
     * Test updating follow-up other fields without changing due_at does not log reschedule activity.
     */
    public function test_updating_without_rescheduling_does_not_log_activity(): void
    {
        $dueAt = now()->addDay()->roundSecond();

        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => $dueAt,
            'subject' => 'Original Subject',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/admin/leads/{$this->lead->public_id}/follow-ups/{$followUp->id}", [
                'due_at' => $dueAt->toIso8601String(),
                'subject' => 'Updated Subject Only',
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('lead_activities', [
            'lead_id' => $this->lead->id,
            'activity_type' => 'follow_up_rescheduled',
        ]);
    }

    /**
     * Test completing a follow-up logs completed activity.
     */
    public function test_completing_follow_up_logs_completed_activity(): void
    {
        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/admin/leads/{$this->lead->public_id}/follow-ups/{$followUp->id}/complete");

        $response->assertOk();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->lead->id,
            'activity_type' => 'follow_up_completed',
            'created_by_user_id' => $this->user->id,
        ]);

        $activity = LeadActivity::where('lead_id', $this->lead->id)
            ->where('activity_type', 'follow_up_completed')
            ->firstOrFail();

        $this->assertEquals($followUp->id, $activity->metadata['follow_up_id']);
        $this->assertNotNull($activity->metadata['completed_at']);
        $this->assertEquals($this->user->id, $activity->metadata['completed_by_user_id']);
    }

    /**
     * Test cancelling a follow-up logs cancelled activity.
     */
    public function test_cancelling_follow_up_logs_cancelled_activity(): void
    {
        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/admin/leads/{$this->lead->public_id}/follow-ups/{$followUp->id}/cancel");

        $response->assertOk();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->lead->id,
            'activity_type' => 'follow_up_cancelled',
            'created_by_user_id' => $this->user->id,
        ]);

        $activity = LeadActivity::where('lead_id', $this->lead->id)
            ->where('activity_type', 'follow_up_cancelled')
            ->firstOrFail();

        $this->assertEquals($followUp->id, $activity->metadata['follow_up_id']);
        $this->assertNotNull($activity->metadata['cancelled_at']);
    }
}
