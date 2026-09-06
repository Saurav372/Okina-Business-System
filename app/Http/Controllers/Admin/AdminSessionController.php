<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Services\AdminSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSessionController extends Controller
{
    public function __construct(
        protected AdminSessionService $sessionService
    ) {}

    /**
     * Revoke all other active & expired session rows for current user.
     */
    public function revokeOthers(Request $request): RedirectResponse
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();
        $userId = $user->id;
        $actor = $user;

        $revokedCount = DB::transaction(function () use ($user, $currentSessionId, $userId, $actor): int {
            $count = $this->sessionService->revokeOtherSessions($user, $currentSessionId);

            $nowIso = now()->toIso8601String();

            // Emit event even if count = 0 (records evidence of the user's explicit action)
            DB::afterCommit(static function () use ($userId, $count, $nowIso, $actor): void {
                event(new AuditEvent('security.sessions_revoked', $actor, [
                    'user_id' => $userId,
                    'actor_id' => $userId,
                    'revoked_count' => $count,
                    'occurred_at' => $nowIso,
                ]));
            });

            return $count;
        });

        $message = $revokedCount > 0
            ? "Revoked {$revokedCount} other active session(s)."
            : 'No other active sessions were found.';

        return redirect()->route('admin.security')->with('status', $message);
    }
}
