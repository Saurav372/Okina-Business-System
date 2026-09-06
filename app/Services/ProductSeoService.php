<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Product;
use App\Models\ProductSeo;
use App\Models\User;

class ProductSeoService
{
    /**
     * Update or create ProductSeo record for a product and dispatch diff audit log.
     *
     * @param  array<string, mixed>  $seoData
     */
    public function updateSeo(Product $product, array $seoData, ?User $actor = null): ProductSeo
    {
        $seo = $product->seo ?? new ProductSeo(['product_id' => $product->id]);

        $updatableKeys = [
            'meta_title',
            'meta_description',
            'focus_keyword',
            'canonical_url',
            'robots_index',
            'robots_follow',
            'og_title',
            'og_description',
            'og_image_id',
            'twitter_title',
            'twitter_description',
            'twitter_image_id',
        ];

        $oldValues = [];
        $newValues = [];
        $changes = [];

        foreach ($updatableKeys as $key) {
            if (array_key_exists($key, $seoData)) {
                $oldVal = $seo->{$key};
                $newVal = $seoData[$key];

                // Strict comparison to prevent null == false loose equality false negatives in PHP
                if ($oldVal !== $newVal) {
                    $oldValues[$key] = $oldVal;
                    $newValues[$key] = $newVal;
                    $changes[$key] = [
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                    $seo->{$key} = $newVal;
                }
            }
        }

        if ($seo->isDirty() || ! $seo->exists) {
            $seo->save();

            if (! empty($changes)) {
                event(new AuditEvent('products.seo_updated', $actor, [
                    'product_id' => $product->id,
                    'subject_type' => 'product',
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'changes' => $changes,
                ]));
            }
        }

        return $seo;
    }
}
