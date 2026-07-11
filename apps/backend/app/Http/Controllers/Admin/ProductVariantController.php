<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductVariantController extends Controller
{
    public function __construct(
        protected ProductVariantService $variantService,
    ) {}

    /**
     * Store a newly created variant in storage.
     */
    public function store(StoreProductVariantRequest $request, Product $product)
    {
        $this->variantService->store($product, $request->validated(), $request->user());

        return redirect()->route('admin.products.edit', [$product, 'tab' => 'variants'])
            ->with('success', 'Variant option created successfully.');
    }

    /**
     * Update the specified variant in storage.
     */
    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant)
    {
        $this->variantService->update($variant, $request->validated(), $request->user());

        return redirect()->route('admin.products.edit', [$product, 'tab' => 'variants'])
            ->with('success', 'Variant option updated successfully.');
    }

    /**
     * Remove the specified variant from storage.
     */
    public function destroy(Product $product, ProductVariant $variant, Request $request)
    {
        Gate::authorize('update', $product);

        $this->variantService->destroy($variant, $request->user());

        return redirect()->route('admin.products.edit', [$product, 'tab' => 'variants'])
            ->with('success', 'Variant option deleted successfully.');
    }
}
