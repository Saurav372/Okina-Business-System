<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Admin\AdminResourceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderResourceBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_resource_is_registered_as_a_read_only_boundary(): void
    {
        $catalog = app(AdminResourceCatalog::class);
        $resource = $catalog->resource('orders');

        $this->assertNotNull($resource);
        $this->assertSame(OrderResource::registration(), $resource);
        $this->assertSame('orders.view', $resource['permission']);
        $this->assertTrue($resource['read_only']);
        $this->assertSame(['view'], $resource['allowed_actions']);
        $this->assertSame([], $resource['pages']);

        foreach (['create', 'edit', 'delete', 'forceDelete', 'restore', 'replicate', 'status', 'payment', 'refund', 'shipping'] as $blockedAction) {
            $this->assertContains($blockedAction, $resource['blocked_actions']);
        }
    }

    public function test_order_resource_requires_the_order_view_permission(): void
    {
        $orderViewer = $this->makeStaffUser('order_viewer', ['orders.view']);
        $dashboardOnly = $this->makeStaffUser('dashboard_only', ['dashboard.access']);

        $this->assertTrue(OrderResource::canAccess($orderViewer));
        $this->assertFalse(OrderResource::canAccess($dashboardOnly));
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
