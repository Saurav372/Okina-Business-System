<?php

namespace App\Policies;

use App\Models\CustomerAccount;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class StoredFilePolicy
{
    public function view(Authenticatable $actor, StoredFile $file): bool
    {
        if ($actor instanceof User) {
            return $actor->canAccessDashboard() && $actor->hasPermissionTo('files.download_private');
        }

        if ($actor instanceof CustomerAccount) {
            if (! $actor->canAccessCustomerAccount()) {
                return false;
            }

            return $file->visibility !== StoredFile::VISIBILITY_STAFF_ONLY
                && (
                    $file->customer_id === $actor->customer_id
                    || $file->uploaded_by_customer_id === $actor->customer_id
                );
        }

        return false;
    }

    public function download(Authenticatable $actor, StoredFile $file): bool
    {
        return $this->view($actor, $file);
    }

    public function preview(Authenticatable $actor, StoredFile $file): bool
    {
        return $this->view($actor, $file);
    }

    public function delete(Authenticatable $actor, StoredFile $file): bool
    {
        if ($file->protected_until !== null && $file->protected_until->isFuture()) {
            return false;
        }

        return $actor instanceof User
            && $actor->canAccessDashboard()
            && $actor->hasPermissionTo('files.download_private');
    }
}
