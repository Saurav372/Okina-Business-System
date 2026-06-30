<?php

namespace Tests\Feature;

use App\Enums\LeadFollowUpStatus;
use App\Events\LeadFollowUpDue;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LeadFollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->lead = Lead::factory()->create();
    }

    /**
     * Test command dispatches events for due follow-ups including exact boundary now().
     */
    public function test_dispatches_events_for_due_follow_ups(): void
    {
        Event::fake([LeadFollowUpDue::class]);

        Carbon::setTestNow('2026-06-30 12:00:00');

        // 1. Overdue
        $overdue = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->subHour(),
        ]);

        // 2. Due exactly now (exact boundary)
        $dueNow = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now(),
        ]);

        // 3. Future (ignored)
        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now()->addHour(),
        ]);

        Artisan::call('crm:dispatch-follow-up-reminders');

        Event::assertDispatched(LeadFollowUpDue::class, 2);
        Event::assertDispatched(LeadFollowUpDue::class, function (LeadFollowUpDue $event) use ($overdue) {
            return $event->followUp->id === $overdue->id;
        });
        Event::assertDispatched(LeadFollowUpDue::class, function (LeadFollowUpDue $event) use ($dueNow) {
            return $event->followUp->id === $dueNow->id;
        });

        Carbon::setTestNow();
    }

    /**
     * Test command ignores completed and cancelled follow-ups.
     */
    public function test_ignores_completed_and_cancelled_follow_ups(): void
    {
        Event::fake([LeadFollowUpDue::class]);

        Carbon::setTestNow('2026-06-30 12:00:00');

        // Completed (past due_at)
        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::COMPLETED,
            'due_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        // Cancelled (past due_at)
        LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'status' => LeadFollowUpStatus::CANCELLED,
            'due_at' => now()->subHour(),
        ]);

        Artisan::call('crm:dispatch-follow-up-reminders');

        Event::assertNotDispatched(LeadFollowUpDue::class);

        Carbon::setTestNow();
    }

    /**
     * Test deterministic sorting order of dispatched events.
     */
    public function test_deterministic_sorting_order(): void
    {
        Event::fake([LeadFollowUpDue::class]);

        Carbon::setTestNow('2026-06-30 12:00:00');

        $f1 = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => now()->subMinutes(10), // second due
        ]);

        $f2 = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => now()->subMinutes(30), // first due
        ]);

        $f3 = LeadFollowUp::factory()->create([
            'lead_id' => $this->lead->id,
            'due_at' => now()->subMinutes(10), // identical to f1, should sort by id secondary
        ]);

        Artisan::call('crm:dispatch-follow-up-reminders');

        $dispatched = [];
        Event::assertDispatched(LeadFollowUpDue::class, function (LeadFollowUpDue $event) use (&$dispatched) {
            $dispatched[] = $event->followUp->id;

            return true;
        });

        $this->assertCount(3, $dispatched);
        // Sorted ascending: f2 (earliest), then f1 vs f3 depending on id
        $this->assertEquals($f2->id, $dispatched[0]);
        $expectedSecond = $f1->id < $f3->id ? $f1->id : $f3->id;
        $expectedThird = $f1->id < $f3->id ? $f3->id : $f1->id;
        $this->assertEquals($expectedSecond, $dispatched[1]);
        $this->assertEquals($expectedThird, $dispatched[2]);

        Carbon::setTestNow();
    }
}
