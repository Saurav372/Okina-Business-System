<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CustomizationOptionContract;
use App\Contracts\PublicCatalogContract;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    public function __construct(
        private readonly PublicCatalogContract $rules,
        private readonly CustomizationOptionContract $customizationRules,
    ) {}

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => $this->rules->categories(),
            'guidance' => $this->rules->guidance(),
        ]);
    }

    public function categoryProducts(ProductCategory $category): JsonResponse
    {
        if (! ($category->isPubliclyVisible())) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'category' => $this->rules->category($category->slug),
                'products' => $this->rules->categoryProducts($category->slug),
            ],
            'guidance' => $this->rules->guidance(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $categorySlug = $request->string('category')->trim()->toString();
        $products = $this->rules->products();

        if ($categorySlug !== '') {
            $products = array_values(array_filter($products, fn (array $product): bool => ($product['category']['slug'] ?? null) === $categorySlug));
        }

        return response()->json([
            'data' => $products,
            'guidance' => $this->rules->guidance(),
        ]);
    }

    public function product(Product $product): JsonResponse
    {
        if (! ($product->isPubliclyVisible())) {
            abort(404);
        }

        return response()->json([
            'data' => $this->rules->product($product->slug),
            'guidance' => $this->rules->guidance(),
        ]);
    }

    public function customizationOptions(Product $product): JsonResponse
    {
        if (! ($product->isPubliclyVisible())) {
            abort(404);
        }

        return response()->json([
            'data' => $this->customizationRules->product($product->slug),
            'guidance' => $this->customizationRules->guidance(),
        ]);
    }
}
