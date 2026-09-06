<?php

namespace App\Observers;

use App\Events\AuditEvent;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductObserver
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
    ];

    /**
     * Handle the Product "updated" event.
     *
     * Fires after the model has been saved. At this point:
     *   - getChanges()  → new values that were just persisted
     *   - getOriginal() → pre-save (old) values (syncOriginal has not yet run)
     */
    public function updated(Product $product): void
    {
        $changes = collect($product->getChanges())->except(self::IGNORED_KEYS);

        if ($changes->isEmpty()) {
            return;
        }

        $oldValues = collect($product->getOriginal())->only($changes->keys())->all();
        $newValues = $changes->all();

        DB::afterCommit(function () use ($product, $oldValues, $newValues): void {
            event(new AuditEvent('products.product_updated', Auth::user(), [
                'subject_type' => 'product',
                'subject_id' => $product->id,
                'subject_public_id' => $product->slug,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]));
        });
    }
}
