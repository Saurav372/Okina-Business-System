<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_admin_login_page(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_log_in_and_access_the_admin_dashboard(): void
    {
        $user = $this->createStaffUserWithRole(Role::ADMIN, [
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Okina Craft Admin');
    }

    public function test_non_staff_users_cannot_access_the_admin_dashboard(): void
    {
        $user = User::factory()->customer()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_non_staff_sessions_are_removed_from_dashboard_routes(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_staff_cannot_access_the_admin_dashboard(): void
    {
        $user = $this->createStaffUserWithRole(Role::ADMIN, [
            'status' => User::STATUS_SUSPENDED,
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unverified_staff_cannot_access_the_admin_dashboard(): void
    {
        $user = $this->createStaffUserWithRole(Role::ADMIN, [
            'email_verified_at' => null,
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_without_a_role_cannot_access_the_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_logins_use_a_generic_dashboard_error(): void
    {
        $user = $this->createStaffUserWithRole(Role::ADMIN, [
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'The provided credentials are incorrect or your account cannot access the dashboard.',
        ]);

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_temporarily_lock_the_staff_account(): void
    {
        $user = $this->createStaffUserWithRole(Role::ADMIN, [
            'password' => Hash::make('password123'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $user->refresh();

        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_forgot_password_uses_a_generic_response(): void
    {
        Notification::fake();

        $user = $this->createStaffUserWithRole(Role::ADMIN);

        $this->post(route('admin.password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status', 'If a dashboard account exists for this email, a reset link has been sent.');
    }

    public function test_profile_and_security_pages_are_protected(): void
    {
        $this->get(route('admin.profile'))->assertRedirect(route('login'));
        $this->get(route('admin.security'))->assertRedirect(route('login'));

        $user = $this->createStaffUserWithRole(Role::ADMIN);

        $this->actingAs($user)
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertSee('Profile');

        $this->actingAs($user)
            ->get(route('admin.security'))
            ->assertOk()
            ->assertSee('Security');
    }

    public function test_staff_can_log_out_and_lose_admin_access(): void
    {
        $user = $this->createStaffUserWithRole(Role::ADMIN, [
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->post(route('admin.logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_roles_can_grant_permissions(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'Update Orders',
            'slug' => 'orders.update_status',
            'group' => 'orders',
        ]);

        $role = Role::factory()->create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'is_system' => true,
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionTo('orders.update_status'));
        $this->assertFalse($user->hasPermissionTo('finance.view_profit'));
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $role = Role::factory()->create([
            'name' => 'Super Admin',
            'slug' => Role::SUPER_ADMIN,
            'is_system' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionTo('finance.view_profit'));
    }

    private function createStaffUserWithRole(string $roleSlug, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => Hash::make('password123'),
        ], $attributes));

        $role = Role::query()->updateOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => str($roleSlug)->replace('_', ' ')->title()->toString(),
                'guard_name' => 'web',
                'description' => str($roleSlug)->replace('_', ' ')->title()->toString(),
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $user->assignRole($role);

        return $user;
    }
}
