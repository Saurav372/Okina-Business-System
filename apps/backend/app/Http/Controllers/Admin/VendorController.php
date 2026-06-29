<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVendorRequest;
use App\Http\Requests\Vendor\UpdateVendorRequest;
use App\Models\Vendor;
use App\Support\Vendors\VendorCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Vendor::class);

        $query = Vendor::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('vendor_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $vendors = $query->paginate($request->integer('per_page', 15));

        return response()->json($vendors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        Gate::authorize('create', Vendor::class);

        $data = $request->validated();
        if (empty($data['vendor_code'])) {
            $data['vendor_code'] = VendorCodeGenerator::generate();
        }
        $data['status'] ??= VendorStatus::ACTIVE->value;
        $data['created_by_user_id'] = Auth::id();

        $vendor = Vendor::create($data);

        event(new AuditEvent('vendors.created', Auth::user(), [
            'vendor_id' => $vendor->id,
            'vendor_code' => $vendor->vendor_code,
            'name' => $vendor->name,
        ]));

        return response()->json($vendor, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        Gate::authorize('view', $vendor);

        return response()->json($vendor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        Gate::authorize('update', $vendor);

        $data = $request->validated();
        $data['updated_by_user_id'] = Auth::id();

        $vendor->update($data);

        event(new AuditEvent('vendors.updated', Auth::user(), [
            'vendor_id' => $vendor->id,
            'vendor_code' => $vendor->vendor_code,
            'name' => $vendor->name,
        ]));

        return response()->json($vendor);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        Gate::authorize('delete', $vendor);

        $vendor->delete();

        event(new AuditEvent('vendors.deleted', Auth::user(), [
            'vendor_id' => $vendor->id,
            'vendor_code' => $vendor->vendor_code,
        ]));

        return response()->json(['message' => 'Vendor soft-deleted successfully.']);
    }
}
