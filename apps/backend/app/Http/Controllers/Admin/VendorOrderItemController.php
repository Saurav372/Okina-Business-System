<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Exceptions\PurchaseOrderImmutableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StoreVendorOrderItemRequest;
use App\Http\Requests\PurchaseOrder\UpdateVendorOrderItemRequest;
use App\Models\ProductSku;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VendorOrderItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVendorOrderItemRequest $request, VendorOrder $purchaseOrder): JsonResponse
    {
        Gate::authorize('create', [VendorOrderItem::class, $purchaseOrder]);

        if ($purchaseOrder->status->value !== 'draft') {
            throw new PurchaseOrderImmutableException('Cannot modify items of a purchase order that is not in draft status.');
        }

        $sku = ProductSku::with('product:id,name')->findOrFail($request->input('product_sku_id'));

        try {
            $item = DB::transaction(function () use ($request, $purchaseOrder, $sku) {
                $item = new VendorOrderItem($request->validated());
                $item->vendor_order_id = $purchaseOrder->id;
                $item->currency = $purchaseOrder->currency;
                $item->sku_code_snapshot = $sku->sku_code;
                $item->product_name_snapshot = $sku->product?->name;
                $item->line_total_minor = $item->calculateLineTotal();
                $item->save();

                $purchaseOrder->recalculateTotals();
                $purchaseOrder->save();

                DB::afterCommit(function () use ($item) {
                    event(new AuditEvent('purchase_order_items.created', Auth::user(), [
                        'vendor_order_id' => $item->vendor_order_id,
                        'vendor_order_item_id' => $item->id,
                        'product_sku_id' => $item->product_sku_id,
                        'sku_code' => $item->sku_code_snapshot,
                        'quantity_ordered' => $item->quantity_ordered,
                        'unit_cost_minor' => $item->unit_cost_minor,
                        'line_total_minor' => $item->line_total_minor,
                        'actor_id' => Auth::id(),
                    ]));
                });

                return $item;
            });
        } catch (QueryException $e) {
            $isUniqueViolation = $e->getCode() === '23000'
                || str_contains($e->getMessage(), '1062 Duplicate entry')
                || str_contains($e->getMessage(), 'UNIQUE constraint failed: vendor_order_items.vendor_order_id, vendor_order_items.product_sku_id');

            if ($isUniqueViolation) {
                throw ValidationException::withMessages([
                    'product_sku_id' => 'This product SKU is already in the purchase order.',
                ]);
            }
            throw $e;
        }

        return response()->json($item, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVendorOrderItemRequest $request, VendorOrder $purchaseOrder, VendorOrderItem $item): JsonResponse
    {
        Gate::authorize('update', $item);

        if ($purchaseOrder->status->value !== 'draft') {
            throw new PurchaseOrderImmutableException('Cannot modify items of a purchase order that is not in draft status.');
        }

        DB::transaction(function () use ($request, $purchaseOrder, $item) {
            $data = $request->except(['expected_at']);
            $item->fill($data);

            if ($request->has('expected_at')) {
                $expectedAt = $request->input('expected_at');
                $item->changeExpectedAt($expectedAt ? Carbon::parse($expectedAt) : null, $purchaseOrder);
            }

            $item->line_total_minor = $item->calculateLineTotal();
            $item->save();

            $purchaseOrder->recalculateTotals();
            $purchaseOrder->save();

            DB::afterCommit(function () use ($item) {
                event(new AuditEvent('purchase_order_items.updated', Auth::user(), [
                    'vendor_order_id' => $item->vendor_order_id,
                    'vendor_order_item_id' => $item->id,
                    'quantity_ordered' => $item->quantity_ordered,
                    'unit_cost_minor' => $item->unit_cost_minor,
                    'line_total_minor' => $item->line_total_minor,
                    'actor_id' => Auth::id(),
                ]));
            });
        });

        return response()->json($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VendorOrder $purchaseOrder, VendorOrderItem $item): JsonResponse
    {
        Gate::authorize('delete', $item);

        if ($purchaseOrder->status->value !== 'draft') {
            throw new PurchaseOrderImmutableException('Cannot modify items of a purchase order that is not in draft status.');
        }

        DB::transaction(function () use ($purchaseOrder, $item) {
            $item->delete();

            $purchaseOrder->recalculateTotals();
            $purchaseOrder->save();

            DB::afterCommit(function () use ($item) {
                event(new AuditEvent('purchase_order_items.deleted', Auth::user(), [
                    'vendor_order_id' => $item->vendor_order_id,
                    'vendor_order_item_id' => $item->id,
                    'actor_id' => Auth::id(),
                ]));
            });
        });

        return response()->json(['message' => 'Purchase order item deleted successfully.']);
    }
}
