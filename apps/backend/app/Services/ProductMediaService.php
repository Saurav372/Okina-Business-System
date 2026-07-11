<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductMediaService
{
    public function __construct(
        protected FileUploadService $fileUploadService,
    ) {}

    /**
     * Upload one or more images and attach them to the product.
     *
     * The first successfully committed upload establishes the initial cover image
     * if no cover exists yet. Subsequent images in the batch default to gallery.
     */
    public function upload(Product $product, array $files, ?string $altText, User $actor): void
    {
        $hasCover = $product->media()->where('role', ProductMedia::ROLE_COVER)->exists();
        $currentMaxSort = (int) ($product->media()->max('sort_order') ?? -1);

        DB::transaction(function () use ($product, $files, $altText, $actor, $hasCover, $currentMaxSort): void {
            $isFirstUploadInBatch = true;

            foreach ($files as $uploadedFile) {
                /** @var UploadedFile $uploadedFile */
                $storedFile = $this->fileUploadService->store($uploadedFile, $actor, [
                    'file_kind' => StoredFile::KIND_ATTACHMENT,
                    'visibility' => StoredFile::VISIBILITY_PUBLIC_SAFE_PREVIEW,
                ]);

                $currentMaxSort++;

                // First image uploaded becomes cover if no cover exists yet
                $role = (! $hasCover && $isFirstUploadInBatch)
                    ? ProductMedia::ROLE_COVER
                    : ProductMedia::ROLE_GALLERY;

                ProductMedia::query()->create([
                    'product_id' => $product->id,
                    'file_id' => $storedFile->id,
                    'role' => $role,
                    'alt_text' => $altText,
                    'sort_order' => $currentMaxSort,
                ]);

                $isFirstUploadInBatch = false;
            }
        });

        DB::afterCommit(function () use ($product, $files, $actor): void {
            event(new AuditEvent('products.media_uploaded', $actor, [
                'product_id' => $product->id,
                'file_count' => count($files),
            ]));
        });
    }

    /**
     * Delete a ProductMedia record and its underlying StoredFile from disk.
     *
     * V1 ownership assumption: files in product_media are exclusively owned by
     * this media record. No reference-count check is performed.
     *
     * If the deleted record was the cover, the gallery image with the lowest
     * sort_order (then lowest id) is automatically promoted to cover.
     */
    public function destroy(ProductMedia $media, User $actor): void
    {
        $productId = $media->product_id;
        $mediaId = $media->id;
        $fileId = $media->file_id;
        $wasCover = $media->isCover();

        DB::transaction(function () use ($media, $wasCover, $actor): void {
            $file = $media->file;

            $media->delete();

            if ($file !== null) {
                $this->fileUploadService->delete($file, $actor);
            }

            if ($wasCover) {
                $this->promoteNextGalleryToCover($media->product_id);
            }
        });

        DB::afterCommit(function () use ($productId, $mediaId, $fileId, $actor): void {
            event(new AuditEvent('products.media_deleted', $actor, [
                'product_id' => $productId,
                'media_id' => $mediaId,
                'file_id' => $fileId,
            ]));
        });
    }

    /**
     * Promote a gallery image to cover.
     *
     * Inside a transaction:
     *  1. All existing covers for this product are demoted to gallery.
     *  2. The target media record is set to cover.
     */
    public function setCover(ProductMedia $media, User $actor): void
    {
        $productId = $media->product_id;
        $mediaId = $media->id;

        DB::transaction(function () use ($media): void {
            ProductMedia::query()
                ->where('product_id', $media->product_id)
                ->where('role', ProductMedia::ROLE_COVER)
                ->update(['role' => ProductMedia::ROLE_GALLERY]);

            $media->update(['role' => ProductMedia::ROLE_COVER]);
        });

        DB::afterCommit(function () use ($productId, $mediaId, $actor): void {
            event(new AuditEvent('products.media_cover_changed', $actor, [
                'product_id' => $productId,
                'media_id' => $mediaId,
            ]));
        });
    }

    /**
     * Reorder all media for a product.
     *
     * Rules (any violation returns 422, existing order is untouched):
     *  - $ids must contain no duplicates.
     *  - $ids must contain exactly the IDs of every ProductMedia record for this product —
     *    no more, no fewer (full ordered list required).
     *
     * On success, sort_order is updated sequentially starting from 0.
     * No audit event is dispatched (high-frequency, low-value operation).
     */
    public function reorder(Product $product, array $ids, User $actor): void
    {
        // 1. Duplicate check
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'ids' => ['The reorder list contains duplicate IDs.'],
            ]);
        }

        // 2. Ownership + completeness check
        $intIds = array_map('intval', $ids);

        $existingIds = ProductMedia::query()
            ->where('product_id', $product->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        sort($existingIds);

        $submittedIds = $intIds;
        sort($submittedIds);

        if ($existingIds !== $submittedIds) {
            throw ValidationException::withMessages([
                'ids' => ['The reorder list must contain exactly all media IDs for this product.'],
            ]);
        }

        // 3. Apply new sort order
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $sortOrder => $id) {
                ProductMedia::query()
                    ->where('id', (int) $id)
                    ->update(['sort_order' => $sortOrder]);
            }
        });
    }

    /**
     * Promote the gallery image with the lowest sort_order (then lowest id) to cover.
     * Called internally after deleting the current cover.
     * Must be called inside an active transaction.
     */
    private function promoteNextGalleryToCover(int $productId): void
    {
        $next = ProductMedia::query()
            ->where('product_id', $productId)
            ->where('role', ProductMedia::ROLE_GALLERY)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($next !== null) {
            $next->update(['role' => ProductMedia::ROLE_COVER]);
        }
    }
}
