<?php

namespace Tests\Feature;

use App\Enums\AuditActorType;
use App\Models\AuditLog;
use App\Models\AuditLogRelatedRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // Clean up any automatically seeded logs
        DB::table('audit_logs')->delete();
    }

    /**
     * Test pruning deletes logs older than retention threshold and preserves newer ones.
     */
    public function test_prunes_only_logs_older_than_retention_days(): void
    {
        Config::set('audit.retention_days', 30);

        // Expired (31 days ago)
        $expiredLog = AuditLog::create([
            'event_id' => 'evt_expired',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now()->subDays(31),
        ]);

        // Retained (29 days ago)
        $retainedLog = AuditLog::create([
            'event_id' => 'evt_retained',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now()->subDays(29),
        ]);

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Retention: 30 days')
            ->expectsOutputToContain('Deleted:')
            ->expectsOutputToContain('1 audit logs')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('audit_logs', ['id' => $expiredLog->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $retainedLog->id]);
    }

    /**
     * Test boundary conditions at the exact cutoff mark.
     */
    public function test_pruning_boundary_conditions(): void
    {
        Config::set('audit.retention_days', 365);

        // Exactly 365 days ago (should be retained under occurred_at < cutoff)
        $exactCutoffLog = AuditLog::create([
            'event_id' => 'evt_cutoff',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now()->subDays(365),
        ]);

        // 365 days and 1 second ago (should be deleted)
        $slightlyOlderLog = AuditLog::create([
            'event_id' => 'evt_older',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now()->subDays(365)->subSecond(),
        ]);

        // 365 days minus 1 second ago (should be retained)
        $slightlyNewerLog = AuditLog::create([
            'event_id' => 'evt_newer',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now()->subDays(365)->addSecond(),
        ]);

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Retention: 365 days')
            ->expectsOutputToContain('1 audit logs')
            ->assertExitCode(0);

        $this->assertDatabaseHas('audit_logs', ['id' => $exactCutoffLog->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $slightlyOlderLog->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $slightlyNewerLog->id]);
    }

    /**
     * Test cascade deletion for audit_log_related_records works.
     */
    public function test_pruning_cascades_to_related_records(): void
    {
        Config::set('audit.retention_days', 10);

        $expiredLog = AuditLog::create([
            'event_id' => 'evt_expired',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now()->subDays(15),
        ]);

        $relatedRecord = AuditLogRelatedRecord::create([
            'audit_log_id' => $expiredLog->id,
            'related_type' => 'orders',
            'related_id' => 123,
            'relation' => 'subject',
        ]);

        $this->assertDatabaseHas('audit_log_related_records', ['id' => $relatedRecord->id]);

        $this->artisan('audit:prune')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('audit_logs', ['id' => $expiredLog->id]);
        $this->assertDatabaseMissing('audit_log_related_records', ['id' => $relatedRecord->id]);
    }

    /**
     * Test empty database behaves correctly.
     */
    public function test_empty_database_pruning_success(): void
    {
        Config::set('audit.retention_days', 365);

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Retention: 365 days')
            ->expectsOutputToContain('0 audit logs')
            ->assertExitCode(0);
    }

    /**
     * Test pruning a large dataset that spans multiple chunk iterations.
     */
    public function test_large_dataset_pruning_chunking(): void
    {
        Config::set('audit.retention_days', 30);

        // Insert 2500 expired records and 500 recent records in bulk to run fast
        $expiredData = [];
        for ($i = 1; $i <= 2500; $i++) {
            $expiredData[] = [
                'event_id' => "evt_exp_{$i}",
                'action' => 'test.action',
                'module' => 'test',
                'actor_type' => 'system',
                'subject_type' => 'test',
                'occurred_at' => now()->subDays(40)->toDateTimeString(),
                'created_at' => now()->subDays(40)->toDateTimeString(),
            ];
        }

        $retainedData = [];
        for ($i = 1; $i <= 500; $i++) {
            $retainedData[] = [
                'event_id' => "evt_ret_{$i}",
                'action' => 'test.action',
                'module' => 'test',
                'actor_type' => 'system',
                'subject_type' => 'test',
                'occurred_at' => now()->subDays(5)->toDateTimeString(),
                'created_at' => now()->subDays(5)->toDateTimeString(),
            ];
        }

        DB::table('audit_logs')->insert($expiredData);
        DB::table('audit_logs')->insert($retainedData);

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Retention: 30 days')
            ->expectsOutputToContain('2500 audit logs')
            ->assertExitCode(0);

        $this->assertEquals(500, DB::table('audit_logs')->count());
    }

    /**
     * Test configuration input validation protects against unsafe negative/zero values.
     */
    public function test_normalizes_invalid_retention_days_configuration(): void
    {
        // Unsafe configuration (zero/negative) should fallback/normalize to 1 day
        Config::set('audit.retention_days', 0);

        $expiredLog = AuditLog::create([
            'event_id' => 'evt_expired',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now()->subDays(2),
        ]);

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Retention: 1 days') // normalized from 0 to 1
            ->expectsOutputToContain('1 audit logs')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('audit_logs', ['id' => $expiredLog->id]);
    }
}
