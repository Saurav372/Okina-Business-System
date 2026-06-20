<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminOrderScopeGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_only_view_permission_cannot_update_or_delete_order(): void
    {
        $order = Order::factory()->create();

        $user = $this->makeStaffUser('order_viewer', ['orders.view']);

        $this->assertFalse(Gate::forUser($user)->allows('update', $order));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $order));
        $this->assertFalse(Gate::forUser($user)->allows('create', Order::class));
    }

    public function test_user_with_manage_permission_can_update_and_delete_order(): void
    {
        $order = Order::factory()->create();

        $user = $this->makeStaffUser('order_manager', ['orders.manage']);

        $this->assertTrue(Gate::forUser($user)->allows('update', $order));
        $this->assertTrue(Gate::forUser($user)->allows('delete', $order));
        $this->assertTrue(Gate::forUser($user)->allows('create', Order::class));
    }

    private function makeStaffUser(string $roleSlug, array $permissionSlugs): User
    {
        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->headline()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                    'guard_name' => 'web',
                    'description' => str($slug)->headline()->toString(),
                    'is_sensitive' => false,
                ],
            );
        }

        $role = Role::query()->updateOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'guard_name' => 'web',
                'description' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
