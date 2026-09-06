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
        $perm = Permission::query()->firstOrCreate(
            ['slug' => 'products.manage_seo'],
            [
                'name' => 'Products Manage SEO',
                'group' => 'products',
                'guard_name' => 'web',
                'description' => 'Manage product SEO metadata',
                'is_sensitive' => false,
            ]
        );

        $superAdmin = Role::query()->where('slug', Role::SUPER_ADMIN)->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching([$perm->id]);
        }

        $admin = Role::query()->where('slug', Role::ADMIN)->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching([$perm->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::query()->where('slug', 'products.manage_seo')->first();
        if ($perm) {
            $perm->roles()->detach();
            $perm->delete();
        }
    }
};
