<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
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

    public function test_guest_is_redirected(): void
    {
        $this->get(route('admin.audit_logs.index'))->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_audit_logs(): void
    {
        $user = $this->createStaffUserWithPermission('audit.view');

        AuditLog::query()->forceCreate([
            'event_id' => 'evt-100',
            'action' => 'profile.updated',
            'module' => 'profile',
            'actor_type' => 'user',
            'actor_user_id' => $user->id,
            'actor_label_snapshot' => $user->name,
            'subject_type' => 'user',
            'subject_id' => (string) $user->id,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.audit_logs.index'))
            ->assertOk()
            ->assertSee('profile.updated');
    }

    public function test_json_api_resource_response(): void
    {
        $user = $this->createStaffUserWithPermission('audit.view');

        AuditLog::query()->forceCreate([
            'event_id' => 'evt-101',
            'action' => 'profile.updated',
            'module' => 'profile',
            'actor_type' => 'user',
            'actor_user_id' => $user->id,
            'actor_label_snapshot' => $user->name,
            'subject_type' => 'user',
            'subject_id' => (string) $user->id,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('admin.audit_logs.index'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'action', 'module', 'actor', 'subject', 'occurred_at']]]);
    }
}
