<?php

namespace App\Support\Admin;

/**
 * A3.2.7 Admin catalog management — category index definition.
 */
final class CategoryIndexCatalog
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
                'product_count' => ['label' => 'Products',     'sortable' => false, 'aggregate' => 'products_count'],
                'published_at' => ['label' => 'Published At', 'sortable' => true],
                'created_at' => ['label' => 'Created',      'sortable' => true],
            ],
            'filters' => [
                'status' => [
                    'label' => 'Status',
                    'options' => [
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ],
                ],
            ],
            'default_sort' => ['column' => 'sort_order', 'direction' => 'asc'],
            'searchable' => true,
            'per_page' => 25,
        ];
    }
}
