<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductSeoRequest;
use App\Models\Product;
use App\Services\ProductSeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProductSeoController extends Controller
{
    public function __construct(
        protected ProductSeoService $seoService,
    ) {}

    public function update(UpdateProductSeoRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        // Handle slug update on Product if changed
        if (array_key_exists('slug', $validated) && $validated['slug'] !== $product->slug) {
            $product->update(['slug' => $validated['slug']]);
        }

        // Handle ProductSeo record mutation
        $this->seoService->updateSeo($product, $validated, Auth::user());

        return redirect()
            ->route('admin.products.edit', [$product, 'tab' => 'seo'])
            ->with('success', 'Product SEO metadata updated successfully.');
    }
}
