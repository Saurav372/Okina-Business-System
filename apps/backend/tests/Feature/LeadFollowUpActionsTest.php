<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadFollowUpActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $reader;

    private User $unprivileged;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // Create permissions
        Permission::query()->updateOrCreate(
            ['slug' => 'leads.manage'],
            ['name' => 'Manage Leads', 'group' => 'leads', 'guard_name' => 'web', 'description' => 'Manage leads', 'is_sensitive' => false]
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'leads.view'],
            ['name' => 'View Leads', 'group' => 'leads', 'guard_name' => 'web', 'description' => 'View leads', 'is_sensitive' => false]
        );

        // Create roles
        $managerRole = Role::query()->updateOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            ['name' => 'Super Admin', 'guard_name' => 'web', 'description' => 'Super admin role', 'is_system' => true, 'sort_order' => 0]
        );
        $managerRole->permissions()->sync(
            Permission::query()->whereIn('slug', ['leads.manage', 'leads.view'])->pluck('id')->all()
        );

        $readerRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            ['name' => 'Sales Staff', 'guard_name' => 'web', 'description' => 'Sales staff role', 'is_system' => true, 'sort_order' => 0]
        );
        $readerRole->permissions()->sync(
            Permission::query()->whereIn('slug', ['leads.view'])->pluck('id')->all()
        );

        $unprivilegedRole = Role::query()->updateOrCreate(
            ['slug' => Role::PRODUCTION_STAFF],
            ['name' => 'Production Staff', 'guard_name' => 'web', 'description' => 'Production staff role', 'is_system' => true, 'sort_order' => 0]
        );

        // Create users
        $this->manager = User::factory()->create();
        $this->manager->assignRole($managerRole);

        $this->reader = User::factory()->create();
        $this->reader->assignRole($readerRole);

        $this->unprivileged = User::factory()->create();
        $this->unprivileged->assignRole($unprivilegedRole);

        $this->lead = Lead::factory()->create();
    }

    /**
     * Test authorization on follow-up store.
     */
    public function test_store_follow_up_authorization(): void
    {
        $payload = [
            'due_at' => now()->addDays(2)->toIso8601String(),
            'subject' => 'Follow up',
        ];

        // 1. Unauthenticated gets 401
        $this->postJson(route('admin.leads.follow_ups.store', $this->lead), $payload)
            ->assertStatus(401);

        // 2. Unprivileged gets 403
        $this->actingAs($this->unprivileged)
            ->postJson(route('admin.leads.follow_ups.store', $this->lead), $payload)
            ->assertStatus(403);

        // 3. Reader (leads.view only) gets 403
        $this->actingAs($this->reader)
            ->postJson(route('admin.leads.follow_ups.store', $this->lead), $payload)
            ->assertStatus(403);

        // 4. Manager (leads.manage) gets 201
        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.store', $this->lead), $payload)
            ->assertStatus(201);
    }

    /**
     * Test store validation: due_at must be future date.
     */
    public function test_store_follow_up_date_validation(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        $payload = [
            'due_at' => now()->subHour()->toIso8601String(), // Past date
            'subject' => 'Past follow up',
        ];

        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.store', $this->lead), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('due_at');

        Carbon::setTestNow();
    }

    /**
     * Test store follow-up unique notification key validation.
     */
    public function test_store_follow_up_unique_notification_key(): void
    {
        LeadFollowUp::factory()->create([
            'notification_key' => 'dup_key',
        ]);

        $payload = [
            'due_at' => now()->addDays(2)->toIso8601String(),
            'subject' => 'Follow up with duplicate key',
            'notification_key' => 'dup_key',
        ];

        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.store', $this->lead), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('notification_key');
    }

    /**
     * Test response structure and eager-loaded relationships in mapper.
     */
    public function test_follow_up_response_structure(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        $assignedUser = User::factory()->create();

        $payload = [
            'assigned_to_user_id' => $assignedUser->id,
            'due_at' => now()->addDays(2)->toIso8601String(),
            'subject' => 'Verify details',
            'notes' => 'Call at noon',
            'notification_key' => 'custom_key_456',
        ];

        $response = $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.store', $this->lead), $payload);

        $response->assertStatus(201);

        $response->assertJson([
            'status' => 'pending',
            'due_at' => now()->addDays(2)->toIso8601String(),
            'completed_at' => null,
            'snoozed_until' => null,
            'subject' => 'Verify details',
            'notes' => 'Call at noon',
            'notification_key' => 'custom_key_456',
            'assigned_to' => [
                'name' => $assignedUser->name,
                'email' => $assignedUser->email,
            ],
            'completed_by' => null,
            'created_by' => [
                'name' => $this->manager->name,
                'email' => $this->manager->email,
            ],
        ]);

        $response->assertJsonMissing(['lead_id', 'created_by_user_id', 'assigned_to_user_id', 'completed_by_user_id']);

        Carbon::setTestNow();
    }

    /**
     * Test updating/rescheduling follow-up.
     */
    public function test_update_follow_up_rescheduling(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        $followUp = LeadFollowUp::factory()->snoozed()->create([
            'lead_id' => $this->lead->id,
            'notification_key' => 'unique_key_789',
        ]);

        $this->assertNotNull($followUp->snoozed_until);

        $payload = [
            'due_at' => now()->addDays(5)->toIso8601String(),
            'subject' => 'Rescheduled subject',
            'notification_key' => 'unique_key_789', // Ignore self check
        ];

        $response = $this->actingAs($this->manager)
            ->patchJson(route('admin.leads.follow_ups.update', [$this->lead, $followUp]), $payload);

        $response->assertStatus(200);

        // Verify status remains intact or fields updated, and snoozed_until is cleared (null)
        $response->assertJson([
            'subject' => 'Rescheduled subject',
            'snoozed_until' => null,
            'due_at' => now()->addDays(5)->toIso8601String(),
        ]);

        $this->assertNull($followUp->fresh()->snoozed_until);

        Carbon::setTestNow();
    }

    /**
     * Test complete action and terminal state transitions.
     */
    public function test_complete_follow_up(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        $followUp = LeadFollowUp::factory()->snoozed()->create([
            'lead_id' => $this->lead->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.complete', [$this->lead, $followUp]));

        $response->assertStatus(200);

        $response->assertJson([
            'status' => 'completed',
            'completed_at' => now()->toIso8601String(),
            'snoozed_until' => null,
            'completed_by' => [
                'name' => $this->manager->name,
                'email' => $this->manager->email,
            ],
        ]);

        // Second completion assertion (returns 422)
        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.complete', [$this->lead, $followUp]))
            ->assertStatus(422);

        // Cannot update completed follow-up
        $this->actingAs($this->manager)
            ->patchJson(route('admin.leads.follow_ups.update', [$this->lead, $followUp]), ['subject' => 'Update'])
            ->assertStatus(422);

        // Cannot cancel completed follow-up
        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.cancel', [$this->lead, $followUp]))
            ->assertStatus(422);

        Carbon::setTestNow();
    }

    /**
     * Test cancel action and terminal state transitions.
     */
    public function test_cancel_follow_up(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        $followUp = LeadFollowUp::factory()->snoozed()->create([
            'lead_id' => $this->lead->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.cancel', [$this->lead, $followUp]));

        $response->assertStatus(200);

        $response->assertJson([
            'status' => 'cancelled',
            'snoozed_until' => null,
        ]);

        // Second cancellation assertion (returns 422)
        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.cancel', [$this->lead, $followUp]))
            ->assertStatus(422);

        // Cannot update cancelled follow-up
        $this->actingAs($this->manager)
            ->patchJson(route('admin.leads.follow_ups.update', [$this->lead, $followUp]), ['subject' => 'Update'])
            ->assertStatus(422);

        // Cannot complete cancelled follow-up
        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.complete', [$this->lead, $followUp]))
            ->assertStatus(422);

        Carbon::setTestNow();
    }

    /**
     * Test scoped bindings / child-parent ownership.
     */
    public function test_scoped_bindings_rejection(): void
    {
        $otherLead = Lead::factory()->create();

        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $otherLead->id,
        ]);

        // Accessing other lead's follow-up through this lead route must return 404
        $this->actingAs($this->manager)
            ->patchJson(route('admin.leads.follow_ups.update', [$this->lead, $followUp]), ['subject' => 'Hack'])
            ->assertStatus(404);

        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.complete', [$this->lead, $followUp]))
            ->assertStatus(404);

        $this->actingAs($this->manager)
            ->postJson(route('admin.leads.follow_ups.cancel', [$this->lead, $followUp]))
            ->assertStatus(404);
    }
}
