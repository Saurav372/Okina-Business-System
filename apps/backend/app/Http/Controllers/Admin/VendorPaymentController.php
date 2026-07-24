<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorPaymentMethod;
use App\Enums\VendorPaymentStatus;
use App\Exceptions\PurchaseOrderNotPayableException;
use App\Exceptions\PurchaseOrderPaymentLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StoreVendorPaymentRequest;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\VendorPaymentService;
use App\Support\Vendors\VendorPaymentCatalog;
use App\Support\Vendors\VendorPaymentFilters;
use App\Support\Vendors\VendorPaymentMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VendorPaymentController extends Controller
{
    public function __construct(
        protected VendorPaymentService $paymentService,
        protected VendorPaymentCatalog $catalog
    ) {}

    /**
     * Display a listing of vendor payments (Accounts Payable ledger).
     */
    public function index(Request $request): JsonResponse|View
    {
        Gate::authorize('viewAny', VendorOrder::class);

        $filters = new VendorPaymentFilters($request->all());
        $metrics = new VendorPaymentMetrics($filters);
        $payments = $this->catalog->getPaginatedPayments($filters, 15);
        $vendors = Vendor::orderBy('name')->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'metrics' => $metrics,
                'payments' => $payments,
            ]);
        }

        return view('admin.vendor-payments.index', [
            'filters' => $filters,
            'metrics' => $metrics,
            'payments' => $payments,
            'vendors' => $vendors,
            'paymentMethods' => VendorPaymentMethod::cases(),
            'paymentStatuses' => VendorPaymentStatus::cases(),
        ]);
    }

    /**
     * Store a newly created vendor payment in storage.
     */
    public function store(StoreVendorPaymentRequest $request, VendorOrder $purchaseOrder): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $purchaseOrder);

        $validated = $request->validated();

        try {
            $result = $this->paymentService->recordPayment(
                order: $purchaseOrder,
                data: $validated,
                actor: $request->user()
            );
        } catch (PurchaseOrderPaymentLimitExceededException $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                throw ValidationException::withMessages(['amount_minor' => $e->getMessage()]);
            }

            return redirect()->back()->withErrors(['amount_minor' => $e->getMessage()]);
        } catch (PurchaseOrderNotPayableException $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                throw ValidationException::withMessages(['purchase_order' => $e->getMessage()]);
            }

            return redirect()->back()->withErrors(['purchase_order' => $e->getMessage()]);
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'payment' => $result['payment'],
                'purchase_order' => $result['purchase_order'],
            ]);
        }

        $formattedAmount = number_format($result['payment']->amount_minor / 100, 2);

        return redirect()->back()->with('success', "Vendor payment of ₹{$formattedAmount} recorded successfully for PO [{$purchaseOrder->public_id}].");
    }
}
