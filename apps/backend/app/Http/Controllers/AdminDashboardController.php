<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $metricsService
    ) {}

    /**
     * Display the admin panel dashboard screen.
     */
    public function index(Request $request)
    {
        $widgets = $this->metricsService->getWidgetsData();

        // Calculate if we are in an empty state (0 revenue, 0 active orders, 0 low stock)
        $isEmptyState = true;
        foreach ($widgets as $widget) {
            $cleanedVal = preg_replace('/[^0-9.]/', '', $widget->value);
            if (!empty($cleanedVal) && (float)$cleanedVal > 0) {
                $isEmptyState = false;
                break;
            }
        }

        return view('admin.dashboard', compact('widgets', 'isEmptyState'));
    }
}
