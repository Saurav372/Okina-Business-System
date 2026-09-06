<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permView = Permission::query()->firstOrCreate(
            ['slug' => 'inventory.view'],
            [
                'name' => 'Inventory View',
                'group' => 'inventory',
                'guard_name' => 'web',
                'description' => 'View stock balances and inventory movements',
                'is_sensitive' => false,
            ]
        );

        $permManage = Permission::query()->firstOrCreate(
            ['slug' => 'inventory.manage'],
            [
                'name' => 'Inventory Manage',
                'group' => 'inventory',
                'guard_name' => 'web',
                'description' => 'Perform manual stock adjustments and inventory updates',
                'is_sensitive' => false,
            ]
        );

        $rolesToAttach = [Role::SUPER_ADMIN, Role::ADMIN, Role::INVENTORY_STAFF];

        foreach ($rolesToAttach as $roleSlug) {
            $role = Role::query()->where('slug', $roleSlug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching([$permView->id, $permManage->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perms = Permission::query()->whereIn('slug', ['inventory.view', 'inventory.manage'])->get();

        foreach ($perms as $perm) {
            $perm->roles()->detach();
            $perm->delete();
        }
    }
};
