<?php

namespace App\Http\Controllers;

use App\Events\AuditEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffInvitationController extends Controller
{
    /**
     * Show the staff invitation password setup screen.
     */
    public function show(string $token): View
    {
        $tokenHash = hash('sha256', $token);

        $user = User::query()
            ->where('user_type', User::TYPE_STAFF)
            ->where('invitation_token_hash', $tokenHash)
            ->first();

        if (! $user || ! $user->hasValidInvitation()) {
            return view('auth.staff-invitation', [
                'isValid' => false,
                'errorMessage' => 'This invitation link is invalid or has expired. Please request a new invitation from your administrator.',
                'user' => null,
                'token' => $token,
            ]);
        }

        return view('auth.staff-invitation', [
            'isValid' => true,
            'errorMessage' => null,
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Complete activation by choosing a secure password.
     */
    public function update(Request $request, string $token): RedirectResponse|View
    {
        $tokenHash = hash('sha256', $token);

        $user = User::query()
            ->where('user_type', User::TYPE_STAFF)
            ->where('invitation_token_hash', $tokenHash)
            ->first();

        if (! $user || ! $user->hasValidInvitation()) {
            return view('auth.staff-invitation', [
                'isValid' => false,
                'errorMessage' => 'This invitation link is invalid or has expired. Please request a new invitation from your administrator.',
                'user' => null,
                'token' => $token,
            ]);
        }

        $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        DB::transaction(function () use ($user, $request): void {
            $user->forceFill([
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
                'invitation_token_hash' => null,
                'invitation_expires_at' => null,
                'password_changed_at' => now(),
            ])->save();

            event(new AuditEvent('users.invitation_accepted', $user, [
                'subject_type' => 'user',
                'subject_id' => $user->id,
                'subject_public_id' => $user->email,
                'new_values' => [
                    'status' => User::STATUS_ACTIVE,
                    'activated_at' => now()->toIso8601String(),
                ],
            ]));
        });

        // Automatically log in to the admin dashboard
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('status', 'Your account has been activated successfully. Welcome to Okina Business System!');
    }
}
