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
use App\Models\Vendor;
use App\Models\VendorOrder;
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
                    $data = $request->validated();
                    $data['public_id'] = PurchaseOrderCodeGenerator::generate();
                    $data['status'] = VendorOrderStatus::DRAFT->value;
                    $data['payment_status'] = VendorOrderPaymentStatus::UNPAID->value;
                    $data['created_by_user_id'] = Auth::id();

                    $po = new VendorOrder($data);
                    $po->total_amount_minor = $po->calculateTotalAmount();
                    $po->save();

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
        if ($request->input('status') === 'approved' || $request->input('status') === 'ordered') {
            Gate::authorize('approve', $purchaseOrder);
        } elseif ($request->input('status') === 'cancelled') {
            Gate::authorize('cancel', $purchaseOrder);
        } else {
            Gate::authorize('update', $purchaseOrder);
        }

        try {
            $updatedOrder = $this->statusService->transition(
                order: $purchaseOrder,
                targetStatus: $request->input('status'),
                actor: $request->user(),
                targetPaymentStatus: $request->input('payment_status')
            );

            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return redirect()->route('admin.purchases.show', $updatedOrder->public_id)
                    ->with('success', "Purchase order status updated to {$updatedOrder->status->value}.");
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
