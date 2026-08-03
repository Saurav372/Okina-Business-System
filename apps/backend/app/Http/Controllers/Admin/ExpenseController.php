<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseReportingService;
use App\Services\ExpenseService;
use App\Support\Expenses\ExpenseCatalog;
use App\Support\Expenses\ExpenseFilters;
use App\Support\Money\MoneyParser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ExpenseService $expenseService,
        protected ExpenseReportingService $reportingService,
        protected ExpenseCatalog $catalog
    ) {}

    /**
     * Display a listing of operational expenses (Dashboard Blade view & JSON API).
     */
    public function index(Request $request): AnonymousResourceCollection|View
    {
        $this->authorize('viewAny', Expense::class);

        $filters = new ExpenseFilters($request->all());
        $expenses = $this->catalog->getPaginatedExpenses($filters, $filters->perPage);

        if ($request->expectsJson() || $request->is('api/*')) {
            return ExpenseResource::collection($expenses);
        }

        // Blade view: compute metrics and category list for dashboard
        $metrics = $this->reportingService->generateSummary($filters);
        $categories = ExpenseCategory::query()->orderBy('name')->get();

        // Server-side action resolution for modal recovery
        $modalState = [
            'expense_modal_mode' => old('expense_modal_mode', 'create'),
            'edit_expense_id' => old('edit_expense_id', ''),
            'category_modal_mode' => old('category_modal_mode', 'create'),
            'edit_category_id' => old('edit_category_id', ''),
        ];

        return view('admin.expenses.index', [
            'filters' => $filters,
            'metrics' => $metrics,
            'expenses' => $expenses,
            'categories' => $categories,
            'statuses' => ExpenseStatus::cases(),
            'modalState' => $modalState,
        ]);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(StoreExpenseRequest $request): JsonResponse|ExpenseResource|RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $validated = $request->validated();
        $amountMinor = MoneyParser::toMinorUnits((string) $validated['amount']);

        $expense = $this->expenseService->createExpense([
            'expense_category_public_id' => $validated['expense_category_public_id'],
            'amount_minor' => $amountMinor,
            'currency' => $validated['currency'] ?? 'INR',
            'notes' => $validated['notes'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'occurred_at' => $validated['occurred_at'],
            'status' => $validated['status'] ?? Expense::STATUS_DRAFT,
        ], $request->user(), $request->file('proof_file'));

        if ($request->expectsJson() || $request->is('api/*')) {
            return (new ExpenseResource($expense))
                ->response()
                ->setStatusCode(201);
        }

        return redirect()->route('admin.expenses.index')
            ->with('success', "Expense [{$expense->public_id}] created successfully.");
    }

    /**
     * Display the specified expense resource.
     */
    public function show(Request $request, Expense $expense): ExpenseResource|View
    {
        $this->authorize('view', $expense);

        $expense->load(['expenseCategory', 'recordedBy', 'attachment']);

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return view('admin.expenses.show', [
            'expense' => $expense,
        ]);
    }

    /**
     * Update the specified expense resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        $this->authorize('update', $expense);

        $validated = $request->validated();
        $attributes = [];

        if (array_key_exists('expense_category_public_id', $validated)) {
            $attributes['expense_category_public_id'] = $validated['expense_category_public_id'];
        }

        if (array_key_exists('amount', $validated)) {
            $attributes['amount_minor'] = MoneyParser::toMinorUnits((string) $validated['amount']);
        }

        if (array_key_exists('currency', $validated)) {
            $attributes['currency'] = $validated['currency'];
        }

        if (array_key_exists('notes', $validated)) {
            $attributes['notes'] = $validated['notes'];
        }

        if (array_key_exists('reference', $validated)) {
            $attributes['reference'] = $validated['reference'];
        }

        if (array_key_exists('occurred_at', $validated)) {
            $attributes['occurred_at'] = $validated['occurred_at'];
        }

        $updatedExpense = $this->expenseService->updateExpense(
            $expense,
            $attributes,
            $request->user(),
            $request->file('proof_file')
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseResource($updatedExpense);
        }

        return redirect()->route('admin.expenses.index')
            ->with('success', "Expense [{$updatedExpense->public_id}] updated successfully.");
    }

    /**
     * Remove the specified expense resource from storage (soft delete).
     */
    public function destroy(Request $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $expense);

        $this->expenseService->deleteExpense($expense, $request->user());

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Expense deleted successfully.']);
        }

        return redirect()->route('admin.expenses.index')
            ->with('success', "Expense [{$expense->public_id}] deleted successfully.");
    }
}
