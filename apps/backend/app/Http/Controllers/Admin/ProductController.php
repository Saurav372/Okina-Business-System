<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Admin\ProductIndexCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
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
}
