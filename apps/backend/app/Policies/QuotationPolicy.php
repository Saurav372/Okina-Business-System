<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class QuotationPolicy
{
    /**
     * Determine whether the user can view any quotations.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('quotations.view') || $actor->hasPermissionTo('quotations.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can view the quotation.
     */
    public function view(Authenticatable $actor, Quotation $quotation): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('quotations.view') || $actor->hasPermissionTo('quotations.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can create quotations.
     */
    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('quotations.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can update the quotation.
     */
    public function update(Authenticatable $actor, Quotation $quotation): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('quotations.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the quotation.
     */
    public function delete(Authenticatable $actor, Quotation $quotation): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('quotations.manage');
        }

        return false;
    }
}
