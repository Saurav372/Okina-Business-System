<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

readonly class ProductService
{
    /**
     * Update an existing product model.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data, User $actor): Product
    {
        $product->fill($data);

        if ($product->isDirty()) {
            DB::transaction(function () use ($product, $actor) {
                $product->save();
                $product->refresh();

                DB::afterCommit(function () use ($product, $actor) {
                    event(new AuditEvent('products.updated', $actor, [
                        'product_id' => $product->id,
                        'slug' => $product->slug,
                        'name' => $product->name,
                    ]));
                });
            });
        }

        return $product;
    }
}
