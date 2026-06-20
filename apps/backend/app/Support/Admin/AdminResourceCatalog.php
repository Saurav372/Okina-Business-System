<?php

namespace App\Support\Admin;

use App\Filament\Resources\Orders\OrderResource;

final class AdminResourceCatalog
{
    public function resources(): array
    {
        return [
            OrderResource::registration(),
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
