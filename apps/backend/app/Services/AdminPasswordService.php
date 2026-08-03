<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminPasswordService
{
    public function __construct(
        protected AdminSessionService $sessionService
    ) {}

    /**
     * Update user password, clear lockouts, revoke other sessions, and dispatch audit event inside DB transaction.
     *
     * @return int Revoked other session count
     */
    public function changePassword(User $user, string $newPassword, string $currentSessionId): int
    {
        // Cryptographic Hash diff check: reject if new password matches current hash
        if (Hash::check($newPassword, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The new password must be different from your current password.',
            ])->errorBag('password');
        }

        $userId = $user->id;

        return DB::transaction(function () use ($user, $newPassword, $currentSessionId, $userId): int {
            // Password attribute uses 'hashed' cast on User model
            $user->password = $newPassword;
            $user->password_changed_at = now();
            $user->failed_login_attempts = 0;
            $user->locked_until = null;
            $user->save();

            $revokedCount = $this->sessionService->revokeOtherSessions($user, $currentSessionId);

            $nowIso = now()->toIso8601String();
            $actor = $user;

            DB::afterCommit(static function () use ($userId, $nowIso, $revokedCount, $actor): void {
                event(new AuditEvent('security.password_updated', $actor, [
                    'user_id' => $userId,
                    'actor_id' => $userId,
                    'password_changed_at' => $nowIso,
                    'other_sessions_revoked_count' => $revokedCount,
                ]));
            });

            return $revokedCount;
        });
    }
}
