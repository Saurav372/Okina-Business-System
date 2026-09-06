<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryLocation;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryStatus;
use App\Exceptions\StaleInventoryBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Models\ProductSku;
use App\Services\InventoryBalanceService;
use App\Support\Inventory\InventoryDashboardMetrics;
use App\Support\Inventory\StockBalanceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        protected StockBalanceCatalog $catalog,
        protected InventoryDashboardMetrics $metricsProvider,
        protected InventoryBalanceService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('inventory.view');

        $rawFilters = $request->only(['search', 'status', 'location', 'sort_by', 'sort_order']);
        $metrics = $this->metricsProvider->getMetrics($rawFilters['location'] ?? null);
        $items = $this->catalog->getPaginatedBalances($rawFilters);
        $reasons = InventoryMovementReason::cases();
        $locations = InventoryLocation::cases();
        $statuses = InventoryStatus::cases();

        return view('admin.inventory.index', [
            'metrics' => $metrics,
            'items' => $items,
            'filters' => (object) [
                'search' => $rawFilters['search'] ?? '',
                'status' => isset($rawFilters['status']) && $rawFilters['status'] !== 'all' ? InventoryStatus::tryFrom($rawFilters['status']) : null,
                'location' => isset($rawFilters['location']) && $rawFilters['location'] !== 'all' ? InventoryLocation::tryFrom($rawFilters['location']) : null,
                'sortBy' => $rawFilters['sort_by'] ?? 'available_quantity',
                'sortOrder' => $rawFilters['sort_order'] ?? 'desc',
            ],
            'reasons' => $reasons,
            'locations' => $locations,
            'statuses' => $statuses,
        ]);
    }

    public function adjust(AdjustStockRequest $request, ProductSku $sku): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $validated = $request->validated();
        $reason = InventoryMovementReason::from($validated['reason_code']);

        try {
            $resultDto = $this->inventoryService->adjustWithExpectedBalance(
                sku: $sku,
                expectedOnHand: (int) $validated['expected_on_hand'],
                newOnHand: (int) $validated['new_on_hand'],
                newReserved: (int) $validated['new_reserved'],
                reason: $reason,
                options: [
                    'notes' => $validated['notes'] ?? null,
                    'created_by_user_id' => $request->user()?->id,
                ]
            );

            return redirect()->back()->with('success', $resultDto->getSummaryText());
        } catch (StaleInventoryBalanceException $e) {
            return redirect()->back()
                ->withInput()
                ->with('adjustment_sku_id', $sku->id)
                ->withErrors([
                    'expected_on_hand' => $e->getMessage(),
                ]);
        }
    }
}
