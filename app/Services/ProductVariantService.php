<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

readonly class ProductVariantService
{
    /**
     * Store a new product variant option.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(Product $product, array $data, User $actor): ProductVariant
    {
        $values = $this->parseCsvValues($data['values_csv']);

        return DB::transaction(function () use ($product, $data, $values, $actor) {
            $variant = $product->variants()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'display_type' => $data['display_type'],
                'values' => $values,
                'is_required' => $data['is_required'],
                'sort_order' => $data['sort_order'],
            ]);

            $variant->refresh();

            DB::afterCommit(function () use ($variant, $actor) {
                event(new AuditEvent('products.variant_created', $actor, [
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'name' => $variant->name,
                    'code' => $variant->code,
                ]));
            });

            return $variant;
        });
    }

    /**
     * Update an existing product variant option.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariant $variant, array $data, User $actor): ProductVariant
    {
        $values = $this->parseCsvValues($data['values_csv']);

        $variant->fill([
            'name' => $data['name'],
            'code' => $data['code'],
            'display_type' => $data['display_type'],
            'values' => $values,
            'is_required' => $data['is_required'],
            'sort_order' => $data['sort_order'],
        ]);

        if ($variant->isDirty()) {
            DB::transaction(function () use ($variant, $actor) {
                $variant->save();
                $variant->refresh();

                DB::afterCommit(function () use ($variant, $actor) {
                    event(new AuditEvent('products.variant_updated', $actor, [
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->id,
                        'name' => $variant->name,
                        'code' => $variant->code,
                    ]));
                });
            });
        }

        return $variant;
    }

    /**
     * Delete an existing product variant option.
     */
    public function destroy(ProductVariant $variant, User $actor): void
    {
        DB::transaction(function () use ($variant, $actor) {
            // The deletion audit payload is built from the in-memory model captured before deletion.
            $productId = $variant->product_id;
            $variantId = $variant->id;
            $name = $variant->name;
            $code = $variant->code;

            $variant->delete();

            DB::afterCommit(function () use ($productId, $variantId, $name, $code, $actor) {
                event(new AuditEvent('products.variant_deleted', $actor, [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'name' => $name,
                    'code' => $code,
                ]));
            });
        });
    }

    /**
     * Parse and filter comma-separated value list into structured, unique arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseCsvValues(string $csv): array
    {
        $raw = array_filter(array_map('trim', explode(',', $csv)));

        // Case-insensitive deduplication preserving original casing of first occurrence
        $unique = collect($raw)->unique(fn ($item) => mb_strtolower($item))->all();

        $values = [];
        foreach (array_values($unique) as $index => $val) {
            $values[] = [
                'code' => Str::slug($val),
                'label' => $val,
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
                'metadata' => [],
            ];
        }

        return $values;
    }
}
