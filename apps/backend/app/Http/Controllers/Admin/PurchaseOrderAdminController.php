<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\ReceivePurchaseOrderRequest;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\PurchaseReceivingService;
use App\Support\Purchases\PurchaseOrderCatalog;
use App\Support\Purchases\PurchaseOrderFilters;
use App\Support\Purchases\PurchaseOrderMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseOrderAdminController extends Controller
{
    public function __construct(
        protected PurchaseOrderCatalog $catalog,
        protected PurchaseReceivingService $receivingService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', VendorOrder::class);

        $filters = new PurchaseOrderFilters($request->all());
        $metrics = new PurchaseOrderMetrics($filters);
        $orders = $this->catalog->getPaginatedOrders($filters, 25);
        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();

        return view('admin.purchases.index', [
            'filters' => $filters,
            'metrics' => $metrics,
            'orders' => $orders,
            'vendors' => $vendors,
            'statuses' => VendorOrderStatus::cases(),
            'paymentStatuses' => VendorOrderPaymentStatus::cases(),
        ]);
    }

    public function show(VendorOrder $vendorOrder): View
    {
        Gate::authorize('view', $vendorOrder);

        $vendorOrder->load([
            'vendor',
            'creator',
            'updater',
            'items.productSku.product',
            'payments.recordedBy',
        ]);

        return view('admin.purchases.show', [
            'order'          => $vendorOrder,
            'statuses'       => VendorOrderStatus::cases(),
            'paymentStatuses' => VendorOrderPaymentStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', VendorOrder::class);

        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();

        return view('admin.purchases.create', [
            'vendors' => $vendors,
        ]);
    }

    public function receive(ReceivePurchaseOrderRequest $request, VendorOrder $vendorOrder): RedirectResponse
    {
        Gate::authorize('update', $vendorOrder);

        $validated = $request->validated();

        $result = $this->receivingService->receive(
            order: $vendorOrder,
            items: $validated['items'],
            idempotencyKey: $validated['idempotency_key'],
            actor: $request->user(),
            notes: $validated['notes'] ?? null
        );

        return redirect()->back()->with('success', "Goods receipt batch [{$result['batch_code']}] successfully processed. Received {$result['received_count']} total stock units.");
    }
}
