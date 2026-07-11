<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

readonly class ProductSkuService
{
    /**
     * Maximum Cartesian combination count permitted per generation run.
     *
     * SKU generation is intended for modest option sets. Extremely large
     * Cartesian products are outside the supported scope for V1. This limit
     * is a business decision; raising it requires explicit review.
     */
    private const MAX_COMBINATIONS = 500;

    /**
     * Generate the full Cartesian SKU matrix for a product.
     *
     * For each variant combination that does not yet exist, a new ProductSku
     * record is created using firstOrCreate() against the unique composite
     * index (product_id, variant_key). This makes generation idempotent —
     * re-running it never produces duplicate records.
     *
     * Variant codes are sorted alphabetically (ksort) before key assembly,
     * ensuring deterministic variant_key values regardless of iteration order.
     *
     * SKU code generation: a readable code is derived from the product slug and
     * variant value codes (e.g. PREMIUM-POLO-RED-M). On sku_code uniqueness
     * violations a numeric suffix is appended (-1, -2, …). Detection is isolated
     * behind isDuplicateKeyException() to avoid mixing SQLSTATE values with
     * vendor-specific message parsing inline.
     *
     * If the existing combination is already present, firstOrCreate() returns
     * the existing record and no additional SKU is created.
     */
    public function generateMatrix(Product $product, User $actor): void
    {
        $variants = $product->variants()->orderBy('sort_order')->get();

        if ($variants->isEmpty()) {
            $this->ensureDefaultSku($product);

            return;
        }

        $arrays = [];
        foreach ($variants as $variant) {
            $arrays[$variant->code] = $variant->values;
        }

        // Guard against runaway combination counts before allocating memory.
        $combinationCount = 1;
        foreach ($arrays as $values) {
            $combinationCount *= count($values);
        }

        if ($combinationCount > self::MAX_COMBINATIONS) {
            throw ValidationException::withMessages([
                'matrix' => [
                    'The variant options yield too many combinations (maximum '.self::MAX_COMBINATIONS.'). Please reduce variant values.',
                ],
            ]);
        }

        $combinations = $this->cartesianProduct($arrays);

        DB::transaction(function () use ($product, $combinations): void {
            foreach ($combinations as $combination) {
                // Alphabetical sorting contract: ksort guarantees that variant
                // codes are always assembled in the same order, producing
                // deterministic variant_key values regardless of iteration order.
                ksort($combination);

                $parts = [];
                $optionValues = [];
                $suffix = [];

                foreach ($combination as $variantCode => $value) {
                    $parts[] = $variantCode.':'.$value['code'];
                    $optionValues[] = [
                        'code' => $value['code'],
                        'label' => $value['label'],
                        'variant_code' => $variantCode,
                    ];
                    $suffix[] = strtoupper($value['code']);
                }

                $variantKey = implode('|', $parts);
                $baseSkuCode = strtoupper($product->slug).($suffix ? '-'.implode('-', $suffix) : '');

                $this->firstOrCreateSku($product, $variantKey, $baseSkuCode, [
                    'option_values' => $optionValues,
                    'name_suffix' => implode(' / ', array_column($optionValues, 'label')),
                    'price_minor' => $product->base_price_minor ?? 0,
                ]);
            }
        });
    }

    /**
     * Update a SKU's editable fields.
     *
     * No-op optimization: if no fields have changed after fill(), the save()
     * is skipped, preventing unnecessary writes and audit events.
     */
    public function update(ProductSku $sku, array $data, User $actor): ProductSku
    {
        $sku->fill($data);

        if ($sku->isDirty()) {
            DB::transaction(function () use ($sku): void {
                $sku->save();
                $sku->refresh();
            });
        }

        return $sku;
    }

    /**
     * Soft-delete a SKU and dispatch an audit event.
     *
     * Inventory records and movement history are intentionally retained after
     * deletion to preserve audit and ledger integrity. They are not removed
     * by this method or the observer.
     *
     * The deletion payload is captured from the in-memory model before
     * deletion so that future maintainers do not assume the event queries
     * a deleted record.
     */
    public function destroy(ProductSku $sku, User $actor): void
    {
        $productId = $sku->product_id;
        $skuCode = $sku->sku_code;

        DB::transaction(function () use ($sku, $actor, $productId, $skuCode): void {
            $sku->delete();

            DB::afterCommit(function () use ($actor, $productId, $skuCode): void {
                event(new AuditEvent('products.sku_deleted', $actor, [
                    'product_id' => $productId,
                    'sku_code' => $skuCode,
                ]));
            });
        });
    }

    /**
     * Ensure a default SKU exists for products with no variant options.
     *
     * Uses the same firstOrCreate() + retry pattern as generateMatrix() to
     * remain safe under concurrent requests.
     */
    private function ensureDefaultSku(Product $product): ProductSku
    {
        $baseSkuCode = strtoupper($product->slug);

        return DB::transaction(function () use ($product, $baseSkuCode): ProductSku {
            return $this->firstOrCreateSku($product, 'default', $baseSkuCode, [
                'option_values' => [],
                'name_suffix' => null,
                'price_minor' => $product->base_price_minor ?? 0,
            ]);
        });
    }

    /**
     * Insert or retrieve a SKU using firstOrCreate, retrying on sku_code
     * uniqueness collisions by incrementing a numeric suffix (-1, -2, …).
     *
     * The unique database constraint on (product_id, variant_key) ensures
     * that concurrent requests do not produce duplicate variant rows; the
     * sku_code suffix retry loop handles the narrower race on the global
     * sku_code unique constraint.
     */
    private function firstOrCreateSku(
        Product $product,
        string $variantKey,
        string $baseSkuCode,
        array $extraAttributes,
    ): ProductSku {
        $counter = 0;

        while (true) {
            try {
                $skuCode = $counter === 0 ? $baseSkuCode : $baseSkuCode.'-'.$counter;

                return $product->skus()->firstOrCreate(
                    ['variant_key' => $variantKey],
                    array_merge($extraAttributes, [
                        'sku_code' => $skuCode,
                        'status' => 'active',
                        'direct_checkout_enabled' => true,
                        'quote_required' => false,
                        'track_stock' => true,
                        'stock_quantity' => 0,
                    ])
                );
            } catch (QueryException $e) {
                if ($this->isDuplicateKeyException($e)) {
                    $counter++;

                    if ($counter > 20) {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Detect a unique constraint violation in a vendor-agnostic way.
     *
     * Centralising this check avoids mixing SQLSTATE values with vendor-specific
     * message string parsing throughout the retry loops.
     */
    private function isDuplicateKeyException(QueryException $e): bool
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        // SQLSTATE 23000 covers integrity constraint violations across MySQL,
        // PostgreSQL, and SQLite. The additional str_contains guards handle
        // environments where PDO returns a string code instead of '23000'.
        return $code === '23000'
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, '1062');
    }

    /**
     * Compute the full Cartesian product of an associative array of value sets.
     *
     * Note: The result is held in memory as a plain PHP array. This is
     * appropriate for the modest option sets expected in V1. Should V2 require
     * support for larger sets, a generator-based (yield) implementation would
     * reduce memory usage without changing the calling code.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $arrays
     * @return array<int, array<string, array<string, mixed>>>
     */
    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $key => $values) {
            $append = [];

            foreach ($result as $product) {
                foreach ($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }

            $result = $append;
        }

        return $result;
    }
}
