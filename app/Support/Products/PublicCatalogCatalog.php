<?php

namespace App\Support\Products;

final class PublicCatalogCatalog
{
    public function endpoints(): array
    {
        return [
            'categories_endpoint' => '/api/catalog/categories',
            'category_products_endpoint' => '/api/catalog/categories/{slug}/products',
            'products_endpoint' => '/api/catalog/products',
            'product_detail_endpoint' => '/api/catalog/products/{slug}',
        ];
    }

    public function guidance(): array
    {
        return [
            'Use category responses for listing and navigation.',
            'Use product responses for product detail pages.',
            'Treat published, public records as the only public source of truth.',
            'Do not rely on raw stock counts or internal identifiers in the frontend.',
        ];
    }
}
