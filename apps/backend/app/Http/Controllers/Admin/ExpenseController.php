<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveExpenseRequest;
use App\Http\Requests\Admin\ExpenseReportRequest;
use App\Http\Requests\Admin\RejectExpenseRequest;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\SubmitExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseReportingService;
use App\Services\ExpenseService;
use App\Support\Expenses\ExpenseCatalog;
use App\Support\Expenses\ExpenseFilters;
use App\Support\Expenses\ExpenseMetrics;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ExpenseService $expenseService,
        protected ExpenseCatalog $catalog
    ) {}

    /**
     * Display a listing of operational expenses (Dashboard Blade view & JSON API).
     */
    public function index(Request $request): AnonymousResourceCollection|View
    {
        $this->authorize('viewAny', Expense::class);

        $filters = new ExpenseFilters($request->all());
        $metrics = new ExpenseMetrics($filters);
        $expenses = $this->catalog->getPaginatedExpenses($filters, 15);
        $categories = ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return ExpenseResource::collection($expenses);
        }

        return view('admin.expenses.index', [
            'filters' => $filters,
            'metrics' => $metrics,
            'expenses' => $expenses,
            'categories' => $categories,
            'statuses' => ExpenseStatus::cases(),
        ]);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(StoreExpenseRequest $request): ExpenseResource|RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $validated = $request->validated();
        $amountMinor = $this->amountToMinorUnits((string) ($validated['amount'] ?? '0'));

        $expense = $this->expenseService->createExpense([
            'expense_category_public_id' => $validated['expense_category_public_id'] ?? null,
            'amount_minor' => $amountMinor,
            'currency' => $validated['currency'] ?? 'INR',
            'notes' => $validated['notes'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'occurred_at' => $validated['occurred_at'],
            'status' => $validated['status'] ?? Expense::STATUS_DRAFT,
        ], $request->user());

        if ($request->wantsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] created successfully.");
    }

    /**
     * Display the specified expense resource.
     */
    public function show(Request $request, Expense $expense): ExpenseResource|View
    {
        $this->authorize('view', $expense);

        $expense->load(['expenseCategory', 'recordedBy']);

        if ($request->wantsJson() || $request->is('api/*')) {
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

        if ($expense->status === Expense::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => ['Approved expenses are immutable and cannot be updated.'],
            ]);
        }

        $category = null;
        if ($request->has('expense_category_public_id')) {
            $category = ExpenseCategory::query()
                ->where('public_id', $request->expense_category_public_id)
                ->firstOrFail();

            try {
                $category->ensureCanAssignToExpense();
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'expense_category_public_id' => [$e->getMessage()],
                ]);
            }
        }

        if ($category) {
            $expense->expense_category_id = $category->id;
        }

        if ($request->has('amount')) {
            $expense->amount_minor = $this->amountToMinorUnits((string) $request->amount);
        }

        if ($request->has('currency')) {
            $expense->currency = $request->currency;
        }

        if ($request->has('notes')) {
            $expense->notes = $request->notes;
        }

        if ($request->has('reference')) {
            $expense->reference = $request->reference;
        }

        if ($request->has('occurred_at')) {
            $expense->occurred_at = $request->occurred_at;
        }

        $expense->save();
        $expense->load(['expenseCategory', 'recordedBy']);

        if ($request->wantsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] updated successfully.");
    }

    /**
     * Remove the specified expense resource from storage.
     */
    public function destroy(Request $request, Expense $expense): Response|RedirectResponse
    {
        $this->authorize('delete', $expense);

        if ($expense->status === Expense::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => ['Approved expenses are immutable and cannot be deleted.'],
            ]);
        }

        $expense->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->noContent();
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] deleted successfully.");
    }

    /**
     * Submit the expense for approval.
     */
    public function submit(SubmitExpenseRequest $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        $this->authorize('submit', $expense);

        $expense = $this->expenseService->submitExpense($expense, $request->user());

        if ($request->wantsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] submitted for approval.");
    }

    /**
     * Approve the expense.
     */
    public function approve(ApproveExpenseRequest $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        $this->authorize('approve', $expense);

        $expense = $this->expenseService->approveExpense($expense, $request->user());

        if ($request->wantsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] approved successfully.");
    }

    /**
     * Reject the expense with a reason.
     */
    public function reject(RejectExpenseRequest $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        $this->authorize('reject', $expense);

        $reason = (string) $request->input('rejection_reason', '');
        $expense = $this->expenseService->rejectExpense($expense, $request->user(), $reason);

        if ($request->wantsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] rejected.");
    }

    /**
     * Display a summary report of expenses.
     */
    public function reportSummary(ExpenseReportRequest $request, ExpenseReportingService $reportingService): JsonResponse
    {
        $this->authorize('viewExpenseReports', Expense::class);

        $summary = $reportingService->generateSummary($request->validated());

        return response()->json($summary);
    }

    /**
     * Centralized amount minor conversion helper.
     */
    protected function amountToMinorUnits(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
