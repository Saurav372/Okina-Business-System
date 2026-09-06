<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVendorRequest;
use App\Http\Requests\Vendor\UpdateVendorRequest;
use App\Models\Vendor;
use App\Support\Vendors\VendorCodeGenerator;
use App\Support\Vendors\VendorFilters;
use App\Support\Vendors\VendorQueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors (supports HTML Blade view and JSON API).
     */
    public function index(Request $request): JsonResponse|View
    {
        Gate::authorize('viewAny', Vendor::class);

        $filters = new VendorFilters($request->all());
        $query = VendorQueryBuilder::buildQuery($filters);
        $vendors = $query->paginate($filters->perPage)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($vendors);
        }

        // Global KPI metrics
        $totalVendors = Vendor::count();
        $activeVendors = Vendor::where('status', VendorStatus::ACTIVE)->count();
        $inactiveVendors = Vendor::where('status', VendorStatus::INACTIVE)->count();
        $blockedVendors = Vendor::where('status', VendorStatus::BLOCKED)->count();

        // Safely resolve editing vendor for validation recovery state
        $editingVendorId = old('edit_vendor_id');
        $editingVendor = null;
        if ($editingVendorId && is_numeric($editingVendorId)) {
            $candidate = Vendor::query()->find((int) $editingVendorId);
            if ($candidate && Gate::allows('update', $candidate)) {
                $editingVendor = $candidate;
            }
        }

        $modalMode = old('modal_mode', $editingVendor ? 'edit' : 'create');
        if ($modalMode === 'edit' && ! $editingVendor) {
            $modalMode = 'create';
        }

        $formAction = $modalMode === 'edit' && $editingVendor
            ? route('admin.vendors.update', $editingVendor)
            : route('admin.vendors.store');

        return view('admin.vendors.index', [
            'vendors' => $vendors,
            'statuses' => VendorStatus::cases(),
            'filters' => $filters,
            'totalVendors' => $totalVendors,
            'activeVendors' => $activeVendors,
            'inactiveVendors' => $inactiveVendors,
            'blockedVendors' => $blockedVendors,
            'modalMode' => $modalMode,
            'editingVendor' => $editingVendor,
            'formAction' => $formAction,
        ]);
    }

    /**
     * Store a newly created vendor in storage.
     */
    public function store(StoreVendorRequest $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Vendor::class);

        $customCode = $request->input('vendor_code');

        if ($customCode) {
            $vendor = DB::transaction(function () use ($request) {
                $data = $request->safe()->except(['modal_mode', 'edit_vendor_id']);
                $data['status'] ??= VendorStatus::ACTIVE->value;
                $data['created_by_user_id'] = Auth::id();

                $vendor = Vendor::create($data);

                $auditPayload = [
                    'vendor_id' => $vendor->id,
                    'vendor_code' => $vendor->vendor_code,
                    'name' => $vendor->name,
                ];
                DB::afterCommit(fn () => event(new AuditEvent('vendors.created', Auth::user(), $auditPayload)));

                return $vendor;
            });
        } else {
            $vendor = VendorCodeGenerator::executeWithRetry(function ($code) use ($request) {
                return DB::transaction(function () use ($request, $code) {
                    $data = $request->safe()->except(['modal_mode', 'edit_vendor_id']);
                    $data['vendor_code'] = $code;
                    $data['status'] ??= VendorStatus::ACTIVE->value;
                    $data['created_by_user_id'] = Auth::id();

                    $vendor = Vendor::create($data);

                    $auditPayload = [
                        'vendor_id' => $vendor->id,
                        'vendor_code' => $vendor->vendor_code,
                        'name' => $vendor->name,
                    ];
                    DB::afterCommit(fn () => event(new AuditEvent('vendors.created', Auth::user(), $auditPayload)));

                    return $vendor;
                });
            });
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vendor created successfully.',
                'data' => $vendor,
            ], 201);
        }

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
    }

    /**
     * Display the specified vendor.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        Gate::authorize('view', $vendor);

        return response()->json($vendor);
    }

    /**
     * Update the specified vendor in storage.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $vendor);

        $beforeValues = $vendor->only([
            'name', 'vendor_code', 'status', 'contact_name', 'email', 'phone',
            'gstin', 'payment_terms', 'address_line1', 'address_line2', 'city',
            'state', 'postal_code', 'country_code', 'notes',
        ]);

        DB::transaction(function () use ($request, $vendor, $beforeValues) {
            $data = $request->safe()->except(['modal_mode', 'edit_vendor_id']);
            if (array_key_exists('vendor_code', $data) && $data['vendor_code'] === null) {
                unset($data['vendor_code']);
            }
            $data['updated_by_user_id'] = Auth::id();

            $vendor->update($data);

            $afterValues = $vendor->only(array_keys($beforeValues));
            $changedFields = [];
            foreach ($beforeValues as $key => $before) {
                $after = $afterValues[$key] ?? null;
                if ($before !== $after) {
                    $changedFields[$key] = ['before' => $before, 'after' => $after];
                }
            }

            $auditPayload = [
                'vendor_id' => $vendor->id,
                'vendor_code' => $vendor->vendor_code,
                'changed_fields' => $changedFields,
            ];
            DB::afterCommit(fn () => event(new AuditEvent('vendors.updated', Auth::user(), $auditPayload)));
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vendor updated successfully.',
                'data' => $vendor->fresh(),
            ]);
        }

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    /**
     * Soft-delete the specified vendor from storage.
     */
    public function destroy(Request $request, Vendor $vendor): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $vendor);

        DB::transaction(function () use ($vendor) {
            $auditPayload = [
                'vendor_id' => $vendor->id,
                'vendor_code' => $vendor->vendor_code,
            ];

            $vendor->delete();

            DB::afterCommit(fn () => event(new AuditEvent('vendors.deleted', Auth::user(), $auditPayload)));
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Vendor deleted successfully.']);
        }

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    /**
     * Get the paginated purchase order history for the specified vendor.
     */
    public function purchaseOrders(Request $request, Vendor $vendor): JsonResponse
    {
        Gate::authorize('view', $vendor);

        $query = $vendor->purchaseOrders()
            ->with(['vendor:id,name,vendor_code', 'creator:id,name'])
            ->filter($request->only(['search', 'status', 'payment_status']))
            ->orderByDesc('id');

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }
}
