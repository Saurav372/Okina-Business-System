<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductSkuRequest;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\ProductSkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductSkuController extends Controller
{
    public function __construct(
        protected ProductSkuService $skuService,
    ) {}

    /**
     * Generate the full Cartesian SKU matrix for the product.
     *
     * Scoped bindings verify the product belongs to the admin route group.
     * Gate::authorize enforces the update policy check.
     */
    public function generate(Product $product, Request $request)
    {
        Gate::authorize('update', $product);

        $this->skuService->generateMatrix($product, $request->user());

        return redirect()->route('admin.products.edit', [$product, 'tab' => 'variants'])
            ->with('success', 'SKU combinations generated successfully.');
    }

    /**
     * Update an existing SKU's editable fields.
     *
     * Ownership (SKU belongs to product) is verified natively by
     * scopeBindings() on the route; no redundant manual check is needed.
     */
    public function update(UpdateProductSkuRequest $request, Product $product, ProductSku $sku)
    {
        $this->skuService->update($sku, $request->validated(), $request->user());

        return redirect()->route('admin.products.edit', [$product, 'tab' => 'variants'])
            ->with('success', 'SKU details updated successfully.');
    }

    /**
     * Delete an existing SKU.
     *
     * Scoped bindings on the route ensure the SKU belongs to the product.
     * An additional Gate::authorize enforces the update policy check.
     */
    public function destroy(Product $product, ProductSku $sku, Request $request)
    {
        Gate::authorize('update', $product);

        $this->skuService->destroy($sku, $request->user());

        return redirect()->route('admin.products.edit', [$product, 'tab' => 'variants'])
            ->with('success', 'SKU configuration deleted successfully.');
    }
}
