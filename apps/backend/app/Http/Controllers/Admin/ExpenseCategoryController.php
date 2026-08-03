<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateExpenseCategoryRequest;
use App\Http\Requests\Admin\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection|RedirectResponse
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        $categories = ExpenseCategory::query()
            ->orderBy('name', 'asc')
            ->paginate(20);

        if ($request->expectsJson() || $request->is('api/*')) {
            return ExpenseCategoryResource::collection($categories);
        }

        return redirect()->route('admin.expenses.index');
    }

    public function store(CreateExpenseCategoryRequest $request): JsonResponse|ExpenseCategoryResource|RedirectResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        $category = DB::transaction(function () use ($request) {
            $cat = ExpenseCategory::create($request->validated());

            DB::afterCommit(function () use ($cat, $request) {
                event(new AuditEvent('expense_categories.created', $request->user(), [
                    'category_id' => $cat->id,
                    'public_id' => $cat->public_id,
                    'code' => $cat->code,
                    'name' => $cat->name,
                    'actor_id' => $request->user()?->id,
                ]));
            });

            return $cat;
        });

        if ($request->expectsJson() || $request->is('api/*')) {
            return (new ExpenseCategoryResource($category))
                ->response()
                ->setStatusCode(201);
        }

        return redirect()->back()->with('success', "Expense category [{$category->name}] created successfully.");
    }

    public function show(Request $request, ExpenseCategory $expenseCategory): ExpenseCategoryResource
    {
        $this->authorize('view', $expenseCategory);

        return new ExpenseCategoryResource($expenseCategory);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): ExpenseCategoryResource|RedirectResponse
    {
        $this->authorize('update', $expenseCategory);

        DB::transaction(function () use ($request, $expenseCategory) {
            $oldName = $expenseCategory->name;
            $expenseCategory->update($request->validated());

            DB::afterCommit(function () use ($expenseCategory, $oldName, $request) {
                event(new AuditEvent('expense_categories.updated', $request->user(), [
                    'category_id' => $expenseCategory->id,
                    'public_id' => $expenseCategory->public_id,
                    'code' => $expenseCategory->code,
                    'old_name' => $oldName,
                    'name' => $expenseCategory->name,
                    'is_active' => $expenseCategory->is_active,
                    'actor_id' => $request->user()?->id,
                ]));
            });
        });

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseCategoryResource($expenseCategory);
        }

        return redirect()->back()->with('success', "Expense category [{$expenseCategory->name}] updated successfully.");
    }

    public function toggleActive(Request $request, ExpenseCategory $expenseCategory): ExpenseCategoryResource|RedirectResponse
    {
        $this->authorize('toggleActive', $expenseCategory);

        DB::transaction(function () use ($expenseCategory, $request) {
            $wasActive = $expenseCategory->is_active;
            $expenseCategory->is_active = ! $wasActive;
            $expenseCategory->save();

            DB::afterCommit(function () use ($expenseCategory, $wasActive, $request) {
                event(new AuditEvent('expense_categories.updated', $request->user(), [
                    'category_id' => $expenseCategory->id,
                    'public_id' => $expenseCategory->public_id,
                    'code' => $expenseCategory->code,
                    'is_active' => [
                        'before' => $wasActive,
                        'after' => $expenseCategory->is_active,
                    ],
                    'actor_id' => $request->user()?->id,
                ]));
            });
        });

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ExpenseCategoryResource($expenseCategory);
        }

        $statusStr = $expenseCategory->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Expense category [{$expenseCategory->name}] {$statusStr} successfully.");
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $expenseCategory);

        // Option A Protection: block soft deletion if referenced by any active or soft-deleted expense
        $expenseCategory->ensureNotReferenced();

        $categoryId = $expenseCategory->id;
        $categoryName = $expenseCategory->name;

        DB::transaction(function () use ($expenseCategory) {
            $expenseCategory->delete();
        });

        DB::afterCommit(function () use ($categoryId, $categoryName, $request) {
            event(new AuditEvent('expense_categories.deleted', $request->user(), [
                'category_id' => $categoryId,
                'name' => $categoryName,
                'actor_id' => $request->user()?->id,
            ]));
        });

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Expense category soft deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', "Expense category [{$categoryName}] soft deleted successfully.");
    }
}
