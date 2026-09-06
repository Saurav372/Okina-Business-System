<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Exceptions\InvalidPurchaseOrderPaymentStatusTransitionException;
use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use App\Exceptions\PurchaseOrderImmutableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StoreVendorOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdateVendorOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdateVendorOrderStatusRequest;
use App\Models\ProductSku;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use App\Services\PurchaseOrderStatusService;
use App\Support\Purchases\PurchaseOrderCodeGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VendorOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderStatusService $statusService
    ) {}

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): JsonResponse|View
    {
        Gate::authorize('viewAny', VendorOrder::class);

        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return app(PurchaseOrderAdminController::class)->index($request);
        }

        $query = VendorOrder::query()
            ->with(['vendor:id,name,vendor_code', 'creator:id,name'])
            ->filter($request->only(['search', 'status', 'payment_status', 'vendor_id']))
            ->orderByDesc('id');

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVendorOrderRequest $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', VendorOrder::class);

        $vendor = Vendor::findOrFail($request->input('vendor_id'));
        if ($vendor->status !== VendorStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'vendor_id' => 'The selected vendor must be active.',
            ]);
        }

        $attempts = 0;
        $maxAttempts = 3;
        $purchaseOrder = null;

        while ($attempts < $maxAttempts) {
            try {
                $attempts++;

                $purchaseOrder = DB::transaction(function () use ($request) {
                    $validated = $request->validated();

                    $orderedAt = $validated['ordered_at'] ?? $validated['order_date'] ?? now();
                    $expectedAt = $validated['expected_at'] ?? $validated['expected_delivery_date'] ?? null;

                    $shippingMinor = isset($validated['shipping_amount_minor'])
                        ? (int) $validated['shipping_amount_minor']
                        : (isset($validated['shipping_amount']) ? (int) round($validated['shipping_amount'] * 100) : 0);

                    $discountMinor = isset($validated['discount_amount_minor'])
                        ? (int) $validated['discount_amount_minor']
                        : (isset($validated['discount_amount']) ? (int) round($validated['discount_amount'] * 100) : 0);

                    $status = ($validated['status'] ?? 'draft') === 'ordered'
                        ? VendorOrderStatus::ORDERED->value
                        : VendorOrderStatus::DRAFT->value;

                    $notes = $validated['notes'] ?? null;
                    if (! empty($validated['payment_terms'])) {
                        $termNote = 'Payment Terms: '.$validated['payment_terms'];
                        $notes = $notes ? "{$termNote}\n\n{$notes}" : $termNote;
                    }

                    $po = new VendorOrder([
                        'vendor_id' => $validated['vendor_id'],
                        'public_id' => PurchaseOrderCodeGenerator::generate(),
                        'status' => $status,
                        'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
                        'ordered_at' => $orderedAt,
                        'expected_at' => $expectedAt,
                        'subtotal_amount_minor' => (int) ($validated['subtotal_amount_minor'] ?? 0),
                        'tax_amount_minor' => (int) ($validated['tax_amount_minor'] ?? 0),
                        'shipping_amount_minor' => $shippingMinor,
                        'discount_amount_minor' => $discountMinor,
                        'total_amount_minor' => 0,
                        'currency' => $validated['currency'] ?? 'INR',
                        'notes' => $notes,
                        'created_by_user_id' => Auth::id(),
                    ]);
                    $po->total_amount_minor = $po->calculateTotalAmount();
                    $po->save();

                    // Create Line Items if provided
                    $itemsData = $validated['items'] ?? [];
                    foreach ($itemsData as $itemData) {
                        if (empty($itemData['product_sku_id'])) {
                            continue;
                        }

                        $sku = ProductSku::with('product')->find($itemData['product_sku_id']);
                        if (! $sku) {
                            continue;
                        }

                        $unitCostMinor = isset($itemData['unit_cost_minor'])
                            ? (int) $itemData['unit_cost_minor']
                            : (isset($itemData['unit_cost']) ? (int) round($itemData['unit_cost'] * 100) : 0);

                        $taxMinor = isset($itemData['tax_amount_minor'])
                            ? (int) $itemData['tax_amount_minor']
                            : (isset($itemData['tax_amount']) ? (int) round($itemData['tax_amount'] * 100) : 0);

                        $qty = max(1, (int) ($itemData['quantity_ordered'] ?? 1));

                        $item = new VendorOrderItem([
                            'vendor_order_id' => $po->id,
                            'product_sku_id' => $sku->id,
                            'sku_code_snapshot' => $sku->sku_code,
                            'product_name_snapshot' => $sku->product?->name ?? $sku->sku_code,
                            'quantity_ordered' => $qty,
                            'quantity_received' => 0,
                            'unit_cost_minor' => $unitCostMinor,
                            'tax_amount_minor' => $taxMinor,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                        $item->line_total_minor = $item->calculateLineTotal();
                        $item->save();
                    }

                    if (! empty($itemsData)) {
                        $po->recalculateTotals();
                        $po->shipping_amount_minor = $shippingMinor;
                        $po->discount_amount_minor = $discountMinor;
                        $po->total_amount_minor = $po->calculateTotalAmount();
                        $po->save();
                    }

                    DB::afterCommit(function () use ($po) {
                        event(new AuditEvent('purchase_orders.created', Auth::user(), [
                            'public_id' => $po->public_id,
                            'vendor_id' => $po->vendor_id,
                            'previous_status' => null,
                            'new_status' => $po->status->value,
                            'payment_status' => $po->payment_status->value,
                            'subtotal_amount_minor' => $po->subtotal_amount_minor,
                            'tax_amount_minor' => $po->tax_amount_minor,
                            'shipping_amount_minor' => $po->shipping_amount_minor,
                            'discount_amount_minor' => $po->discount_amount_minor,
                            'total_amount_minor' => $po->total_amount_minor,
                            'actor_id' => Auth::id(),
                            'created_at' => $po->created_at->toIso8601String(),
                        ]));
                    });

                    return $po;
                });

                break;
            } catch (QueryException $e) {
                $isUniqueViolation = $e->getCode() === '23000'
                    || str_contains($e->getMessage(), '1062 Duplicate entry')
                    || str_contains($e->getMessage(), 'UNIQUE constraint failed: vendor_orders.public_id');

                if ($isUniqueViolation && $attempts < $maxAttempts) {
                    continue;
                }
                throw $e;
            }
        }

        if (! $purchaseOrder) {
            abort(500, 'Failed to create purchase order.');
        }

        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return redirect()->route('admin.purchases.show', $purchaseOrder->public_id)
                ->with('success', "Purchase order [{$purchaseOrder->public_id}] created successfully.");
        }

        return response()->json($purchaseOrder, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(VendorOrder $purchaseOrder, Request $request): JsonResponse|View
    {
        Gate::authorize('view', $purchaseOrder);

        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return app(PurchaseOrderAdminController::class)->show($purchaseOrder);
        }

        $purchaseOrder->load(['vendor:id,name,vendor_code', 'items.productSku']);

        return response()->json($purchaseOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVendorOrderRequest $request, VendorOrder $purchaseOrder): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $purchaseOrder);

        $isOrdered = $purchaseOrder->status !== VendorOrderStatus::DRAFT;

        if ($isOrdered) {
            $immutableFields = [
                'vendor_id',
                'currency',
                'subtotal_amount_minor',
                'tax_amount_minor',
                'shipping_amount_minor',
                'discount_amount_minor',
            ];

            foreach ($immutableFields as $field) {
                if ($request->has($field) && $request->input($field) != $purchaseOrder->{$field}) {
                    throw new PurchaseOrderImmutableException(
                        "Cannot modify field [{$field}] once purchase order is ordered."
                    );
                }
            }
        }

        if ($request->filled('vendor_id')) {
            $newVendor = Vendor::findOrFail($request->input('vendor_id'));
            if ($newVendor->status !== VendorStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'vendor_id' => 'The selected vendor must be active.',
                ]);
            }
        }

        $previousStatus = $purchaseOrder->status->value;

        DB::transaction(function () use ($request, $purchaseOrder, $previousStatus) {
            $data = $request->except(['status', 'payment_status', 'expected_at']);

            $purchaseOrder->fill($data);

            if ($request->filled('status')) {
                $purchaseOrder->transitionStatusTo(VendorOrderStatus::from($request->input('status')));
            }

            if ($request->has('expected_at')) {
                $expectedAt = $request->input('expected_at');
                $purchaseOrder->changeExpectedAt($expectedAt ? Carbon::parse($expectedAt) : null);
            }

            $purchaseOrder->total_amount_minor = $purchaseOrder->calculateTotalAmount();
            $purchaseOrder->updated_by_user_id = Auth::id();
            $purchaseOrder->save();

            DB::afterCommit(function () use ($purchaseOrder, $previousStatus) {
                event(new AuditEvent('purchase_orders.updated', Auth::user(), [
                    'public_id' => $purchaseOrder->public_id,
                    'vendor_id' => $purchaseOrder->vendor_id,
                    'previous_status' => $previousStatus,
                    'new_status' => $purchaseOrder->status->value,
                    'payment_status' => $purchaseOrder->payment_status->value,
                    'subtotal_amount_minor' => $purchaseOrder->subtotal_amount_minor,
                    'tax_amount_minor' => $purchaseOrder->tax_amount_minor,
                    'shipping_amount_minor' => $purchaseOrder->shipping_amount_minor,
                    'discount_amount_minor' => $purchaseOrder->discount_amount_minor,
                    'total_amount_minor' => $purchaseOrder->total_amount_minor,
                    'actor_id' => Auth::id(),
                    'updated_at' => $purchaseOrder->updated_at->toIso8601String(),
                ]));
            });
        });

        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return redirect()->route('admin.purchases.show', $purchaseOrder->public_id)
                ->with('success', "Purchase order [{$purchaseOrder->public_id}] updated successfully.");
        }

        return response()->json($purchaseOrder);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VendorOrder $purchaseOrder, Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $purchaseOrder);

        if ($purchaseOrder->status !== VendorOrderStatus::DRAFT) {
            abort(400, 'Only draft purchase orders can be deleted.');
        }

        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->delete();

            DB::afterCommit(function () use ($purchaseOrder) {
                event(new AuditEvent('purchase_orders.deleted', Auth::user(), [
                    'public_id' => $purchaseOrder->public_id,
                    'vendor_id' => $purchaseOrder->vendor_id,
                    'actor_id' => Auth::id(),
                ]));
            });
        });

        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return redirect()->route('admin.purchases.index')
                ->with('success', 'Purchase order deleted successfully.');
        }

        return response()->json(['message' => 'Purchase order deleted successfully.']);
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(UpdateVendorOrderStatusRequest $request, VendorOrder $purchaseOrder): JsonResponse|RedirectResponse
    {
        $targetStatus = $request->input('status');
        if ($targetStatus === 'confirmed' || $targetStatus === 'approved') {
            $targetStatus = 'ordered';
        }

        if ($targetStatus === 'ordered') {
            Gate::authorize('approve', $purchaseOrder);
        } elseif ($targetStatus === 'cancelled') {
            Gate::authorize('cancel', $purchaseOrder);
        } else {
            Gate::authorize('update', $purchaseOrder);
        }

        try {
            $updatedOrder = $this->statusService->transition(
                order: $purchaseOrder,
                targetStatus: $targetStatus,
                actor: $request->user(),
                targetPaymentStatus: $request->input('payment_status')
            );

            if (! $request->expectsJson() && ! $request->is('api/*')) {
                $statusLabel = $updatedOrder->status === VendorOrderStatus::ORDERED ? 'Confirmed & Ordered' : $updatedOrder->status->label();
                return redirect()->route('admin.purchases.show', $updatedOrder->public_id)
                    ->with('success', "Purchase order [{$updatedOrder->public_id}] marked as {$statusLabel}.");
            }

            return response()->json($updatedOrder);
        } catch (InvalidPurchaseOrderStatusTransitionException $e) {
            throw ValidationException::withMessages([
                'status' => $e->getMessage(),
            ]);
        } catch (InvalidPurchaseOrderPaymentStatusTransitionException $e) {
            throw ValidationException::withMessages([
                'payment_status' => $e->getMessage(),
            ]);
        }
    }
}
