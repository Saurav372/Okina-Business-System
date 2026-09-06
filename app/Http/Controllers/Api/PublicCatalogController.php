<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CustomizationOptionContract;
use App\Contracts\PublicCatalogContract;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductMedia;
use App\Models\StoredFile;
use App\Services\FileUploadService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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

    public function storefront(SettingsService $settings): JsonResponse
    {
        return response()->json([
            'data' => [
                'business' => [
                    'company_name' => $settings->get('business', 'company_name', 'Okina Craft'),
                    'support_email' => $settings->get('business', 'support_email'),
                    'support_phone' => $settings->get('business', 'support_phone'),
                    'default_currency' => $settings->get('business', 'default_currency', 'INR'),
                    'tax_inclusive_pricing' => (bool) $settings->get('business', 'tax_inclusive_pricing', false),
                ],
                'checkout' => [
                    'online_payments_enabled' => (bool) $settings->get('payment', 'online_payments_enabled', true),
                    'cod_enabled' => (bool) $settings->get('payment', 'cod_enabled', false),
                    'default_gateway' => $settings->get('payment', 'default_gateway', 'cashfree'),
                ],
                'seo' => [
                    'site_title' => $settings->get('seo', 'site_title', 'Okina Craft'),
                    'meta_description' => $settings->get('seo', 'meta_description'),
                    'robots' => [
                        'index' => (bool) $settings->get('seo', 'robots_index', true),
                        'follow' => (bool) $settings->get('seo', 'robots_follow', true),
                    ],
                    'open_graph_image' => $settings->get('seo', 'og_image_path'),
                ],
            ],
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

    public function media(StoredFile $file, FileUploadService $files): Response
    {
        $isPublicProductMedia = $file->visibility === StoredFile::VISIBILITY_PUBLIC_SAFE_PREVIEW
            && $file->status === StoredFile::STATUS_ACTIVE
            && $file->isImage()
            && ProductMedia::query()
                ->where('file_id', $file->id)
                ->whereHas('product', fn ($query) => $query->publiclyVisible())
                ->exists();

        abort_unless($isPublicProductMedia, 404);

        $file = $files->ensurePreview($file);
        $path = $file->previewPath() ?? $file->storage_path;
        $mimeType = $file->previewMimeType() ?? $file->mime_type;

        abort_unless(Storage::disk($file->previewStorageDisk() ?? $file->storage_disk)->exists($path), 404);

        return Storage::disk($file->previewStorageDisk() ?? $file->storage_disk)
            ->response($path, basename($path), [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400, immutable',
                'X-Content-Type-Options' => 'nosniff',
            ], 'inline');
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
