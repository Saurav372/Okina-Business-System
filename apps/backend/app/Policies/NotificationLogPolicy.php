<?php

namespace App\Policies;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class NotificationLogPolicy
{
    /**
     * Determine whether the actor can view any models.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('notifications.view');
        }

        return false;
    }

    /**
     * Determine whether the actor can view the model.
     */
    public function view(Authenticatable $actor, NotificationLog $log): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('notifications.view');
        }

        return false;
    }

    /**
     * Determine whether the actor can create models.
     */
    public function create(Authenticatable $actor): bool
    {
        return false;
    }

    /**
     * Determine whether the actor can update the model.
     */
    public function update(Authenticatable $actor, NotificationLog $log): bool
    {
        return false;
    }

    /**
     * Determine whether the actor can delete the model.
     */
    public function delete(Authenticatable $actor, NotificationLog $log): bool
    {
        return false;
    }
}
