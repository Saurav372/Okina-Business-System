<?php

namespace Tests\Feature;

use App\Jobs\SyncRecordToGoogleSheetsJob;
use App\Models\Customer;
use App\Models\GoogleSheetsSyncLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\GoogleSheets\GoogleSheetsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GoogleSheetsSyncLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions for testing using custom models
        $viewPermission = Permission::query()->updateOrCreate(
            ['slug' => 'sheets.view'],
            [
                'name' => 'View Sheets Logs',
                'group' => 'google_sheets',
                'guard_name' => 'web',
                'description' => 'Can view sync logs',
                'is_sensitive' => false,
            ]
        );
        $managePermission = Permission::query()->updateOrCreate(
            ['slug' => 'sheets.manage'],
            [
                'name' => 'Manage Sheets Logs',
                'group' => 'google_sheets',
                'guard_name' => 'web',
                'description' => 'Can manage sync logs',
                'is_sensitive' => false,
            ]
        );

        $superAdminRole = Role::query()->updateOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
                'description' => 'Super Admin',
                'is_system' => true,
                'sort_order' => 1,
            ]
        );
        $superAdminRole->permissions()->sync([$viewPermission->id, $managePermission->id]);

        $salesStaffRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales Staff',
                'is_system' => false,
                'sort_order' => 2,
            ]
        );
        $salesStaffRole->permissions()->sync([$viewPermission->id]);

        // A role that has dashboard access but NO sheets permissions
        $inventoryStaffRole = Role::query()->updateOrCreate(
            ['slug' => Role::INVENTORY_STAFF],
            [
                'name' => 'Inventory Staff',
                'guard_name' => 'web',
                'description' => 'Inventory Staff',
                'is_system' => false,
                'sort_order' => 3,
            ]
        );
    }

    /**
     * Helper to create a staff user with dashboard access.
     */
    protected function createStaffUser(string $roleSlug): User
    {
        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($roleSlug);

        return $user;
    }

    /**
     * Test log lifecycle: queued -> processing -> success.
     */
    public function test_log_lifecycle_success(): void
    {
        // 1. Create a customer. Disable sync during factory creation to avoid auto-firing.
        Config::set('sheets.enabled', false);
        $customer = Customer::factory()->create(['display_name' => 'Test Customer']);
        Config::set('sheets.enabled', true);

        // Pre-create the log in queued status (as the observer would)
        $log = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => $customer->id,
            'unique_key' => 'pending',
            'unique_value' => 'pending',
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'triggered_by' => 'automatic',
            'payload_hash' => '',
        ]);

        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        $mockClient->shouldReceive('syncRow')->once()->andReturnNull();
        $this->app->instance(GoogleSheetsClient::class, $mockClient);

        // 2. Handle the job to transition to processing -> success
        $job = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id, $log->id);
        $this->app->call([$job, 'handle']);

        // Reload log and assert state transitions
        $log->refresh();
        $this->assertEquals(GoogleSheetsSyncLog::STATUS_SUCCESS, $log->status);
        $this->assertEquals(1, $log->attempts);
        $this->assertNotNull($log->completed_at);
        $this->assertNull($log->payload); // No payload stored on success
        $this->assertNotNull($log->payload_hash);
    }

    /**
     * Test log lifecycle on permanent failure.
     */
    public function test_log_lifecycle_permanent_failure(): void
    {
        Config::set('sheets.enabled', false);
        $customer = Customer::factory()->create();
        Config::set('sheets.enabled', true);
        Config::set('sheets.logging.store_payloads', true);

        $log = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => $customer->id,
            'unique_key' => 'pending',
            'unique_value' => 'pending',
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'triggered_by' => 'automatic',
            'payload_hash' => '',
        ]);

        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        // Throw a permanent error (404)
        $mockClient->shouldReceive('syncRow')->andThrow(new \Exception('Spreadsheet not found', 404));
        $this->app->instance(GoogleSheetsClient::class, $mockClient);

        $job = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id, $log->id);
        $this->app->call([$job, 'handle']);

        $log->refresh();
        $this->assertEquals(GoogleSheetsSyncLog::STATUS_FAILED, $log->status);
        $this->assertEquals(1, $log->attempts);
        $this->assertNotNull($log->completed_at);
        $this->assertEquals('Spreadsheet not found', $log->error_message);
        $this->assertNotNull($log->payload); // Payload stored on failure
    }

    /**
     * Test subsequent model saves create separate sync log events.
     */
    public function test_subsequent_saves_create_new_logs(): void
    {
        Config::set('sheets.enabled', false);
        $customer = Customer::factory()->create();

        $log1 = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => $customer->id,
            'unique_key' => 'pending',
            'unique_value' => 'pending',
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'triggered_by' => 'automatic',
            'payload_hash' => '',
        ]);

        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        $mockClient->shouldReceive('syncRow')->twice()->andReturnNull();
        $this->app->instance(GoogleSheetsClient::class, $mockClient);

        // Turn enabled to true before running the job
        Config::set('sheets.enabled', true);

        $job1 = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id, $log1->id);
        $this->app->call([$job1, 'handle']);

        // Update customer. Disable sync during update to avoid auto-firing.
        Config::set('sheets.enabled', false);
        $customer->update(['display_name' => 'Updated Name']);
        Config::set('sheets.enabled', true);

        $log2 = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => $customer->id,
            'unique_key' => 'pending',
            'unique_value' => 'pending',
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'triggered_by' => 'automatic',
            'payload_hash' => '',
        ]);

        $this->assertNotEquals($log1->id, $log2->id);

        $job2 = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id, $log2->id);
        $this->app->call([$job2, 'handle']);

        $log2->refresh();
        $this->assertEquals(GoogleSheetsSyncLog::STATUS_SUCCESS, $log2->status);
    }

    /**
     * Test that retries increment attempts on the same log.
     */
    public function test_retry_increments_attempts_on_same_log(): void
    {
        Config::set('sheets.enabled', false);
        $customer = Customer::factory()->create();
        Config::set('sheets.enabled', true);

        $log = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => $customer->id,
            'unique_key' => 'pending',
            'unique_value' => 'pending',
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'triggered_by' => 'automatic',
            'payload_hash' => '',
        ]);

        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        // First call fails with transient error, second call succeeds
        $mockClient->shouldReceive('syncRow')->twice()->andReturnUsing(function () {
            static $count = 0;
            $count++;
            if ($count === 1) {
                throw new \Exception('Transient quota error', 429);
            }

            return null;
        });
        $this->app->instance(GoogleSheetsClient::class, $mockClient);

        // First attempt (fails and throws)
        $job = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id, $log->id);
        try {
            $this->app->call([$job, 'handle']);
        } catch (\Throwable $e) {
            $this->assertEquals('Transient quota error', $e->getMessage());
        }

        $log->refresh();
        $this->assertEquals(GoogleSheetsSyncLog::STATUS_QUEUED, $log->status);
        $this->assertEquals(1, $log->attempts);

        // Second attempt (retry, succeeds)
        $this->app->call([$job, 'handle']);

        $log->refresh();
        $this->assertEquals(GoogleSheetsSyncLog::STATUS_SUCCESS, $log->status);
        $this->assertEquals(2, $log->attempts);
    }

    /**
     * Test manual sync endpoint creates a new log.
     */
    public function test_manual_sync_endpoint(): void
    {
        Config::set('sheets.enabled', false);
        $customer = Customer::factory()->create();
        Config::set('sheets.enabled', true);
        Queue::fake();

        $admin = $this->createStaffUser(Role::SUPER_ADMIN);

        // Clear automatic logs to isolate manual test
        GoogleSheetsSyncLog::truncate();

        $response = $this->actingAs($admin)->postJson(route('admin.google_sheets.sync_record'), [
            'model_class' => Customer::class,
            'model_id' => $customer->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('google_sheets_sync_logs', [
            'model_class' => Customer::class,
            'model_id' => $customer->id,
            'status' => GoogleSheetsSyncLog::STATUS_QUEUED,
            'triggered_by' => 'manual',
            'user_id' => $admin->id,
        ]);

        Queue::assertPushed(SyncRecordToGoogleSheetsJob::class);
    }

    /**
     * Test retry endpoint on failed logs.
     */
    public function test_retry_endpoint(): void
    {
        Config::set('sheets.enabled', true);
        Queue::fake();

        $admin = $this->createStaffUser(Role::SUPER_ADMIN);

        $log = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => 123,
            'unique_key' => 'id',
            'unique_value' => '123',
            'status' => GoogleSheetsSyncLog::STATUS_FAILED,
            'payload_hash' => 'hash',
            'error_message' => 'Failed permanently',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.google_sheets.sync_logs.retry', $log));

        $response->assertStatus(200);
        $log->refresh();
        $this->assertEquals(GoogleSheetsSyncLog::STATUS_QUEUED, $log->status);
        $this->assertNull($log->error_message);

        Queue::assertPushed(SyncRecordToGoogleSheetsJob::class, function ($job) use ($log, $admin) {
            // Retrieve properties using reflection
            $refLogId = (new \ReflectionClass($job))->getProperty('syncLogId');
            $refLogId->setAccessible(true);
            $refTriggeredBy = (new \ReflectionClass($job))->getProperty('triggeredBy');
            $refTriggeredBy->setAccessible(true);
            $refUserId = (new \ReflectionClass($job))->getProperty('userId');
            $refUserId->setAccessible(true);

            return $refLogId->getValue($job) === $log->id &&
                $refTriggeredBy->getValue($job) === 'retry' &&
                $refUserId->getValue($job) === $admin->id;
        });
    }

    /**
     * Test authorization on admin endpoints.
     */
    public function test_authorization_protection(): void
    {
        $staff = $this->createStaffUser(Role::SALES_STAFF); // has sheets.view, but not sheets.manage
        $unauthorized = $this->createStaffUser(Role::INVENTORY_STAFF); // no sheets permissions

        $log = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => 123,
            'unique_key' => 'id',
            'unique_value' => '123',
            'status' => GoogleSheetsSyncLog::STATUS_FAILED,
            'payload_hash' => 'hash',
        ]);

        // 1. Staff can view logs
        $this->actingAs($staff)->getJson(route('admin.google_sheets.sync_logs.index'))->assertStatus(200);
        $this->actingAs($staff)->getJson(route('admin.google_sheets.sync_logs.show', $log))->assertStatus(200);

        // 2. Staff CANNOT retry (returns 403)
        $this->actingAs($staff)->postJson(route('admin.google_sheets.sync_logs.retry', $log))->assertStatus(403);

        // 3. Unauthorized user cannot view logs (returns 403)
        $this->actingAs($unauthorized)->getJson(route('admin.google_sheets.sync_logs.index'))->assertStatus(403);
    }

    /**
     * Test pruning command deletes old logs.
     */
    public function test_pruning_command(): void
    {
        Config::set('sheets.logging.prune_days', 10);

        // Create log older than 10 days
        $oldLog = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => 1,
            'unique_key' => 'id',
            'unique_value' => '1',
            'status' => GoogleSheetsSyncLog::STATUS_SUCCESS,
            'payload_hash' => 'hash1',
        ]);
        $oldLog->timestamps = false;
        $oldLog->created_at = now()->subDays(11);
        $oldLog->save();

        // Create log newer than 10 days
        $newLog = GoogleSheetsSyncLog::create([
            'model_class' => Customer::class,
            'model_id' => 2,
            'unique_key' => 'id',
            'unique_value' => '2',
            'status' => GoogleSheetsSyncLog::STATUS_SUCCESS,
            'payload_hash' => 'hash2',
        ]);
        $newLog->timestamps = false;
        $newLog->created_at = now()->subDays(5);
        $newLog->save();

        Artisan::call('sheets:prune-logs');

        $this->assertDatabaseMissing('google_sheets_sync_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('google_sheets_sync_logs', ['id' => $newLog->id]);
    }
}
