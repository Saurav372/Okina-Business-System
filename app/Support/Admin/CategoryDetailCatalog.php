<?php

namespace App\Support\Admin;

/**
 * A3.2.7 Admin catalog management — category form/detail field definition.
 */
final class CategoryDetailCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sections' => [
                'core' => [
                    'label' => 'Category Details',
                    'fields' => [
                        'name' => ['label' => 'Name',        'type' => 'text',     'required' => true,  'max' => 255],
                        'slug' => ['label' => 'Slug',        'type' => 'text',     'required' => false, 'auto_from' => 'name'],
                        'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => false, 'max' => 1000],
                        'status' => [
                            'label' => 'Status',
                            'type' => 'select',
                            'required' => true,
                            'options' => [
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ],
                        ],
                        'sort_order' => ['label' => 'Sort Order',  'type' => 'integer',  'required' => false, 'min' => 0],
                        'published_at' => ['label' => 'Publish At',  'type' => 'datetime', 'required' => false],
                    ],
                ],
                'seo' => [
                    'label' => 'SEO',
                    'fields' => [
                        'seo_title' => ['label' => 'SEO Title',       'type' => 'text',     'required' => false, 'max' => 255],
                        'seo_description' => ['label' => 'SEO Description', 'type' => 'textarea', 'required' => false, 'max' => 500],
                    ],
                ],
            ],
        ];
    }
}
