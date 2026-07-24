<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryLocation;
use App\Enums\WarehouseTransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreWarehouseTransferRequest;
use App\Models\ProductSku;
use App\Models\WarehouseTransfer;
use App\Services\WarehouseTransferService;
use App\Support\Inventory\Transfers\WarehouseTransferCatalog;
use App\Support\Inventory\Transfers\WarehouseTransferFilters;
use App\Support\Inventory\Transfers\WarehouseTransferMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WarehouseTransferController extends Controller
{
    public function __construct(
        protected WarehouseTransferCatalog $catalog,
        protected WarehouseTransferService $transferService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('inventory.view');

        $filters = new WarehouseTransferFilters($request->all());
        $metrics = new WarehouseTransferMetrics($filters);
        $transfers = $this->catalog->getPaginatedTransfers($filters, 25);
        $locations = InventoryLocation::cases();
        $statuses = WarehouseTransferStatus::cases();

        return view('admin.inventory.transfers.index', [
            'filters' => $filters,
            'metrics' => $metrics,
            'transfers' => $transfers,
            'locations' => $locations,
            'statuses' => $statuses,
        ]);
    }

    public function create()
    {
        Gate::authorize('inventory.manage');

        $skus = ProductSku::with('product')->where('track_stock', true)->orderBy('sku_code')->get();
        $locations = InventoryLocation::cases();

        return view('admin.inventory.transfers.create', [
            'skus' => $skus,
            'locations' => $locations,
        ]);
    }

    public function store(StoreWarehouseTransferRequest $request): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $sku = ProductSku::findOrFail($request->integer('product_sku_id'));

        $transfer = $this->transferService->initiateTransfer(
            sku: $sku,
            sourceLocation: InventoryLocation::from($request->string('source_location')),
            destinationLocation: InventoryLocation::from($request->string('destination_location')),
            quantity: $request->integer('quantity'),
            actor: $request->user(),
            notes: $request->input('notes')
        );

        return redirect()->route('admin.inventory.transfers.show', $transfer)
            ->with('success', "Warehouse transfer record [{$transfer->transfer_code}] created in DRAFT status.");
    }

    public function show(WarehouseTransfer $transfer)
    {
        Gate::authorize('inventory.view');

        $transfer->load(['productSku.product', 'initiator', 'completer']);

        return view('admin.inventory.transfers.show', [
            'transfer' => $transfer,
        ]);
    }

    public function ship(Request $request, WarehouseTransfer $transfer): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $idempotencyKey = $request->input('idempotency_key') ?: "ship_{$transfer->id}_".time();

        $shipped = $this->transferService->shipTransfer(
            transfer: $transfer,
            idempotencyKey: $idempotencyKey,
            actor: $request->user()
        );

        return redirect()->back()
            ->with('success', "Warehouse transfer [{$shipped->transfer_code}] successfully dispatched IN TRANSIT.");
    }

    public function receive(Request $request, WarehouseTransfer $transfer): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $idempotencyKey = $request->input('idempotency_key') ?: "receive_{$transfer->id}_".time();

        $completed = $this->transferService->receiveTransfer(
            transfer: $transfer,
            idempotencyKey: $idempotencyKey,
            actor: $request->user()
        );

        return redirect()->back()
            ->with('success', "Warehouse transfer [{$completed->transfer_code}] successfully received and COMPLETED.");
    }

    public function cancel(Request $request, WarehouseTransfer $transfer): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $cancelled = $this->transferService->cancelTransfer(
            transfer: $transfer,
            actor: $request->user(),
            reasonNotes: $request->input('reason')
        );

        return redirect()->back()
            ->with('success', "Warehouse transfer [{$cancelled->transfer_code}] has been CANCELLED.");
    }
}
