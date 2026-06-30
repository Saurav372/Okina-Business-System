<?php

namespace Tests\Feature;

use App\Enums\LeadFollowUpStatus;
use App\Events\LeadFollowUpDue;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LeadFollowUpPermissionsAndRegressionTest extends TestCase
{
    use RefreshDatabase;

    private Lead $leadA;

    private Lead $leadB;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->leadA = Lead::factory()->create();
        $this->leadB = Lead::factory()->create();
    }

    /**
     * Helper to create a staff user with specific permission slugs.
     */
    private function makeStaffWithPermissions(string|array $permissionSlugs): User
    {
        $permissionSlugs = is_array($permissionSlugs) ? $permissionSlugs : [$permissionSlugs];

        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->headline()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                    'guard_name' => 'web',
                    'description' => str($slug)->headline()->toString(),
                    'is_sensitive' => false,
                ],
            );
        }

        $customSlug = 'custom_role_'.implode('_', $permissionSlugs);
        $role = Role::query()->updateOrCreate(
            ['slug' => $customSlug],
            [
                'name' => 'Custom Role '.$customSlug,
                'guard_name' => 'web',
                'description' => 'Custom Role for Test',
                'is_system' => false,
                'sort_order' => 10,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);

        // General sales staff role to satisfy dashboard access middleware if needed
        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff',
                'is_system' => true,
                'sort_order' => 1,
            ],
        );

        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($salesRole);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Test leads.manage user can perform all follow-up actions.
     */
    public function test_leads_manage_can_perform_all_actions(): void
    {
        $user = $this->makeStaffWithPermissions('leads.manage');

        // 1. Store
        $response = $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups", [
                'due_at' => now()->addDay()->toIso8601String(),
                'subject' => 'Follow up',
            ]);
        $response->assertCreated();
        $followUpId = $response->json('id');

        // 2. Index
        $this->actingAs($user)
            ->getJson('/admin/leads/follow-ups')
            ->assertOk();

        // 3. Update
        $this->actingAs($user)
            ->patchJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUpId}", [
                'due_at' => now()->addDays(2)->toIso8601String(),
                'subject' => 'Rescheduled follow up',
            ])
            ->assertOk();

        // 4. Complete
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUpId}/complete")
            ->assertOk();
    }

    /**
     * Test leads.view user can only list but not mutate follow-ups.
     */
    public function test_leads_view_only_can_only_list(): void
    {
        $user = $this->makeStaffWithPermissions('leads.view');
        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $this->leadA->id,
            'created_by_user_id' => $user->id,
        ]);

        // 1. Index is allowed
        $this->actingAs($user)
            ->getJson('/admin/leads/follow-ups')
            ->assertOk();

        // 2. Store blocked
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups", [
                'due_at' => now()->addDay()->toIso8601String(),
                'subject' => 'Follow up',
            ])
            ->assertStatus(403);

        // 3. Update blocked
        $this->actingAs($user)
            ->patchJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}", [
                'due_at' => now()->addDays(2)->toIso8601String(),
            ])
            ->assertStatus(403);

        // 4. Complete blocked
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/complete")
            ->assertStatus(403);

        // 5. Cancel blocked
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/cancel")
            ->assertStatus(403);
    }

    /**
     * Test staff with no leads permissions is forbidden from all endpoints.
     */
    public function test_no_leads_permission_is_forbidden(): void
    {
        // Unprivileged staff has no leads.view or leads.manage
        $user = $this->makeStaffWithPermissions([]);
        $followUp = LeadFollowUp::factory()->create(['lead_id' => $this->leadA->id]);

        $this->actingAs($user)->getJson('/admin/leads/follow-ups')->assertStatus(403);
        $this->actingAs($user)->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups", ['due_at' => now()->addDay()->toIso8601String(), 'subject' => 'S'])->assertStatus(403);
        $this->actingAs($user)->patchJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}", ['due_at' => now()->addDays(2)->toIso8601String()])->assertStatus(403);
        $this->actingAs($user)->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/complete")->assertStatus(403);
        $this->actingAs($user)->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/cancel")->assertStatus(403);
    }

    /**
     * Test unauthenticated is unauthorized.
     */
    public function test_unauthenticated_is_unauthorized(): void
    {
        $followUp = LeadFollowUp::factory()->create(['lead_id' => $this->leadA->id]);

        $this->getJson('/admin/leads/follow-ups')->assertStatus(401);
        $this->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups", ['due_at' => now()->addDay()->toIso8601String(), 'subject' => 'S'])->assertStatus(401);
        $this->patchJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}", ['due_at' => now()->addDays(2)->toIso8601String()])->assertStatus(401);
        $this->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/complete")->assertStatus(401);
        $this->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/cancel")->assertStatus(401);
    }

    /**
     * Test route model binding mismatch.
     */
    public function test_route_model_binding_mismatch_returns_404(): void
    {
        $user = $this->makeStaffWithPermissions('leads.manage');
        // followUp belongs to leadB
        $followUp = LeadFollowUp::factory()->create(['lead_id' => $this->leadB->id]);

        // Attempting to update via leadA context
        $this->actingAs($user)
            ->patchJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}", [
                'due_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertStatus(404);
    }

    /**
     * Test retry-safety on completed and cancelled states (terminal state protection).
     */
    public function test_completed_is_terminal_and_retry_safe(): void
    {
        $user = $this->makeStaffWithPermissions('leads.manage');
        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $this->leadA->id,
            'status' => LeadFollowUpStatus::COMPLETED,
            'completed_at' => now()->subDay(),
        ]);

        $initialActivitiesCount = LeadActivity::where('lead_id', $this->leadA->id)->count();

        // 1. Reschedule completed returns 422
        $this->actingAs($user)
            ->patchJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}", [
                'due_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertStatus(422);

        // 2. Complete again returns 422
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/complete")
            ->assertStatus(422);

        // 3. Cancel completed returns 422
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/cancel")
            ->assertStatus(422);

        // Assert no new timeline entries were recorded for the failed attempts
        $this->assertEquals($initialActivitiesCount, LeadActivity::where('lead_id', $this->leadA->id)->count());
    }

    /**
     * Test cancelled is terminal and retry-safe.
     */
    public function test_cancelled_is_terminal_and_retry_safe(): void
    {
        $user = $this->makeStaffWithPermissions('leads.manage');
        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $this->leadA->id,
            'status' => LeadFollowUpStatus::CANCELLED,
        ]);

        $initialActivitiesCount = LeadActivity::where('lead_id', $this->leadA->id)->count();

        // 1. Reschedule cancelled returns 422
        $this->actingAs($user)
            ->patchJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}", [
                'due_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertStatus(422);

        // 2. Cancel again returns 422
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/cancel")
            ->assertStatus(422);

        // 3. Complete cancelled returns 422
        $this->actingAs($user)
            ->postJson("/admin/leads/{$this->leadA->public_id}/follow-ups/{$followUp->id}/complete")
            ->assertStatus(422);

        // Assert no new timeline entries were recorded for the failed attempts
        $this->assertEquals($initialActivitiesCount, LeadActivity::where('lead_id', $this->leadA->id)->count());
    }

    /**
     * Test reminder dispatch command regression on completed/cancelled follow-ups.
     */
    public function test_reminder_command_does_not_dispatch_for_terminal_states(): void
    {
        Event::fake([
            LeadFollowUpDue::class,
        ]);

        Carbon::setTestNow('2026-06-30 12:00:00');

        // Completed but overdue
        LeadFollowUp::factory()->create([
            'lead_id' => $this->leadA->id,
            'status' => LeadFollowUpStatus::COMPLETED,
            'due_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(30),
        ]);

        // Cancelled but overdue
        LeadFollowUp::factory()->create([
            'lead_id' => $this->leadA->id,
            'status' => LeadFollowUpStatus::CANCELLED,
            'due_at' => now()->subHour(),
        ]);

        Artisan::call('crm:dispatch-follow-up-reminders');

        Event::assertNotDispatched(LeadFollowUpDue::class);

        Carbon::setTestNow();
    }
}
