<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_initials_extraction(): void
    {
        $user1 = new User(['name' => 'Saurav Nanda']);
        $this->assertEquals('SN', $user1->initials());

        $user2 = new User(['name' => 'John']);
        $this->assertEquals('JO', $user2->initials());

        $user3 = new User(['name' => '']);
        $this->assertEquals('US', $user3->initials());

        $user4 = new User(['name' => 'First Middle Last Name']);
        $this->assertEquals('FN', $user4->initials());
    }

    public function test_navigation_hides_groups_with_no_permitted_children(): void
    {
        // Create user with no roles or permissions
        $user = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);

        $navigation = (new Navigation)->forUser($user);

        // A user with no permissions should only see the Dashboard group (since Dashboard has null permission)
        $this->assertCount(1, $navigation);
        $this->assertEquals('Dashboard', $navigation[0]->group);
    }

    public function test_navigation_allows_groups_when_user_has_permissions(): void
    {
        $user = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);

        // Grant "orders.view" permission to the user
        $role = Role::create(['name' => 'Sales', 'slug' => 'sales']);
        $permission = Permission::create(['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'sales']);
        $role->permissions()->attach($permission->id, ['granted_by_user_id' => $user->id]);
        $user->roles()->attach($role->id, ['assigned_by_user_id' => $user->id, 'assigned_at' => now()]);

        $navigation = (new Navigation)->forUser($user);

        // Now Dashboard and Sales should both be visible
        $visibleGroups = collect($navigation)->map(fn ($g) => $g->group)->all();
        $this->assertContains('Dashboard', $visibleGroups);
        $this->assertContains('Sales', $visibleGroups);
    }
}
