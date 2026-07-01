<?php

namespace App\Observers;

use App\Events\AuditEvent;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerObserver
{
    /**
     * Timestamp-only or framework-generated columns that do not constitute
     * a meaningful business change.
     *
     * @var array<int, string>
     */
    private const IGNORED_KEYS = [
        'updated_at',
        'created_at',
        'deleted_at',
        'last_login_at',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * Handle the Customer "updated" event.
     *
     * Fires after the model has been saved. At this point:
     *   - getChanges()  → new values that were just persisted
     *   - getOriginal() → pre-save (old) values (syncOriginal has not yet run)
     */
    public function updated(Customer $customer): void
    {
        $changes = collect($customer->getChanges())->except(self::IGNORED_KEYS);

        if ($changes->isEmpty()) {
            return;
        }

        $oldValues = collect($customer->getOriginal())->only($changes->keys())->all();
        $newValues = $changes->all();

        DB::afterCommit(function () use ($customer, $oldValues, $newValues): void {
            event(new AuditEvent('customers.customer_updated', Auth::user(), [
                'subject_type' => 'customer',
                'subject_id' => $customer->id,
                'subject_public_id' => $customer->public_id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]));
        });
    }
}
