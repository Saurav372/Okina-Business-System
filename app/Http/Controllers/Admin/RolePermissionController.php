<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    /**
     * Display the Role & Permission Matrix with collapsible domains and sensitive indicators.
     */
    public function index(Request $request): View
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->hasRole(Role::SUPER_ADMIN) && ! $actor->hasPermissionTo('users.manage_roles'))) {
            abort(403, 'Unauthorized access to role permissions.');
        }

        $roles = Role::query()
            ->with(['permissions' => fn ($q) => $q->select('permissions.id', 'permissions.slug')])
            ->withCount('users')
            ->orderBy('sort_order', 'asc')
            ->get();

        $permissions = Permission::query()
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $groupedPermissions = $permissions->groupBy('group');

        return view('admin.roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
            'isSuperAdmin' => $actor->hasRole(Role::SUPER_ADMIN),
        ]);
    }

    /**
     * Synchronize permissions for a specific role with atomic locking and delta auditing.
     */
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $actor = Auth::user();

        // System guard: Super Admin role permissions are immutable
        if ($role->slug === Role::SUPER_ADMIN) {
            throw ValidationException::withMessages([
                'role' => ['The Super Admin role possesses immutable universal access and cannot be modified.'],
            ]);
        }

        DB::transaction(function () use ($role, $request, $actor): void {
            $targetRole = Role::query()
                ->whereKey($role->id)
                ->lockForUpdate()
                ->firstOrFail();

            $requestedSlugs = (array) $request->validated('permissions', []);
            $permissionModels = Permission::query()->whereIn('slug', $requestedSlugs)->get();
            $permissionIds = $permissionModels->pluck('id')->all();

            $beforeSlugs = $targetRole->permissions()->pluck('slug')->all();
            $added = array_values(array_diff($requestedSlugs, $beforeSlugs));
            $removed = array_values(array_diff($beforeSlugs, $requestedSlugs));

            $targetRole->permissions()->sync($permissionIds);

            // Audit Delta
            event(new AuditEvent('roles.permissions_updated', $actor, [
                'subject_type' => 'role',
                'subject_id' => $targetRole->id,
                'subject_public_id' => $targetRole->slug,
                'new_values' => [
                    'role_name' => $targetRole->name,
                    'assigned_permissions' => $requestedSlugs,
                    'added_permissions' => $added,
                    'removed_permissions' => $removed,
                ],
                'metadata' => [
                    'updated_by_user_id' => $actor?->id,
                ],
            ]));
        });

        return redirect()->route('admin.roles.index')
            ->with('status', "Permissions for role '{$role->name}' have been updated successfully.");
    }
}
