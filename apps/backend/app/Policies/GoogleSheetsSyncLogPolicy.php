<?php

namespace App\Policies;

use App\Models\GoogleSheetsSyncLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class GoogleSheetsSyncLogPolicy
{
    /**
     * Determine if the user can view any Google Sheets sync logs.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('sheets.view');
        }

        return false;
    }

    /**
     * Determine if the user can view the specific Google Sheets sync log.
     */
    public function view(Authenticatable $actor, GoogleSheetsSyncLog $log): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('sheets.view');
        }

        return false;
    }

    /**
     * Determine if the user can retry sync events.
     */
    public function retry(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('sheets.manage');
        }

        return false;
    }

    /**
     * Determine if the user can prune logs.
     */
    public function prune(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('sheets.manage');
        }

        return false;
    }

    /**
     * Determine if the user can manually trigger a sync for a record.
     */
    public function sync(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('sheets.manage');
        }

        return false;
    }
}
