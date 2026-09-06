<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    /**
     * Render administrator profile edit page.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $primaryRole = $user->roles()->orderBy('slug')->first()?->name ?? 'Staff';

        return view('admin.profile', [
            'user' => $user,
            'primaryRole' => $primaryRole,
        ]);
    }

    /**
     * Update profile details (name, phone) with diff auditing inside DB transaction.
     */
    public function update(UpdateAdminProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $oldName = (string) $user->name;
        $oldPhone = $user->phone !== null ? (string) $user->phone : null;

        $newName = (string) $validated['name'];
        $newPhone = isset($validated['phone']) && (string) $validated['phone'] !== '' ? (string) $validated['phone'] : null;

        $changedFields = [];
        if ($oldName !== $newName) {
            $changedFields['name'] = ['from' => $oldName, 'to' => $newName];
        }
        if ($oldPhone !== $newPhone) {
            $changedFields['phone'] = ['from' => $oldPhone, 'to' => $newPhone];
        }

        // If no attributes changed, return without database mutation or audit dispatch
        if (empty($changedFields)) {
            return redirect()->route('admin.profile')->with('status', 'Profile updated successfully.');
        }

        $userId = $user->id;
        $actor = $user;

        DB::transaction(function () use ($user, $newName, $newPhone, $changedFields, $userId, $actor): void {
            $user->name = $newName;
            $user->phone = $newPhone;
            $user->save();

            DB::afterCommit(static function () use ($userId, $changedFields, $actor): void {
                event(new AuditEvent('profile.updated', $actor, [
                    'user_id' => $userId,
                    'actor_id' => $userId,
                    'changed_fields' => $changedFields,
                ]));
            });
        });

        return redirect()->route('admin.profile')->with('status', 'Profile updated successfully.');
    }
}
