<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveExpenseRequest;
use App\Http\Requests\Admin\RejectExpenseRequest;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\SubmitExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = Expense::query()
            ->with(['expenseCategory', 'recordedBy'])
            ->orderBy('occurred_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return ExpenseResource::collection($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request): ExpenseResource
    {
        $this->authorize('create', Expense::class);

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

        return DB::transaction(function () use ($request, $category) {
            $expense = new Expense;
            $expense->expense_category_id = $category->id;
            $expense->amount_minor = $this->amountToMinorUnits($request->amount);
            $expense->currency = $request->input('currency', 'INR');
            $expense->notes = $request->notes;
            $expense->reference = $request->reference;
            $expense->status = $request->input('status', Expense::STATUS_DRAFT);
            $expense->occurred_at = $request->occurred_at;
            $expense->recorded_by_user_id = $request->user()->id;
            $expense->save();

            $expense->load(['expenseCategory', 'recordedBy']);

            return new ExpenseResource($expense);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expense);

        $expense->load(['expenseCategory', 'recordedBy']);

        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $this->authorize('update', $expense);

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

        DB::transaction(function () use ($request, $expense, $category) {
            if ($category) {
                $expense->expense_category_id = $category->id;
            }

            if ($request->has('amount')) {
                $expense->amount_minor = $this->amountToMinorUnits($request->amount);
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

            if ($request->has('status')) {
                $expense->status = $request->status;
            }

            if ($request->has('occurred_at')) {
                $expense->occurred_at = $request->occurred_at;
            }

            $expense->save();
        });

        $expense->load(['expenseCategory', 'recordedBy']);

        return new ExpenseResource($expense);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): Response
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return response()->noContent();
    }

    /**
     * Submit the expense for approval.
     */
    public function submit(SubmitExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $this->authorize('submit', $expense);

        return DB::transaction(function () use ($request, $expense) {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            try {
                $expense->submit($request->user());
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'status' => [$e->getMessage()],
                ]);
            }

            $expense->load(['expenseCategory', 'recordedBy']);

            return new ExpenseResource($expense);
        });
    }

    /**
     * Approve the expense.
     */
    public function approve(ApproveExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $this->authorize('approve', $expense);

        return DB::transaction(function () use ($request, $expense) {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            try {
                $expense->approve($request->user());
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'status' => [$e->getMessage()],
                ]);
            }

            $expense->load(['expenseCategory', 'recordedBy']);

            return new ExpenseResource($expense);
        });
    }

    /**
     * Reject the expense.
     */
    public function reject(RejectExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $this->authorize('reject', $expense);

        return DB::transaction(function () use ($request, $expense) {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            try {
                $expense->reject($request->user(), $request->rejection_reason);
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'status' => [$e->getMessage()],
                ]);
            }

            $expense->load(['expenseCategory', 'recordedBy']);

            return new ExpenseResource($expense);
        });
    }

    /**
     * Centralized amount minor conversion helper.
     */
    protected function amountToMinorUnits(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
