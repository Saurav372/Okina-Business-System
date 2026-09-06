<?php

namespace App\Policies;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class NotificationLogPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('notifications.view');
        }

        return false;
    }

    public function view(Authenticatable $actor, NotificationLog $log): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('notifications.view');
        }

        return false;
    }

    public function create(Authenticatable $actor): bool
    {
        return false;
    }

    public function update(Authenticatable $actor, NotificationLog $log): bool
    {
        return false;
    }

    public function delete(Authenticatable $actor, NotificationLog $log): bool
    {
        return false;
    }
}
