<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class LeadPolicy
{
    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.view') || $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(Authenticatable $actor, Lead $lead): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.view') || $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(Authenticatable $actor, Lead $lead): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(Authenticatable $actor, Lead $lead): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('leads.manage');
        }

        return false;
    }
}
