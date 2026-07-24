<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Models\ProductSku;
use App\Services\InventoryMovementCsvExporter;
use App\Support\Inventory\InventoryMovementCatalog;
use App\Support\Inventory\InventoryMovementFilters;
use App\Support\Inventory\InventoryMovementMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryMovementController extends Controller
{
    public function __construct(
        protected InventoryMovementCatalog $catalog,
        protected InventoryMovementCsvExporter $csvExporter
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('inventory.view');

        $filters = new InventoryMovementFilters($request->all());
        $metrics = new InventoryMovementMetrics($filters);
        $movements = $this->catalog->getPaginatedMovements($filters, 25);

        $selectedSku = $filters->skuId ? ProductSku::with('product')->find($filters->skuId) : null;

        return view('admin.inventory.movements', [
            'filters' => $filters,
            'metrics' => $metrics,
            'movements' => $movements,
            'selectedSku' => $selectedSku,
            'movementTypes' => InventoryMovementType::cases(),
            'directions' => InventoryDirection::cases(),
            'reasons' => InventoryMovementReason::cases(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('inventory.view');

        $filters = new InventoryMovementFilters($request->all());

        return $this->csvExporter->export($filters);
    }
}
