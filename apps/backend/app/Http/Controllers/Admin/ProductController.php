<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use App\Support\Admin\ProductIndexCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Product::class);

        $catalog = app(ProductIndexCatalog::class);
        $definition = $catalog->definition();

        $activeFilters = $request->only([
            'search',
            'status',
            'visibility',
            'product_type',
            'sort',
            'direction',
            'per_page',
        ]);

        $products = $catalog->query($activeFilters)->paginate(
            $activeFilters['per_page'] ?? $definition['per_page']
        )->withQueryString();

        $activeFilters['per_page'] = $products->perPage();

        return view('admin.products.index', [
            'products' => $products,
            'definition' => $definition,
            'activeFilters' => $activeFilters,
        ]);
    }

    public function edit(Product $product)
    {
        Gate::authorize('update', $product);

        $product->load([
            'variants',
            'skus'  => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'media.file',
        ]);

        $categories = ProductCategory::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->productService->update($product, $request->validated(), $request->user());

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product updated successfully.');
    }
}
