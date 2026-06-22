<?php

namespace App\Support\Admin;

use App\Models\Product;

/**
 * A3.2.7 Admin catalog management — product form/detail field definition.
 *
 * Defines which fields are shown and editable in the product create/edit form.
 * Finance-sensitive cost fields are excluded from this surface.
 */
final class ProductDetailCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sections' => [
                'core' => [
                    'label' => 'Product Details',
                    'fields' => [
                        'name' => ['label' => 'Name',             'type' => 'text',     'required' => true,  'max' => 255],
                        'slug' => ['label' => 'Slug',             'type' => 'text',     'required' => false, 'auto_from' => 'name'],
                        'short_description' => ['label' => 'Short Description', 'type' => 'textarea', 'required' => false, 'max' => 500],
                        'description' => ['label' => 'Description',      'type' => 'richtext', 'required' => false],
                        'primary_category_id' => ['label' => 'Category',     'type' => 'select',   'required' => false, 'relationship' => 'category'],
                    ],
                ],
                'status' => [
                    'label' => 'Status & Visibility',
                    'fields' => [
                        'status' => [
                            'label' => 'Status',
                            'type' => 'select',
                            'required' => true,
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
                            'type' => 'select',
                            'required' => true,
                            'options' => [
                                Product::VISIBILITY_PUBLIC => 'Public',
                                Product::VISIBILITY_PRIVATE => 'Private',
                            ],
                        ],
                        'published_at' => ['label' => 'Publish At', 'type' => 'datetime', 'required' => false],
                    ],
                ],
                'ordering' => [
                    'label' => 'Ordering Rules',
                    'fields' => [
                        'product_type' => [
                            'label' => 'Product Type',
                            'type' => 'select',
                            'required' => true,
                            'options' => [
                                Product::TYPE_SIMPLE => 'Simple',
                                Product::TYPE_VARIABLE => 'Variable',
                                Product::TYPE_BUNDLE => 'Bundle',
                            ],
                        ],
                        'customization_mode' => [
                            'label' => 'Customization',
                            'type' => 'select',
                            'required' => true,
                            'options' => [
                                Product::CUSTOMIZATION_NONE => 'None',
                                Product::CUSTOMIZATION_OPTIONAL => 'Optional',
                                Product::CUSTOMIZATION_REQUIRED => 'Required',
                            ],
                        ],
                        'fulfillment_type' => [
                            'label' => 'Fulfillment',
                            'type' => 'select',
                            'required' => true,
                            'options' => [
                                Product::FULFILLMENT_STOCKED => 'Stocked',
                                Product::FULFILLMENT_MADE_TO_ORDER => 'Made to Order',
                            ],
                        ],
                        'direct_checkout_enabled' => ['label' => 'Direct Checkout', 'type' => 'boolean', 'required' => false],
                        'quote_enabled' => ['label' => 'Quote Enabled',   'type' => 'boolean', 'required' => false],
                        'min_order_quantity' => ['label' => 'Min Quantity',     'type' => 'integer', 'required' => false, 'min' => 1],
                        'max_order_quantity' => ['label' => 'Max Quantity',     'type' => 'integer', 'required' => false, 'min' => 1],
                        'bulk_threshold_quantity' => ['label' => 'Bulk Threshold',   'type' => 'integer', 'required' => false, 'min' => 1],
                        'base_price_minor' => ['label' => 'Base Price (paisa)', 'type' => 'integer', 'required' => false, 'min' => 0],
                        'currency' => ['label' => 'Currency',         'type' => 'text',    'required' => false, 'default' => 'INR'],
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
            // Variants and SKUs are managed via related panels below the form
            'relations' => [
                'variants' => ['label' => 'Variants', 'resource' => 'product_variants'],
                'skus' => ['label' => 'SKUs',     'resource' => 'product_skus'],
            ],
        ];
    }
}
