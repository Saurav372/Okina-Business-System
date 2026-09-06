<?php

namespace App\Services;

use App\Models\User;
use App\Support\Security\AdminSessionView;
use App\Support\Security\SessionDevicePresenter;
use Illuminate\Support\Facades\DB;

class AdminSessionService
{
    /**
     * Fetch active session DTO views for a user, excluding expired session rows.
     *
     * @return array<int, AdminSessionView>
     */
    public function getActiveSessions(User $user, string $currentSessionId): array
    {
        $lifetimeMinutes = (int) config('session.lifetime', 120);
        $activeCutoff = now()->timestamp - ($lifetimeMinutes * 60);

        $rows = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $activeCutoff)
            ->orderBy('last_activity', 'desc')
            ->get();

        $views = [];
        foreach ($rows as $row) {
            $views[] = SessionDevicePresenter::present($row, $currentSessionId);
        }

        return $views;
    }

    /**
     * Revoke all non-current active & expired session rows for a user.
     * Returns the count of deleted session rows.
     */
    public function revokeOtherSessions(User $user, string $currentSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}
