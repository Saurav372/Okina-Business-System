<?php

namespace App\Support\Products;

final class CustomizationOptionCatalog
{
    public function endpoints(): array
    {
        return [
            'customization_options_endpoint' => '/api/catalog/products/{slug}/customization-options',
        ];
    }

    public function guidance(): array
    {
        return [
            'Use option groups and SKU matches to drive product customization UI.',
            'Keep print position and print method choices public and product-safe.',
            'Treat the returned validation rules as the backend source of truth for option selection.',
            'Do not rely on private catalog metadata or browser-side SKU assumptions.',
        ];
    }

    public function printPositions(): array
    {
        return [
            ['code' => 'front', 'label' => 'Front'],
            ['code' => 'back', 'label' => 'Back'],
            ['code' => 'left_chest', 'label' => 'Left Chest'],
            ['code' => 'right_chest', 'label' => 'Right Chest'],
            ['code' => 'sleeve', 'label' => 'Sleeve'],
        ];
    }

    public function printMethods(): array
    {
        return [
            ['code' => 'dtf', 'label' => 'DTF'],
            ['code' => 'dtg', 'label' => 'DTG'],
            ['code' => 'embroidery', 'label' => 'Embroidery'],
            ['code' => 'screen_print', 'label' => 'Screen Print'],
        ];
    }

    public function printMethodCompatibility(): array
    {
        return [
            'front' => ['dtf', 'dtg', 'embroidery', 'screen_print'],
            'back' => ['dtf', 'dtg', 'screen_print'],
            'left_chest' => ['dtf', 'embroidery'],
            'right_chest' => ['dtf', 'embroidery'],
            'sleeve' => ['dtf', 'embroidery'],
        ];
    }
}
