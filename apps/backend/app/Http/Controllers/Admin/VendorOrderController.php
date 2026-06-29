<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Exceptions\PurchaseOrderImmutableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StoreVendorOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdateVendorOrderRequest;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Support\Purchases\PurchaseOrderCodeGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VendorOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', VendorOrder::class);

        $query = VendorOrder::query()
            ->with(['vendor:id,name,vendor_code']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('public_id', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate($request->integer('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVendorOrderRequest $request): JsonResponse
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

        return response()->json($purchaseOrder, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(VendorOrder $purchaseOrder): JsonResponse
    {
        Gate::authorize('view', $purchaseOrder);

        $purchaseOrder->load(['vendor:id,name,vendor_code', 'items.productSku']);

        return response()->json($purchaseOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVendorOrderRequest $request, VendorOrder $purchaseOrder): JsonResponse
    {
        Gate::authorize('update', $purchaseOrder);

        $isOrdered = $purchaseOrder->status !== VendorOrderStatus::DRAFT;

        if ($isOrdered) {
            // Check for edits to immutable fields
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

            if ($request->filled('payment_status')) {
                $purchaseOrder->transitionPaymentStatusTo(VendorOrderPaymentStatus::from($request->input('payment_status')));
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

        return response()->json($purchaseOrder);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VendorOrder $purchaseOrder): JsonResponse
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

        return response()->json(['message' => 'Purchase order deleted successfully.']);
    }
}
