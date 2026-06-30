<?php

namespace Tests\Feature;

use App\Enums\LeadFollowUpStatus;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadFollowUpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the lead_follow_ups table migration exists, runs, and has correct columns/indexes.
     */
    public function test_migration_creates_table_with_expected_columns_and_indexes(): void
    {
        $tableName = 'lead_follow_ups';

        $this->assertTrue(Schema::hasTable($tableName));

        $this->assertTrue(Schema::hasColumns($tableName, [
            'id', 'lead_id', 'assigned_to_user_id', 'status', 'due_at',
            'completed_at', 'completed_by_user_id', 'snoozed_until',
            'subject', 'notes', 'notification_key', 'created_by_user_id',
            'created_at', 'updated_at',
        ]));
    }

    /**
     * Test nullable unique constraint on notification_key.
     * Null values should be allowed duplicates, but duplicate non-nulls should fail.
     */
    public function test_nullable_unique_constraint_on_notification_key(): void
    {
        $lead = Lead::factory()->create();

        // 1. Verify multiple NULL notification keys are accepted
        LeadFollowUp::factory()->count(3)->create([
            'lead_id' => $lead->id,
            'notification_key' => null,
        ]);

        $this->assertEquals(3, LeadFollowUp::whereNull('notification_key')->count());

        // 2. Verify duplicate non-null notification keys throw QueryException
        LeadFollowUp::factory()->create([
            'lead_id' => $lead->id,
            'notification_key' => 'unique_key_123',
        ]);

        $this->expectException(QueryException::class);

        LeadFollowUp::factory()->create([
            'lead_id' => $lead->id,
            'notification_key' => 'unique_key_123',
        ]);
    }

    /**
     * Test enum cast.
     */
    public function test_status_attribute_casts_to_enum(): void
    {
        $followUp = LeadFollowUp::factory()->create([
            'status' => LeadFollowUpStatus::PENDING,
        ]);

        $this->assertInstanceOf(LeadFollowUpStatus::class, $followUp->status);
        $this->assertEquals(LeadFollowUpStatus::PENDING, $followUp->status);
        $this->assertEquals('pending', $followUp->status->value);
    }

    /**
     * Test timestamp casts to Carbon.
     */
    public function test_timestamps_cast_to_carbon(): void
    {
        $followUp = LeadFollowUp::factory()->create([
            'due_at' => now()->addDay(),
            'completed_at' => now(),
            'snoozed_until' => now()->addDays(2),
        ]);

        $this->assertInstanceOf(Carbon::class, $followUp->due_at);
        $this->assertInstanceOf(Carbon::class, $followUp->completed_at);
        $this->assertInstanceOf(Carbon::class, $followUp->snoozed_until);
    }

    /**
     * Test relationships are functional.
     */
    public function test_relationships_integrity(): void
    {
        $lead = Lead::factory()->create();
        $assignedUser = User::factory()->create();
        $completedByUser = User::factory()->create();
        $createdByUser = User::factory()->create();

        $followUp = LeadFollowUp::factory()->create([
            'lead_id' => $lead->id,
            'assigned_to_user_id' => $assignedUser->id,
            'completed_by_user_id' => $completedByUser->id,
            'created_by_user_id' => $createdByUser->id,
        ]);

        // Relationships on LeadFollowUp
        $this->assertInstanceOf(Lead::class, $followUp->lead);
        $this->assertEquals($lead->id, $followUp->lead->id);

        $this->assertInstanceOf(User::class, $followUp->assignedTo);
        $this->assertEquals($assignedUser->id, $followUp->assignedTo->id);

        $this->assertInstanceOf(User::class, $followUp->completedBy);
        $this->assertEquals($completedByUser->id, $followUp->completedBy->id);

        $this->assertInstanceOf(User::class, $followUp->createdBy);
        $this->assertEquals($createdByUser->id, $followUp->createdBy->id);

        // Inverse relationship on Lead
        $this->assertTrue($lead->followUps->contains($followUp));
        $this->assertCount(1, $lead->followUps);
    }

    /**
     * Test query scopes.
     */
    public function test_query_scopes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-30 12:00:00'));

        $lead = Lead::factory()->create();

        // 1. Pending scope (due in 2 days)
        $pending = LeadFollowUp::factory()->create([
            'lead_id' => $lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->addDays(2),
        ]);

        // 2. Completed scope (due in 2 days, completed now)
        $completed = LeadFollowUp::factory()->create([
            'lead_id' => $lead->id,
            'status' => LeadFollowUpStatus::COMPLETED,
            'due_at' => now()->addDays(2),
        ]);

        // 3. Overdue scope (due 2 days ago)
        $overdue = LeadFollowUp::factory()->create([
            'lead_id' => $lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->subDays(2),
        ]);

        // 4. Due Today scope (due today at 2 PM, in the future relative to 12 PM)
        $dueToday = LeadFollowUp::factory()->create([
            'lead_id' => $lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->startOfDay()->addHours(14),
        ]);

        // Verify Pending scope
        $pendingQuery = LeadFollowUp::pending()->get();
        $this->assertTrue($pendingQuery->contains($pending));
        $this->assertTrue($pendingQuery->contains($overdue));
        $this->assertTrue($pendingQuery->contains($dueToday));
        $this->assertFalse($pendingQuery->contains($completed));

        // Verify Completed scope
        $completedQuery = LeadFollowUp::completed()->get();
        $this->assertTrue($completedQuery->contains($completed));
        $this->assertFalse($completedQuery->contains($pending));

        // Verify Overdue scope
        $overdueQuery = LeadFollowUp::overdue()->get();
        $this->assertTrue($overdueQuery->contains($overdue));
        $this->assertFalse($overdueQuery->contains($pending));
        $this->assertFalse($overdueQuery->contains($dueToday));

        // Verify Due Today scope
        $dueTodayQuery = LeadFollowUp::dueToday()->get();
        $this->assertTrue($dueTodayQuery->contains($dueToday));
        $this->assertFalse($dueTodayQuery->contains($pending)); // due in 2 days
        $this->assertFalse($dueTodayQuery->contains($overdue)); // due 2 days ago

        Carbon::setTestNow();
    }

    /**
     * Test factory states.
     */
    public function test_factory_states(): void
    {
        $pending = LeadFollowUp::factory()->pending()->create();
        $this->assertEquals(LeadFollowUpStatus::PENDING, $pending->status);
        $this->assertNull($pending->notification_key);

        $completed = LeadFollowUp::factory()->completed()->create();
        $this->assertEquals(LeadFollowUpStatus::COMPLETED, $completed->status);
        $this->assertNotNull($completed->completed_at);
        $this->assertNotNull($completed->completed_by_user_id);

        $snoozed = LeadFollowUp::factory()->snoozed()->create();
        $this->assertEquals(LeadFollowUpStatus::SNOOZED, $snoozed->status);
        $this->assertNotNull($snoozed->snoozed_until);

        $cancelled = LeadFollowUp::factory()->cancelled()->create();
        $this->assertEquals(LeadFollowUpStatus::CANCELLED, $cancelled->status);

        $overdue = LeadFollowUp::factory()->overdue()->create();
        $this->assertEquals(LeadFollowUpStatus::PENDING, $overdue->status);
        $this->assertTrue($overdue->due_at->isPast());

        $withKey = LeadFollowUp::factory()->withNotificationKey('test_key_123')->create();
        $this->assertEquals('test_key_123', $withKey->notification_key);
    }
}
