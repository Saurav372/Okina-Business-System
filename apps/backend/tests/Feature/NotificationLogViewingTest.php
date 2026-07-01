<?php

namespace Tests\Feature;

use App\Models\NotificationDeliveryAttempt;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationLogViewingTest extends TestCase
{
    use RefreshDatabase;

    protected User $unauthorizedUser;

    protected User $authorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // Seed access control permissions
        $this->seed(AccessControlSeeder::class);

        $this->authorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->authorizedUser->assignRole(Role::SUPER_ADMIN);

        $this->unauthorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedUser->assignRole(Role::SALES_STAFF);

        // Clear tables
        NotificationDeliveryAttempt::truncate();
        NotificationLog::truncate();
    }

    /**
     * Test guests are redirected/rejected.
     */
    public function test_guest_is_forbidden_to_view_logs(): void
    {
        $log = NotificationLog::create([
            'event_type' => 'test',
            'channel' => 'email',
            'recipient_type' => 'external',
            'status' => 'pending',
        ]);

        $this->getJson(route('admin.notification_logs.index'))->assertStatus(302)->assertRedirect(route('login'));
        $this->getJson(route('admin.notification_logs.show', $log))->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test unauthorized users cannot view logs (both index and show are verified independently).
     */
    public function test_unauthorized_user_is_forbidden_to_view_logs(): void
    {
        $log = NotificationLog::create([
            'event_type' => 'test',
            'channel' => 'email',
            'recipient_type' => 'external',
            'status' => 'pending',
        ]);

        $this->actingAs($this->unauthorizedUser)
            ->getJson(route('admin.notification_logs.index'))
            ->assertStatus(403);

        $this->actingAs($this->unauthorizedUser)
            ->getJson(route('admin.notification_logs.show', $log))
            ->assertStatus(403);
    }

    /**
     * Test authorized users can list logs.
     */
    public function test_authorized_user_can_view_logs_list(): void
    {
        NotificationLog::create([
            'event_type' => 'user.welcome',
            'channel' => 'email',
            'recipient_type' => 'external',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.index'))
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'event_type',
                    'template_key',
                    'channel',
                    'status',
                    'recipient_type',
                    'recipient_address',
                    'created_at',
                    'attempts',
                ],
            ],
            'current_page',
            'total',
        ]);
    }

    /**
     * Test paginated listing bounds and page sizes.
     */
    public function test_paginated_listing_bounds_and_limit(): void
    {
        for ($i = 0; $i < 30; $i++) {
            NotificationLog::create([
                'event_type' => 'test.event',
                'channel' => 'sms',
                'recipient_type' => 'external',
                'status' => 'sent',
            ]);
        }

        // Test default per_page = 25
        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.index'))
            ->assertStatus(200);

        $this->assertCount(25, $response->json('data'));
        $this->assertEquals(30, $response->json('total'));

        // Test custom per_page = 10
        $responseCustom = $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.index', ['per_page' => 10]))
            ->assertStatus(200);

        $this->assertCount(10, $responseCustom->json('data'));

        // Test custom per_page bound max 100 validation (invalid above 100)
        $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.index', ['per_page' => 101]))
            ->assertStatus(422);
    }

    /**
     * Test ordering newest logs first.
     */
    public function test_logs_ordered_newest_first(): void
    {
        $logOld = NotificationLog::create([
            'event_type' => 'old.event',
            'channel' => 'email',
            'recipient_type' => 'external',
            'status' => 'pending',
            'created_at' => now()->subDay(),
        ]);

        $logNew = NotificationLog::create([
            'event_type' => 'new.event',
            'channel' => 'email',
            'recipient_type' => 'external',
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.index'))
            ->assertStatus(200);

        // Sorting by created_at desc, id desc means newest log is first
        $this->assertEquals($logNew->id, $response->json('data.0.id'));
        $this->assertEquals($logOld->id, $response->json('data.1.id'));
    }

    /**
     * Test list filters.
     */
    public function test_list_filters_work_correctly(): void
    {
        NotificationLog::create([
            'event_type' => 'filter.one',
            'channel' => 'email',
            'recipient_type' => 'external',
            'status' => 'sent',
        ]);

        NotificationLog::create([
            'event_type' => 'filter.two',
            'channel' => 'sms',
            'recipient_type' => 'external',
            'status' => 'failed',
        ]);

        // Filter by channel
        $responseEmail = $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.index', ['channel' => 'email']))
            ->assertStatus(200);
        $this->assertCount(1, $responseEmail->json('data'));
        $this->assertEquals('filter.one', $responseEmail->json('data.0.event_type'));

        // Filter by status
        $responseFailed = $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.index', ['status' => 'failed']))
            ->assertStatus(200);
        $this->assertCount(1, $responseFailed->json('data'));
        $this->assertEquals('filter.two', $responseFailed->json('data.0.event_type'));
    }

    /**
     * Test detailed show endpoint with attempts.
     */
    public function test_show_detailed_view_includes_delivery_attempts_and_template(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'show.test',
            'channel' => 'whatsapp',
            'name' => 'Show Test',
            'body_template' => 'Show Body',
            'status' => 'active',
        ]);

        $log = NotificationLog::create([
            'event_type' => 'show.test',
            'channel' => 'whatsapp',
            'recipient_type' => 'external',
            'status' => 'failed',
            'template_id' => $template->id,
        ]);

        $attempt = NotificationDeliveryAttempt::create([
            'notification_log_id' => $log->id,
            'status' => 'failed',
            'error_message' => 'Gateway failure',
            'response_payload' => ['error_code' => 500],
            'attempted_at' => now(),
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('admin.notification_logs.show', $log))
            ->assertStatus(200);

        $response->assertJsonPath('id', $log->id);
        $response->assertJsonPath('template.id', $template->id);
        $response->assertJsonPath('attempts.0.id', $attempt->id);
        $response->assertJsonPath('attempts.0.response_payload.error_code', 500);
    }
}
