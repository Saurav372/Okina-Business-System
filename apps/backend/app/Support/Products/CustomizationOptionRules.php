<?php

namespace App\Support\Products;

use App\Contracts\CustomizationOptionContract;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariant;

readonly class CustomizationOptionRules implements CustomizationOptionContract
{
    public function __construct(private CustomizationOptionCatalog $catalog) {}

    public function product(string $slug): ?array
    {
        $product = Product::query()
            ->publiclyVisible()
            ->with([
                'category',
                'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skus' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('slug', $slug)
            ->first();

        return $product === null ? null : $this->productPayload($product);
    }

    public function validateSelection(string $slug, array $selection): array
    {
        $product = Product::query()
            ->publiclyVisible()
            ->with([
                'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skus' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('slug', $slug)
            ->first();

        if ($product === null) {
            return [
                'valid' => false,
                'errors' => ['product_not_found'],
            ];
        }

        $selectedOptions = $this->normalizeSelectedOptions($selection['selected_options'] ?? []);
        $selectedSkuCode = is_string($selection['sku_code'] ?? null) ? trim((string) $selection['sku_code']) : null;
        $printPosition = is_string($selection['print_position'] ?? null) ? trim((string) $selection['print_position']) : null;
        $printMethod = is_string($selection['print_method'] ?? null) ? trim((string) $selection['print_method']) : null;
        $errors = [];

        if ($product->customization_mode !== Product::CUSTOMIZATION_NONE) {
            if ($printPosition === null || $printPosition === '') {
                $errors[] = 'print_position_required';
            } elseif (! $this->isAllowedPrintPosition($printPosition)) {
                $errors[] = 'print_position_invalid';
            }

            if ($printMethod === null || $printMethod === '') {
                $errors[] = 'print_method_required';
            } elseif (! $this->isAllowedPrintMethod($printMethod)) {
                $errors[] = 'print_method_invalid';
            }

            if ($printPosition !== null && $printMethod !== null && $printPosition !== '' && $printMethod !== '' && ! $this->isCompatiblePrintChoice($printPosition, $printMethod)) {
                $errors[] = 'print_method_position_incompatible';
            }
        }

        foreach ($product->variants as $variant) {
            $variantCode = (string) $variant->code;
            $required = (bool) $variant->is_required;
            $hasSelection = array_key_exists($variantCode, $selectedOptions);

            if ($required && ! $hasSelection) {
                $errors[] = 'missing_option:'.$variantCode;

                continue;
            }

            if (! $hasSelection) {
                continue;
            }

            $selectedValueCode = $selectedOptions[$variantCode];
            $allowedValueCodes = $this->variantValueCodes($variant);

            if (! in_array($selectedValueCode, $allowedValueCodes, true)) {
                $errors[] = 'invalid_option_value:'.$variantCode;
            }
        }

        foreach (array_keys($selectedOptions) as $selectedVariantCode) {
            if (! $this->hasVariantCode($product, $selectedVariantCode)) {
                $errors[] = 'unknown_option:'.$selectedVariantCode;
            }
        }

        $expectedVariantKey = $this->variantKeyFromSelection($selectedOptions);
        $matchedSku = $product->skus->first(fn (ProductSku $sku): bool => $sku->variant_key === $expectedVariantKey);

        if ($matchedSku === null) {
            $errors[] = 'sku_not_found_for_selection';
        } elseif ($selectedSkuCode !== null && $selectedSkuCode !== '' && $selectedSkuCode !== $matchedSku->sku_code) {
            $errors[] = 'sku_code_mismatch';
        }

        if ($matchedSku !== null && ! $matchedSku->direct_checkout_enabled) {
            $errors[] = 'sku_direct_checkout_disabled';
        }

        return [
            'valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'matched_sku' => $matchedSku === null ? null : $this->skuPayload($matchedSku),
            'resolved_variant_key' => $expectedVariantKey,
        ];
    }

    public function guidance(): array
    {
        $catalog = $this->catalog;

        return [
            ...$catalog->endpoints(),
            'public_option_fields' => $this->publicOptionFields(),
            'print_positions' => $catalog->printPositions(),
            'print_methods' => $catalog->printMethods(),
            'print_method_compatibility' => $catalog->printMethodCompatibility(),
            'astro_usage' => $catalog->guidance(),
        ];
    }

    private function productPayload(Product $product): array
    {
        return [
            'product' => [
                'slug' => $product->slug,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'customization_mode' => $product->customization_mode,
                'status' => $product->status,
                'visibility' => $product->visibility,
                'currency' => $product->currency,
                'category' => $product->category === null ? null : [
                    'slug' => $product->category->slug,
                    'name' => $product->category->name,
                ],
            ],
            'option_groups' => $product->variants
                ->map(fn (ProductVariant $variant): array => $this->variantPayload($variant))
                ->values()
                ->all(),
            'size_options' => $this->sizeOptions($product),
            'print_positions' => $this->catalog->printPositions(),
            'print_methods' => $this->catalog->printMethods(),
            'skus' => $product->skus
                ->map(fn (ProductSku $sku): array => $this->skuPayload($sku))
                ->values()
                ->all(),
            'validation' => [
                'requires_product_sku_match' => true,
                'requires_print_position' => $product->customization_mode !== Product::CUSTOMIZATION_NONE,
                'requires_print_method' => $product->customization_mode !== Product::CUSTOMIZATION_NONE,
                'selected_options_must_match_variant_codes' => true,
                'variant_key_format' => 'sorted code:value pairs or default for simple products',
                'allowed_print_positions' => array_map(fn (array $item): string => $item['code'], $this->catalog->printPositions()),
                'allowed_print_methods' => array_map(fn (array $item): string => $item['code'], $this->catalog->printMethods()),
                'print_method_compatibility' => $this->catalog->printMethodCompatibility(),
            ],
        ];
    }

    private function variantPayload(ProductVariant $variant): array
    {
        return [
            'name' => $variant->name,
            'code' => $variant->code,
            'display_type' => $variant->display_type,
            'is_required' => $variant->is_required,
            'sort_order' => $variant->sort_order,
            'values' => $this->variantValues($variant),
        ];
    }

    private function variantValues(ProductVariant $variant): array
    {
        $values = $variant->values ?? [];

        return collect($values)
            ->map(function (mixed $value): array {
                $code = is_array($value) ? (string) ($value['code'] ?? '') : '';
                $label = is_array($value) ? (string) ($value['label'] ?? '') : '';
                $sortOrder = is_array($value) ? (int) ($value['sort_order'] ?? 0) : 0;
                $isActive = is_array($value) ? (bool) ($value['is_active'] ?? true) : true;

                return [
                    'code' => $code,
                    'label' => $label === '' ? $code : $label,
                    'sort_order' => $sortOrder,
                    'is_active' => $isActive,
                ];
            })
            ->filter(fn (array $value): bool => $value['code'] !== '' && $value['label'] !== '')
            ->sortBy([
                ['sort_order', 'asc'],
                ['code', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function sizeOptions(Product $product): array
    {
        $sizeVariant = $product->variants->first(function (ProductVariant $variant): bool {
            $needle = strtolower((string) $variant->code.' '.(string) $variant->name);

            return str_contains($needle, 'size');
        });

        return $sizeVariant === null ? [] : $this->variantValues($sizeVariant);
    }

    private function skuPayload(ProductSku $sku): array
    {
        return [
            'sku_code' => $sku->sku_code,
            'variant_key' => $sku->variant_key,
            'option_values' => $this->optionValues($sku),
            'name_suffix' => $sku->name_suffix,
            'status' => $sku->status,
            'direct_checkout_enabled' => $sku->direct_checkout_enabled,
            'quote_required' => $sku->quote_required,
            'price_minor' => $sku->price_minor,
            'availability' => [
                'available_for_checkout' => $sku->direct_checkout_enabled && ($sku->quote_required === false),
                'requires_quote' => $sku->quote_required,
            ],
        ];
    }

    private function optionValues(ProductSku $sku): array
    {
        return collect($sku->option_values ?? [])
            ->map(function (mixed $value): array {
                $code = is_array($value) ? (string) ($value['code'] ?? '') : '';
                $label = is_array($value) ? (string) ($value['label'] ?? '') : '';

                return [
                    'code' => $code,
                    'label' => $label === '' ? $code : $label,
                ];
            })
            ->filter(fn (array $value): bool => $value['code'] !== '' && $value['label'] !== '')
            ->values()
            ->all();
    }

    private function normalizeSelectedOptions(mixed $selectedOptions): array
    {
        if (! is_array($selectedOptions)) {
            return [];
        }

        $normalized = [];

        foreach ($selectedOptions as $code => $value) {
            if (! is_string($code) || ! is_string($value)) {
                continue;
            }

            $code = trim($code);
            $value = trim($value);

            if ($code === '' || $value === '') {
                continue;
            }

            $normalized[$code] = $value;
        }

        ksort($normalized);

        return $normalized;
    }

    private function variantKeyFromSelection(array $selectedOptions): string
    {
        if ($selectedOptions === []) {
            return 'default';
        }

        $parts = [];

        foreach ($selectedOptions as $code => $value) {
            $parts[] = $code.':'.$value;
        }

        return implode('|', $parts);
    }

    private function isAllowedPrintPosition(string $position): bool
    {
        return in_array($position, array_map(fn (array $item): string => $item['code'], $this->catalog->printPositions()), true);
    }

    private function isAllowedPrintMethod(string $method): bool
    {
        return in_array($method, array_map(fn (array $item): string => $item['code'], $this->catalog->printMethods()), true);
    }

    private function isCompatiblePrintChoice(string $position, string $method): bool
    {
        $compatibility = $this->catalog->printMethodCompatibility();

        return in_array($method, $compatibility[$position] ?? [], true);
    }

    private function hasVariantCode(Product $product, string $variantCode): bool
    {
        return $product->variants->contains(fn (ProductVariant $variant): bool => $variant->code === $variantCode);
    }

    private function variantValueCodes(ProductVariant $variant): array
    {
        return collect($variant->values ?? [])
            ->map(fn (mixed $value): string => is_array($value) ? (string) ($value['code'] ?? '') : '')
            ->filter(fn (string $code): bool => $code !== '')
            ->values()
            ->all();
    }

    private function publicOptionFields(): array
    {
        return [
            'product',
            'option_groups',
            'size_options',
            'print_positions',
            'print_methods',
            'skus',
            'validation',
        ];
    }
}
