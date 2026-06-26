<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateExpenseCategoryRequest;
use App\Http\Requests\Admin\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ExpenseCategory::class);

        $categories = ExpenseCategory::query()
            ->orderBy('name', 'asc')
            ->paginate(20);

        return ExpenseCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateExpenseCategoryRequest $request): ExpenseCategoryResource
    {
        Gate::authorize('create', ExpenseCategory::class);

        $category = ExpenseCategory::create($request->validated());

        return new ExpenseCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseCategory $expenseCategory): ExpenseCategoryResource
    {
        Gate::authorize('view', $expenseCategory);

        return new ExpenseCategoryResource($expenseCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): ExpenseCategoryResource
    {
        Gate::authorize('update', $expenseCategory);

        // Note: Code is immutable and not present in UpdateExpenseCategoryRequest's rules,
        // so it will not be passed to $request->validated().
        $expenseCategory->update($request->validated());

        return new ExpenseCategoryResource($expenseCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws ValidationException
     */
    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        Gate::authorize('delete', $expenseCategory);

        if ($expenseCategory->isReferenced()) {
            throw ValidationException::withMessages([
                'category' => ['Expense category is referenced by existing expenses.'],
            ]);
        }

        $expenseCategory->delete();

        return response()->json([
            'message' => 'Expense category soft deleted successfully.',
        ]);
    }
}
