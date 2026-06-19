<?php

namespace App\Contracts;

interface PublicCatalogContract
{
    public function categories(): array;

    public function category(string $slug): ?array;

    public function categoryProducts(string $slug): array;

    public function products(): array;

    public function product(string $slug): ?array;

    public function guidance(): array;
}
