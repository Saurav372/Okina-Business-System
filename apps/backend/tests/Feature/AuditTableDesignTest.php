<?php

namespace Tests\Feature;

use App\Enums\AuditActorType;
use App\Models\AuditLog;
use App\Models\AuditLogRelatedRecord;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditTableDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    /**
     * Test migrations run and roll back cleanly.
     */
    public function test_migrations_run_and_roll_back(): void
    {
        // Assert tables exist after migrating
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('audit_logs'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('audit_log_related_records'));

        // Rollback audit logs and any newer migrations (Notifications)
        $this->artisan('migrate:rollback', ['--step' => 2]);

        $this->assertFalse(DB::getSchemaBuilder()->hasTable('audit_log_related_records'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('audit_logs'));
    }

    /**
     * Test that creating a brand-new audit log is fully permitted.
     */
    public function test_creating_new_audit_log_is_permitted(): void
    {
        $user = User::factory()->create();

        $log = AuditLog::create([
            'event_id' => (string) Str::uuid(),
            'action' => 'order.status_changed',
            'module' => 'orders',
            'actor_type' => AuditActorType::USER,
            'actor_user_id' => $user->id,
            'actor_label_snapshot' => $user->name,
            'subject_type' => 'orders',
            'subject_id' => 1,
            'subject_public_id' => 'ORD-1234',
            'summary' => 'Order status changed to completed',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'completed'],
            'metadata' => ['ip' => '127.0.0.1'],
            'occurred_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_id' => $log->event_id,
        ]);

        // Assert relationships and casts work
        $this->assertInstanceOf(User::class, $log->actorUser);
        $this->assertEquals($user->id, $log->actorUser->id);
        $this->assertSame(AuditActorType::USER, $log->actor_type);
        $this->assertIsArray($log->old_values);
        $this->assertEquals('pending', $log->old_values['status']);
        $this->assertIsArray($log->metadata);
        $this->assertEquals('127.0.0.1', $log->metadata['ip']);
        $this->assertNull($log->updated_at);
    }

    /**
     * Test that saving an existing model, updating, or deleting throws a LogicException (Immutability).
     */
    public function test_immutability_guards_prevent_updates_and_deletes(): void
    {
        $log = AuditLog::create([
            'event_id' => (string) Str::uuid(),
            'action' => 'order.created',
            'module' => 'orders',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'orders',
            'occurred_at' => now(),
        ]);

        $related = AuditLogRelatedRecord::create([
            'audit_log_id' => $log->id,
            'related_type' => 'customers',
            'related_id' => 1,
            'relation' => 'customer',
        ]);

        // 1. AuditLog: updating
        try {
            $log->update(['action' => 'modified']);
            $this->fail('Updating audit log should throw LogicException.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        // 2. AuditLog: saving existing
        try {
            $log->action = 'modified';
            $log->save();
            $this->fail('Saving existing audit log should throw LogicException.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        // 3. AuditLog: deleting
        try {
            $log->delete();
            $this->fail('Deleting audit log should throw LogicException.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        // 4. AuditLogRelatedRecord: updating
        try {
            $related->update(['relation' => 'modified']);
            $this->fail('Updating related record should throw LogicException.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        // 5. AuditLogRelatedRecord: saving existing
        try {
            $related->relation = 'modified';
            $related->save();
            $this->fail('Saving existing related record should throw LogicException.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        // 6. AuditLogRelatedRecord: deleting
        try {
            $related->delete();
            $this->fail('Deleting related record should throw LogicException.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
    }

    /**
     * Test unique constraints on event_id and idempotency_key.
     */
    public function test_unique_constraints(): void
    {
        $eventId = (string) Str::uuid();
        $idempotencyKey = 'idem-1234';

        AuditLog::create([
            'event_id' => $eventId,
            'action' => 'action1',
            'module' => 'module1',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'orders',
            'idempotency_key' => $idempotencyKey,
            'occurred_at' => now(),
        ]);

        // Duplicate event_id
        $this->expectException(QueryException::class);
        AuditLog::create([
            'event_id' => $eventId,
            'action' => 'action2',
            'module' => 'module1',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'orders',
            'occurred_at' => now(),
        ]);

        // Duplicate idempotency_key
        $this->expectException(QueryException::class);
        AuditLog::create([
            'event_id' => (string) Str::uuid(),
            'action' => 'action2',
            'module' => 'module1',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'orders',
            'idempotency_key' => $idempotencyKey,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Test foreign key relationships and cascade behavior.
     */
    public function test_foreign_keys_and_cascades(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $log = AuditLog::create([
            'event_id' => (string) Str::uuid(),
            'action' => 'order.created',
            'module' => 'orders',
            'actor_type' => AuditActorType::USER,
            'actor_user_id' => $user->id,
            'actor_customer_id' => $customer->id,
            'subject_type' => 'orders',
            'occurred_at' => now(),
        ]);

        $related = AuditLogRelatedRecord::create([
            'audit_log_id' => $log->id,
            'related_type' => 'orders',
            'related_id' => 1,
            'relation' => 'subject',
        ]);

        // 1. Delete actor User -> FK actor_user_id should set to null
        $user->delete();
        $log->refresh();
        $this->assertNull($log->actor_user_id);

        // 2. Delete actor Customer -> FK actor_customer_id should set to null
        $customer->delete();
        $log->refresh();
        $this->assertNull($log->actor_customer_id);

        // 3. Delete parent AuditLog -> cascade delete related record link
        // Note: AuditLog booted method blocks delete. To test cascading on delete, we must delete using DB query builder bypassing Eloquent events.
        $this->assertDatabaseHas('audit_log_related_records', ['id' => $related->id]);
        DB::table('audit_logs')->where('id', $log->id)->delete();
        $this->assertDatabaseMissing('audit_log_related_records', ['id' => $related->id]);
    }
}
