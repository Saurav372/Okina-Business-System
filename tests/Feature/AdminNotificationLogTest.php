<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationLogTest extends TestCase
{
    use RefreshDatabase;

    private function createStaffUserWithPermission(string $permissionName): User
    {
        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $role = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            ['name' => 'Admin', 'description' => 'Admin Role']
        );

        $perm = Permission::query()->updateOrCreate(
            ['slug' => $permissionName],
            ['name' => $permissionName, 'group' => 'system', 'description' => 'Permission']
        );

        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_authorized_user_can_view_notification_logs_with_masked_recipient(): void
    {
        $user = $this->createStaffUserWithPermission('notifications.view');

        NotificationLog::query()->forceCreate([
            'event_type' => 'order.confirmed',
            'template_key' => 'order_confirmation',
            'channel' => 'email',
            'status' => 'sent',
            'recipient_type' => 'customer',
            'recipient_address' => 'john.doe@example.com',
            'subject_rendered' => 'Order Confirmed',
            'body_summary' => 'Your order #1001 is confirmed.',
        ]);

        $this->actingAs($user)
            ->get(route('admin.notification_logs.index'))
            ->assertOk()
            ->assertSee('j******e@example.com');
    }
}
