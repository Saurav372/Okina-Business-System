<?php

namespace App\Observers;

use App\Events\AuditEvent;
use App\Models\InventoryItem;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductSkuObserver
{
    /**
     * Columns excluded from the SKU audit diff.
     *
     * stock_quantity is intentionally excluded because every inventory movement
     * updates this cached field. Inventory changes are already audited via the
     * inventory.stock_moved event dispatched by InventoryBalanceService.
     *
     * @var array<int, string>
     */
    private const IGNORED_KEYS = [
        'updated_at',
        'created_at',
        'deleted_at',
        'stock_quantity',
    ];

    /**
     * Handle the ProductSku "created" event.
     */
    public function created(ProductSku $sku): void
    {
        $initialStock = $sku->stock_quantity ?? 0;

        InventoryItem::create([
            'product_sku_id' => $sku->id,
            'on_hand_quantity' => $initialStock,
            'reserved_quantity' => 0,
            'available_quantity' => $initialStock,
            'allow_negative_stock' => false,
        ]);
    }

    /**
     * Handle the ProductSku "updated" event.
     *
     * Fires after the model has been saved. At this point:
     *   - getChanges()  → new values that were just persisted
     *   - getOriginal() → pre-save (old) values (syncOriginal has not yet run)
     *
     * stock_quantity changes are excluded — those are emitted as inventory.stock_moved
     * by InventoryBalanceService and would produce duplicate noise here.
     */
    public function updated(ProductSku $sku): void
    {
        $changes = collect($sku->getChanges())->except(self::IGNORED_KEYS);

        if ($changes->isEmpty()) {
            return;
        }

        $oldValues = collect($sku->getOriginal())->only($changes->keys())->all();
        $newValues = $changes->all();

        DB::afterCommit(function () use ($sku, $oldValues, $newValues): void {
            event(new AuditEvent('products.sku_updated', Auth::user(), [
                'subject_type' => 'product_sku',
                'subject_id' => $sku->id,
                'subject_public_id' => $sku->sku_code,
                'product_id' => $sku->product_id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]));
        });
    }
}
