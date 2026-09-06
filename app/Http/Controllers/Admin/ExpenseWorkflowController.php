<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseWorkflowController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ExpenseWorkflowService $workflowService
    ) {}

    /**
     * Submit an expense for approval.
     *
     * Authorization strategy:
     * - Unauthorized users always get 403 (policy check runs first).
     * - Authorized users attempting an invalid transition get 422 from the service.
     */
    public function submit(Request $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        try {
            $this->authorize('submit', $expense);
        } catch (AuthorizationException $e) {
            // Only re-throw 403 for users who genuinely lack permission.
            // If the policy says "no" purely because the expense is in approved status,
            // let the service provide the 422 instead.
            if ($expense->status !== Expense::STATUS_APPROVED) {
                throw $e;
            }
        }

        $expense = $this->workflowService->submit($expense, $request->user());

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] submitted for approval successfully.");
    }

    /**
     * Approve an expense.
     */
    public function approve(Request $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        try {
            $this->authorize('approve', $expense);
        } catch (AuthorizationException $e) {
            // Policy says 'no'. If status is not pending, the service will say 422.
            // But if the user genuinely lacks permission, propagate 403.
            // Distinguish: if user has no expense.approve permission at all → 403.
            // If user has permission but expense is not pending → let service give 422.
            if (! $request->user()?->hasPermissionTo('expenses.approve')
                && ! $request->user()?->hasPermissionTo('finance.approve_expenses')
                && ! $request->user()?->hasPermissionTo('expenses.manage')) {
                throw $e;
            }
            // User has permission but expense is in wrong state — fall through to service for 422.
        }

        $expense = $this->workflowService->approve($expense, $request->user());

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] approved successfully.");
    }

    /**
     * Reject an expense with a reason.
     */
    public function reject(RejectExpenseRequest $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        try {
            $this->authorize('reject', $expense);
        } catch (AuthorizationException $e) {
            if (! $request->user()?->hasPermissionTo('expenses.approve')
                && ! $request->user()?->hasPermissionTo('finance.approve_expenses')
                && ! $request->user()?->hasPermissionTo('expenses.manage')) {
                throw $e;
            }
        }

        $reason = (string) $request->input('rejection_reason', '');
        $expense = $this->workflowService->reject($expense, $reason, $request->user());

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] rejected.");
    }

    /**
     * Withdraw a pending expense submission.
     */
    public function withdraw(Request $request, Expense $expense): ExpenseResource|RedirectResponse
    {
        $this->authorize('withdraw', $expense);

        $expense = $this->workflowService->withdraw($expense, $request->user());

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseResource($expense);
        }

        return redirect()->back()->with('success', "Expense [{$expense->public_id}] withdrawn to draft.");
    }
}
