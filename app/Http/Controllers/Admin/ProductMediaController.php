<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductMediaRequest;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\ProductMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductMediaController extends Controller
{
    public function __construct(
        protected ProductMediaService $mediaService,
    ) {}

    /**
     * Upload one or more images and attach them to the product.
     */
    public function store(StoreProductMediaRequest $request, Product $product): RedirectResponse
    {
        $this->mediaService->upload(
            $product,
            $request->file('images'),
            $request->input('alt_text'),
            $request->user(),
        );

        return redirect()
            ->route('admin.products.edit', [$product, 'tab' => 'media'])
            ->with('success', 'Images uploaded successfully.');
    }

    /**
     * Persist the drag-and-drop reorder result.
     *
     * Expects JSON body: { "ids": [5, 3, 8, ...] }
     * All IDs must belong to this product — no more, no fewer.
     * Returns 204 on success, 422 on validation failure.
     */
    public function reorder(Request $request, Product $product): JsonResponse|Response
    {
        Gate::authorize('update', $product);

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer'],
        ]);

        $this->mediaService->reorder($product, $request->input('ids'), $request->user());

        return response()->noContent();
    }

    /**
     * Promote a gallery image to cover for this product.
     */
    public function setCover(Request $request, Product $product, ProductMedia $media): RedirectResponse
    {
        Gate::authorize('update', $product);

        $this->mediaService->setCover($media, $request->user());

        return redirect()
            ->route('admin.products.edit', [$product, 'tab' => 'media'])
            ->with('success', 'Cover image updated.');
    }

    /**
     * Delete a media record and its underlying file.
     */
    public function destroy(Request $request, Product $product, ProductMedia $media): RedirectResponse
    {
        Gate::authorize('update', $product);

        $this->mediaService->destroy($media, $request->user());

        return redirect()
            ->route('admin.products.edit', [$product, 'tab' => 'media'])
            ->with('success', 'Image deleted.');
    }
}
