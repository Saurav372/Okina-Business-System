<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffProfileRequest;
use App\Http\Requests\Admin\UpdateStaffRolesRequest;
use App\Http\Requests\Admin\UpdateStaffStatusRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    /**
     * Display paginated list of staff members with metrics and dynamic filters.
     */
    public function index(Request $request): View
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->hasRole(Role::SUPER_ADMIN) && ! $actor->hasPermissionTo('users.view'))) {
            abort(403, 'Unauthorized access to staff directory.');
        }

        // Real-time metric counts
        $totalStaff = User::query()->where('user_type', User::TYPE_STAFF)->count();
        $activeStaff = User::query()
            ->where('user_type', User::TYPE_STAFF)
            ->where('status', User::STATUS_ACTIVE)
            ->where(function ($q): void {
                $q->whereNull('locked_until')->orWhere('locked_until', '<=', now());
            })
            ->count();
        $invitedStaff = User::query()
            ->where('user_type', User::TYPE_STAFF)
            ->where('status', User::STATUS_INVITED)
            ->count();
        $suspendedStaff = User::query()
            ->where('user_type', User::TYPE_STAFF)
            ->where('status', User::STATUS_SUSPENDED)
            ->count();
        $lockedStaff = User::query()
            ->where('user_type', User::TYPE_STAFF)
            ->where('locked_until', '>', now())
            ->count();

        // Staff query with eager-loaded roles
        $query = User::query()
            ->where('user_type', User::TYPE_STAFF)
            ->with(['roles' => fn ($q) => $q->orderBy('sort_order', 'asc')]);

        // Search by name or email
        if ($search = trim((string) $request->input('search'))) {
            $escaped = addcslashes($search, '%_');
            $query->where(function ($q) use ($escaped): void {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        // Status filter (active, invited, suspended, locked)
        if ($statusFilter = $request->input('status')) {
            if ($statusFilter === 'locked') {
                $query->where('locked_until', '>', now());
            } elseif (in_array($statusFilter, [User::STATUS_ACTIVE, User::STATUS_INVITED, User::STATUS_SUSPENDED], true)) {
                $query->where('status', $statusFilter);
                if ($statusFilter === User::STATUS_ACTIVE) {
                    $query->where(function ($q): void {
                        $q->whereNull('locked_until')->orWhere('locked_until', '<=', now());
                    });
                }
            }
        }

        // Role filter
        if ($roleFilter = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $roleFilter));
        }

        $staffMembers = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $roles = Role::query()->withCount('users')->orderBy('sort_order', 'asc')->get();

        return view('admin.staff.index', [
            'staffMembers' => $staffMembers,
            'roles' => $roles,
            'metrics' => [
                'total' => $totalStaff,
                'active' => $activeStaff,
                'invited' => $invitedStaff,
                'suspended' => $suspendedStaff,
                'locked' => $lockedStaff,
                'roles_count' => $roles->count(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'role' => $request->input('role', ''),
            ],
        ]);
    }

    /**
     * Invite a new staff member with assigned roles and an expiring cryptographic activation token.
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $actor = Auth::user();

        $plainToken = null;
        $createdUser = DB::transaction(function () use ($request, $actor, &$plainToken) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => strtolower(trim((string) $request->validated('email'))),
                'phone' => $request->validated('phone'),
                'password' => Hash::make(Str::random(64)), // Placeholder unexposed hash
                'user_type' => User::TYPE_STAFF,
                'status' => User::STATUS_INVITED,
                'email_verified_at' => null,
            ]);

            // Assign initial roles
            $user->syncRoles((array) $request->validated('roles'), $actor);

            // Generate one-time cryptographic invitation token (48h expiry)
            $plainToken = $user->generateInvitationToken(48);

            return $user;
        });

        $invitationUrl = route('staff.invitation.show', $plainToken);

        // Immutable Audit Log
        event(new AuditEvent('users.staff_invited', $actor, [
            'subject_type' => 'user',
            'subject_id' => $createdUser->id,
            'subject_public_id' => $createdUser->email,
            'new_values' => [
                'name' => $createdUser->name,
                'email' => $createdUser->email,
                'roles' => (array) $request->validated('roles'),
            ],
            'metadata' => [
                'invited_by_user_id' => $actor?->id,
                'invitation_expires_at' => $createdUser->invitation_expires_at?->toIso8601String(),
            ],
        ]));

        return redirect()->route('admin.staff.index')
            ->with('status', "Staff member {$createdUser->name} has been invited successfully.")
            ->with('invitation_url', $invitationUrl);
    }

    /**
     * Update staff profile details (identity only: name, phone).
     */
    public function update(UpdateStaffProfileRequest $request, User $staff): RedirectResponse
    {
        $actor = Auth::user();

        $before = [
            'name' => $staff->name,
            'phone' => $staff->phone,
        ];

        $staff->update([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
        ]);

        event(new AuditEvent('users.profile_updated', $actor, [
            'subject_type' => 'user',
            'subject_id' => $staff->id,
            'subject_public_id' => $staff->email,
            'old_values' => $before,
            'new_values' => [
                'name' => $staff->name,
                'phone' => $staff->phone,
            ],
        ]));

        return redirect()->route('admin.staff.index')
            ->with('status', "Profile for {$staff->name} updated successfully.");
    }

    /**
     * Update staff roles with mutual exclusion locking and last Super Admin safeguard.
     */
    public function updateRoles(UpdateStaffRolesRequest $request, User $staff): RedirectResponse
    {
        $actor = Auth::user();
        $newRoles = (array) $request->validated('roles');

        DB::transaction(function () use ($staff, $actor, $newRoles): void {
            // 1. Concurrency Mutex: Lock shared Super Admin role record
            Role::query()
                ->where('slug', Role::SUPER_ADMIN)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Authoritative target lock
            $target = User::query()
                ->whereKey($staff->id)
                ->where('user_type', User::TYPE_STAFF)
                ->lockForUpdate()
                ->firstOrFail();

            $isSuperAdmin = $target->hasRole(Role::SUPER_ADMIN);
            $losingSuperAdmin = $isSuperAdmin && ! in_array(Role::SUPER_ADMIN, $newRoles, true);

            // Invariant: Cannot demote oneself
            if ($target->id === $actor?->id && $losingSuperAdmin) {
                throw ValidationException::withMessages([
                    'roles' => ['Security restriction: You cannot revoke the Super Admin role from your own account.'],
                ]);
            }

            // Invariant: Cannot remove the final operational Super Admin
            if ($losingSuperAdmin) {
                $operationalCount = User::query()
                    ->where('user_type', User::TYPE_STAFF)
                    ->where('status', User::STATUS_ACTIVE)
                    ->where(function ($q): void {
                        $q->whereNull('locked_until')->orWhere('locked_until', '<=', now());
                    })
                    ->whereHas('roles', fn ($q) => $q->where('slug', Role::SUPER_ADMIN))
                    ->count();

                if ($operationalCount <= 1) {
                    throw ValidationException::withMessages([
                        'roles' => ['Operation blocked: At least one active, operational Super Admin must remain in the system.'],
                    ]);
                }
            }

            // Calculate exact role diff
            $beforeRoles = $target->roles()->pluck('slug')->all();
            $added = array_values(array_diff($newRoles, $beforeRoles));
            $removed = array_values(array_diff($beforeRoles, $newRoles));

            // Sync roles
            $target->syncRoles($newRoles, $actor);

            // Session hygiene: Rotate remember token
            $target->forceFill(['remember_token' => Str::random(60)])->save();

            // Audit Delta
            event(new AuditEvent('users.roles_updated', $actor, [
                'subject_type' => 'user',
                'subject_id' => $target->id,
                'subject_public_id' => $target->email,
                'new_values' => [
                    'assigned_roles' => $newRoles,
                    'added_roles' => $added,
                    'removed_roles' => $removed,
                ],
                'metadata' => [
                    'updated_by_user_id' => $actor?->id,
                ],
            ]));
        });

        return redirect()->route('admin.staff.index')
            ->with('status', "Roles for {$staff->name} have been updated.");
    }

    /**
     * Change staff account status (suspend, reactivate, unlock) with session termination.
     */
    public function updateStatus(UpdateStaffStatusRequest $request, User $staff): RedirectResponse
    {
        $actor = Auth::user();
        $action = $request->validated('action');

        DB::transaction(function () use ($staff, $actor, $action): void {
            // 1. Lock shared mutex
            Role::query()
                ->where('slug', Role::SUPER_ADMIN)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Lock target user
            $target = User::query()
                ->whereKey($staff->id)
                ->where('user_type', User::TYPE_STAFF)
                ->lockForUpdate()
                ->firstOrFail();

            // Invariant: Cannot suspend oneself
            if ($action === 'suspend' && $target->id === $actor?->id) {
                throw ValidationException::withMessages([
                    'action' => ['Security restriction: You cannot suspend your own active account.'],
                ]);
            }

            // Invariant: Cannot suspend the final operational Super Admin
            if ($action === 'suspend' && $target->hasRole(Role::SUPER_ADMIN)) {
                $operationalCount = User::query()
                    ->where('user_type', User::TYPE_STAFF)
                    ->where('status', User::STATUS_ACTIVE)
                    ->where(function ($q): void {
                        $q->whereNull('locked_until')->orWhere('locked_until', '<=', now());
                    })
                    ->whereHas('roles', fn ($q) => $q->where('slug', Role::SUPER_ADMIN))
                    ->count();

                if ($operationalCount <= 1) {
                    throw ValidationException::withMessages([
                        'action' => ['Operation blocked: The final operational Super Admin cannot be suspended.'],
                    ]);
                }
            }

            $beforeStatus = $target->status;

            if ($action === 'suspend') {
                $target->forceFill([
                    'status' => User::STATUS_SUSPENDED,
                    'disabled_at' => now(),
                    'disabled_by' => $actor?->id,
                    'remember_token' => Str::random(60),
                ])->save();

                // Session termination: Evict all database sessions for this user
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $target->id)->delete();
                }
            } elseif ($action === 'reactivate') {
                $target->forceFill([
                    'status' => User::STATUS_ACTIVE,
                    'disabled_at' => null,
                    'disabled_by' => null,
                ])->save();
            } elseif ($action === 'unlock') {
                // Clear security lock attributes without altering lifecycle status
                $target->forceFill([
                    'locked_until' => null,
                    'failed_login_attempts' => 0,
                ])->save();
            }

            // Audit status mutation
            event(new AuditEvent('users.status_updated', $actor, [
                'subject_type' => 'user',
                'subject_id' => $target->id,
                'subject_public_id' => $target->email,
                'old_values' => ['status' => $beforeStatus],
                'new_values' => [
                    'status' => $target->status,
                    'action_performed' => $action,
                    'is_locked' => $target->isSecurityLocked(),
                ],
                'metadata' => [
                    'action_by_user_id' => $actor?->id,
                ],
            ]));
        });

        $message = match ($action) {
            'suspend' => "Account for {$staff->name} has been suspended and existing sessions terminated.",
            'reactivate' => "Account for {$staff->name} has been reactivated.",
            'unlock' => "Security lockout on {$staff->name} has been cleared.",
        };

        return redirect()->route('admin.staff.index')->with('status', $message);
    }

    /**
     * Resend an expiring activation invitation link.
     */
    public function resendInvitation(Request $request, User $staff): RedirectResponse
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->hasRole(Role::SUPER_ADMIN) && ! $actor->hasPermissionTo('users.manage_staff'))) {
            abort(403);
        }

        if ($staff->status !== User::STATUS_INVITED) {
            return redirect()->route('admin.staff.index')
                ->withErrors(['staff' => 'This account is already activated and cannot receive an invitation.']);
        }

        $plainToken = $staff->generateInvitationToken(48);
        $invitationUrl = route('staff.invitation.show', $plainToken);

        event(new AuditEvent('users.invitation_resent', $actor, [
            'subject_type' => 'user',
            'subject_id' => $staff->id,
            'subject_public_id' => $staff->email,
            'metadata' => [
                'resent_by_user_id' => $actor->id,
                'expires_at' => $staff->invitation_expires_at?->toIso8601String(),
            ],
        ]));

        return redirect()->route('admin.staff.index')
            ->with('status', "Invitation for {$staff->name} has been regenerated.")
            ->with('invitation_url', $invitationUrl);
    }

    /**
     * Trigger a secure password reset link to staff email without exposing passwords to the admin.
     */
    public function sendPasswordReset(Request $request, User $staff): RedirectResponse
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->hasRole(Role::SUPER_ADMIN) && ! $actor->hasPermissionTo('users.manage_staff'))) {
            abort(403);
        }

        if ($staff->status === User::STATUS_SUSPENDED) {
            return redirect()->route('admin.staff.index')
                ->withErrors(['staff' => 'Cannot send password reset to a suspended staff account.']);
        }

        // Dispatch password reset link via standard Laravel broker
        Password::broker('users')->sendResetLink(['email' => $staff->email]);

        event(new AuditEvent('users.password_reset_triggered', $actor, [
            'subject_type' => 'user',
            'subject_id' => $staff->id,
            'subject_public_id' => $staff->email,
            'metadata' => [
                'triggered_by_user_id' => $actor->id,
            ],
        ]));

        return redirect()->route('admin.staff.index')
            ->with('status', "Password reset link has been dispatched to {$staff->email}.");
    }
}
