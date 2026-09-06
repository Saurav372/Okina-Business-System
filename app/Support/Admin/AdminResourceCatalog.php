<?php

namespace App\Support\Admin;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;

final class AdminResourceCatalog
{
    public function resources(): array
    {
        return [
            OrderResource::registration(),
            ProductResource::registration(),
            ProductCategoryResource::registration(),
        ];
    }

    public function resource(string $key): ?array
    {
        foreach ($this->resources() as $resource) {
            if ($resource['key'] === $key) {
                return $resource;
            }
        }

        return null;
    }
}
