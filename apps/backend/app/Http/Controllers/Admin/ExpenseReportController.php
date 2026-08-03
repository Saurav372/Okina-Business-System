<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseReportRequest;
use App\Models\Expense;
use App\Services\ExpenseReportingService;
use App\Support\Expenses\ExpenseFilters;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ExpenseReportingService $reportingService
    ) {}

    /**
     * Display a JSON summary report of expenses.
     */
    public function summary(ExpenseReportRequest $request): JsonResponse
    {
        $this->authorize('viewExpenseReports', Expense::class);

        $filters = new ExpenseFilters($request->validated());
        $summary = $this->reportingService->generateSummary($filters);

        return response()->json($summary);
    }

    /**
     * Export expenses as a CSV download with formula injection protection.
     */
    public function export(ExpenseReportRequest $request): StreamedResponse
    {
        $this->authorize('viewExpenseReports', Expense::class);

        $filters = new ExpenseFilters($request->validated());

        return $this->reportingService->streamCsvExport($filters);
    }
}
