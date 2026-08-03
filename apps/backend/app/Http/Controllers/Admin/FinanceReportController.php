<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinanceReportRequest;
use App\Services\FinanceReportService;
use App\Support\Finance\FinanceReportFilters;
use App\Support\Finance\FinanceReportPresenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected FinanceReportService $reportService
    ) {}

    /**
     * Display Finance Reports dashboard view or return JSON for API requests.
     */
    public function index(FinanceReportRequest $request)
    {
        Gate::authorize('reports.finance.view');

        $filters = FinanceReportFilters::fromRequest($request);
        $summary = $this->reportService->generateSummary($filters);
        $presented = FinanceReportPresenter::present($summary);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($presented);
        }

        return view('admin.reports.finance', [
            'filters' => $filters,
            'report' => $presented,
            'presets' => [
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_quarter' => 'This Quarter',
                'current_fiscal_year' => 'Current Fiscal Year',
                'custom' => 'Custom Range',
            ],
            'exportUrl' => route('admin.reports.finance.export', $filters->toQueryArray()),
        ]);
    }

    /**
     * Return JSON summary & analytics endpoint.
     */
    public function summary(FinanceReportRequest $request): JsonResponse
    {
        Gate::authorize('reports.finance.view');

        $filters = FinanceReportFilters::fromRequest($request);
        $summary = $this->reportService->generateSummary($filters);
        $presented = FinanceReportPresenter::present($summary);

        return response()->json($presented);
    }

    /**
     * Stream CSV export download.
     */
    public function export(FinanceReportRequest $request): StreamedResponse
    {
        Gate::authorize('reports.finance.export');

        $filters = FinanceReportFilters::fromRequest($request);

        return $this->reportService->streamCsvExport($filters, $request->user());
    }
}
