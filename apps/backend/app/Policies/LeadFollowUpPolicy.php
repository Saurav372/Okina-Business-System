<?php

namespace App\Policies;

use App\Models\LeadFollowUp;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class LeadFollowUpPolicy
{
    /**
     * Determine whether the user can view any follow-ups.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.view') || $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can view the follow-up.
     */
    public function view(Authenticatable $actor, LeadFollowUp $followUp): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.view') || $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can create follow-ups.
     */
    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can update the follow-up.
     */
    public function update(Authenticatable $actor, LeadFollowUp $followUp): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the follow-up.
     */
    public function delete(Authenticatable $actor, LeadFollowUp $followUp): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can complete the follow-up.
     */
    public function complete(Authenticatable $actor, LeadFollowUp $followUp): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the follow-up.
     */
    public function cancel(Authenticatable $actor, LeadFollowUp $followUp): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }
}
