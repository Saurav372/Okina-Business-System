<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemHealthTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
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

        $user->assignRole($role);

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.system_health.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_system_health_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.system_health.index'))
            ->assertOk()
            ->assertSee('System Health')
            ->assertSee('Database');
    }

    public function test_json_resource_response(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->getJson(route('admin.system_health.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'overall_status',
                    'checked_at',
                    'components' => [
                        'database' => ['name', 'status', 'latency_ms'],
                        'cache' => ['name', 'status'],
                        'storage' => ['name', 'status'],
                    ],
                ],
            ]);
    }
}
