<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('audit.view');
        }

        return false;
    }

    public function view(Authenticatable $actor, AuditLog $auditLog): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('audit.view');
        }

        return false;
    }

    public function create(Authenticatable $actor): bool
    {
        return false;
    }

    public function update(Authenticatable $actor, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(Authenticatable $actor, AuditLog $auditLog): bool
    {
        return false;
    }
}
