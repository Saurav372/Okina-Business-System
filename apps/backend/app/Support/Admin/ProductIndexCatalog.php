<?php

namespace App\Support\Admin;

use App\Models\Product;

/**
 * A3.2.7 Admin catalog management — product index definition.
 *
 * Defines the columns, scopes, and filters available on the admin product list.
 * Does not implement mutation; mutation is gated by ProductPolicy.
 */
final class ProductIndexCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'columns' => [
                'id' => ['label' => 'ID',           'sortable' => true],
                'name' => ['label' => 'Name',         'sortable' => true, 'searchable' => true],
                'slug' => ['label' => 'Slug',         'sortable' => false],
                'status' => ['label' => 'Status',       'sortable' => true],
                'visibility' => ['label' => 'Visibility',   'sortable' => true],
                'product_type' => ['label' => 'Type',         'sortable' => true],
                'category' => ['label' => 'Category',     'sortable' => false, 'relationship' => 'category.name'],
                'sku_count' => ['label' => 'SKUs',         'sortable' => false, 'aggregate' => 'skus_count'],
                'created_at' => ['label' => 'Created',      'sortable' => true],
            ],
            'filters' => [
                'status' => [
                    'label' => 'Status',
                    'options' => [
                        Product::STATUS_DRAFT => 'Draft',
                        Product::STATUS_ACTIVE => 'Active',
                        Product::STATUS_OUT_OF_STOCK => 'Out of Stock',
                        Product::STATUS_BULK_ONLY => 'Bulk Only',
                        Product::STATUS_DISCONTINUED => 'Discontinued',
                    ],
                ],
                'visibility' => [
                    'label' => 'Visibility',
                    'options' => [
                        Product::VISIBILITY_PUBLIC => 'Public',
                        Product::VISIBILITY_PRIVATE => 'Private',
                    ],
                ],
                'product_type' => [
                    'label' => 'Type',
                    'options' => [
                        Product::TYPE_SIMPLE => 'Simple',
                        Product::TYPE_VARIABLE => 'Variable',
                        Product::TYPE_BUNDLE => 'Bundle',
                    ],
                ],
            ],
            'default_sort' => ['column' => 'created_at', 'direction' => 'desc'],
            'searchable' => true,
            'per_page' => 25,
        ];
    }
}
