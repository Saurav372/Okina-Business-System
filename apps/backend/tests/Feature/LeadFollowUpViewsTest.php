<?php

namespace Tests\Feature;

use App\Enums\LeadFollowUpStatus;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadFollowUpViewsTest extends TestCase
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

        // Setup permissions
        Permission::query()->updateOrCreate(
            ['slug' => 'leads.manage'],
            ['name' => 'Manage Leads', 'group' => 'leads', 'guard_name' => 'web', 'description' => 'Manage leads', 'is_sensitive' => false]
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'leads.view'],
            ['name' => 'View Leads', 'group' => 'leads', 'guard_name' => 'web', 'description' => 'View leads', 'is_sensitive' => false]
        );

        // Setup roles
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

        // Setup users
        $this->manager = User::factory()->create();
        $this->manager->assignRole($managerRole);

        $this->reader = User::factory()->create();
        $this->reader->assignRole($readerRole);

        $this->unprivileged = User::factory()->create();
        $this->unprivileged->assignRole($unprivilegedRole);

        $this->lead = Lead::factory()->create();
    }

    /**
     * Test index authorization.
     */
    public function test_index_authorization(): void
    {
        // Unauthenticated gets 401
        $this->getJson(route('admin.leads.follow_ups.index'))
            ->assertStatus(401);

        // Unprivileged gets 403
        $this->actingAs($this->unprivileged)
            ->getJson(route('admin.leads.follow_ups.index'))
            ->assertStatus(403);

        // Reader (leads.view) gets 200
        $this->actingAs($this->reader)
            ->getJson(route('admin.leads.follow_ups.index'))
            ->assertStatus(200);

        // Manager (leads.manage) gets 200
        $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index'))
            ->assertStatus(200);
    }

    /**
     * Test status filtering.
     */
    public function test_status_filtering(): void
    {
        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
        ]);

        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::COMPLETED,
        ]);

        // Filter status=completed
        $response = $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index', ['status' => 'completed']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('completed', $response->json('data.0.status'));
    }

    /**
     * Test overdue filtering.
     */
    public function test_overdue_filtering(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        // Overdue (due 2 days ago, pending)
        $overdue = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->subDays(2),
        ]);

        // Upcoming (due in 2 days, pending)
        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index', ['filter' => 'overdue']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($overdue->id, $response->json('data.0.id'));

        Carbon::setTestNow();
    }

    /**
     * Test due_today filtering.
     */
    public function test_due_today_filtering(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        // Due today (due today at 2 PM, pending)
        $dueToday = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->startOfDay()->addHours(14),
        ]);

        // Upcoming (due tomorrow, pending)
        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index', ['filter' => 'due_today']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($dueToday->id, $response->json('data.0.id'));

        Carbon::setTestNow();
    }

    /**
     * Test assignee filtering.
     */
    public function test_assignee_filtering(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'assigned_to_user_id' => $userA->id,
        ]);

        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'assigned_to_user_id' => $userB->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index', ['assigned_to_user_id' => $userA->id]));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($userA->email, $response->json('data.0.assigned_to.email'));
    }

    /**
     * Test combined filter (assigned_to_user_id and status).
     */
    public function test_combined_filters(): void
    {
        $userA = User::factory()->create();

        // 1. User A + pending
        $target = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'assigned_to_user_id' => $userA->id,
            'status' => LeadFollowUpStatus::PENDING,
        ]);

        // 2. User A + completed
        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'assigned_to_user_id' => $userA->id,
            'status' => LeadFollowUpStatus::COMPLETED,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index', [
                'assigned_to_user_id' => $userA->id,
                'status' => 'pending',
            ]));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($target->id, $response->json('data.0.id'));
    }

    /**
     * Test mutual exclusivity (status and filter cannot be combined).
     */
    public function test_mutual_exclusivity(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index', [
                'status' => 'pending',
                'filter' => 'overdue',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status', 'filter']);
    }

    /**
     * Test sorting (ascending due_at and secondary id) and pagination.
     */
    public function test_sorting_and_pagination(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        $f1 = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => now()->addDays(5), // Latest
        ]);

        $f2 = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => now()->addDays(2), // Middle
        ]);

        $f3 = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => now()->subDays(2), // Overdue (earliest)
        ]);

        $f4 = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => now()->addDays(2), // Identical to $f2 (secondary sort test)
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index', ['per_page' => 2]));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // Ascending order: f3 (earliest) first
        $this->assertEquals($f3->id, $response->json('data.0.id'));

        // Next is f2 (due in 2 days) or f4 (also due in 2 days, sorted by id secondary)
        $expectedSecond = $f2->id < $f4->id ? $f2->id : $f4->id;
        $this->assertEquals($expectedSecond, $response->json('data.1.id'));

        // Pagination query parameter retention
        $this->assertStringContainsString('per_page=2', $response->json('first_page_url'));

        Carbon::setTestNow();
    }

    /**
     * Test empty results.
     */
    public function test_empty_results(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson(route('admin.leads.follow_ups.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
