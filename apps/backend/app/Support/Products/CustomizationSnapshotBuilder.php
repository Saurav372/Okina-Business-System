<?php

namespace App\Support\Products;

use App\Models\Product;
use App\Models\StoredFile;

class CustomizationSnapshotBuilder
{
    public const SCHEMA_VERSION = 1;

    public function build(
        Product $product,
        array $selection,
        array $validation,
        StoredFile $file,
        mixed $placement,
        mixed $selectedOptions,
        ?string $customerNote = null,
    ): array {
        $normalizedPlacement = $this->normalizePlacement($placement);

        return array_filter([
            'schema_version' => self::SCHEMA_VERSION,
            'product' => [
                'slug' => $product->slug,
                'name' => $product->name,
            ],
            'sku_code' => $validation['matched_sku']['sku_code'] ?? $selection['sku_code'],
            'variant_key' => $validation['resolved_variant_key'],
            'selected_options_snapshot' => $this->selectedOptionsSnapshot(
                $selectedOptions,
                $validation['matched_sku']['option_values'] ?? [],
            ),
            'print_method' => $selection['print_method'],
            'print_position' => $selection['print_position'],
            'placement' => $normalizedPlacement,
            'files' => [
                $this->fileReference($file, 'original_upload'),
            ],
            'mockup_preview' => [
                'role' => 'mockup_preview',
                'render_type' => 'signed_svg_mockup',
                'source_file_public_id' => $file->public_id,
                'route_name' => 'catalog.products.mockup-preview',
                'expires_in_minutes' => 15,
                'placement' => $normalizedPlacement,
            ],
            'customer_note' => $this->cleanNote($customerNote),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function publicCartSnapshot(array $snapshot): array
    {
        $normalizedPlacement = $this->normalizePlacement(data_get($snapshot, 'placement', []));

        return array_filter([
            'schema_version' => data_get($snapshot, 'schema_version', self::SCHEMA_VERSION),
            'product' => $this->publicProductReference(data_get($snapshot, 'product', [])),
            'sku_code' => $this->cleanString(data_get($snapshot, 'sku_code')),
            'variant_key' => $this->cleanString(data_get($snapshot, 'variant_key')),
            'selected_options_snapshot' => $this->publicSelectedOptionsSnapshot(data_get($snapshot, 'selected_options_snapshot', [])),
            'print_method' => $this->cleanString(data_get($snapshot, 'print_method')),
            'print_position' => $this->cleanString(data_get($snapshot, 'print_position')),
            'placement' => $normalizedPlacement,
            'files' => $this->publicFileReferences(data_get($snapshot, 'files', [])),
            'mockup_preview' => $this->publicMockupPreview(data_get($snapshot, 'mockup_preview', [])),
            'customer_note' => $this->cleanNote(data_get($snapshot, 'customer_note')),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function customizationFingerprint(array $snapshot): string
    {
        return hash('sha256', json_encode($this->canonicalizeSnapshot($snapshot), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function normalizePlacement(mixed $placement): array
    {
        $defaults = [
            'x' => 50.0,
            'y' => 50.0,
            'scale' => 1.0,
            'rotation' => 0.0,
        ];
        $ranges = [
            'x' => [0.0, 100.0],
            'y' => [0.0, 100.0],
            'scale' => [0.35, 1.5],
            'rotation' => [-45.0, 45.0],
        ];

        if (! is_array($placement)) {
            return $defaults;
        }

        foreach ($defaults as $key => $default) {
            if (! isset($placement[$key]) || ! is_numeric($placement[$key])) {
                continue;
            }

            $value = (float) $placement[$key];
            $defaults[$key] = max($ranges[$key][0], min($ranges[$key][1], $value));
        }

        return $defaults;
    }

    public function selectedOptionsSnapshot(mixed $selectedOptions, array $skuOptionValues = []): array
    {
        if (! is_array($selectedOptions)) {
            return [];
        }

        $labels = $this->optionLabelsByValue($skuOptionValues);
        $snapshot = [];

        foreach ($selectedOptions as $optionCode => $value) {
            if (! is_string($optionCode) || ! is_string($value)) {
                continue;
            }

            $code = trim($optionCode);
            $resolvedValue = trim($value);

            if ($code === '' || $resolvedValue === '') {
                continue;
            }

            $snapshot[] = array_filter([
                'option_code' => $code,
                'value_code' => $resolvedValue,
                'value_label' => $labels[$resolvedValue] ?? $resolvedValue,
            ], static fn (mixed $item): bool => $item !== null && $item !== '');
        }

        usort($snapshot, static fn (array $left, array $right): int => strcmp($left['option_code'] ?? '', $right['option_code'] ?? ''));

        return array_values($snapshot);
    }

    public function fileReference(StoredFile $file, string $role): array
    {
        return array_filter([
            'public_id' => $file->public_id,
            'role' => $role,
            'file_kind' => $file->file_kind,
            'visibility' => $file->visibility,
            'status' => $file->status,
            'original_filename' => $file->original_filename,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'has_preview' => $file->hasPreview(),
            'preview' => $this->previewReference($file),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    private function publicProductReference(mixed $product): ?array
    {
        if (! is_array($product)) {
            return null;
        }

        $reference = array_filter([
            'slug' => $this->cleanString($product['slug'] ?? null),
            'name' => $this->cleanString($product['name'] ?? null),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $reference === [] ? null : $reference;
    }

    private function publicSelectedOptionsSnapshot(mixed $selectedOptions): array
    {
        if (! is_array($selectedOptions)) {
            return [];
        }

        $snapshot = [];

        foreach ($selectedOptions as $value) {
            if (! is_array($value)) {
                continue;
            }

            $snapshot[] = array_filter([
                'option_code' => $this->cleanString($value['option_code'] ?? null),
                'value_code' => $this->cleanString($value['value_code'] ?? null),
                'value_label' => $this->cleanString($value['value_label'] ?? null),
            ], static fn (mixed $item): bool => $item !== null && $item !== '');
        }

        usort($snapshot, static fn (array $left, array $right): int => strcmp($left['option_code'] ?? '', $right['option_code'] ?? ''));

        return array_values($snapshot);
    }

    private function publicFileReferences(mixed $files): array
    {
        if (! is_array($files)) {
            return [];
        }

        $normalized = [];

        foreach ($files as $file) {
            $reference = $this->publicFileReference($file);

            if ($reference !== null) {
                $normalized[] = $reference;
            }
        }

        usort($normalized, static fn (array $left, array $right): int => strcmp(
            ($left['role'] ?? '').'|'.($left['public_id'] ?? ''),
            ($right['role'] ?? '').'|'.($right['public_id'] ?? '')
        ));

        return array_values($normalized);
    }

    private function publicFileReference(mixed $file): ?array
    {
        if (! is_array($file)) {
            return null;
        }

        $publicId = $this->cleanString($file['public_id'] ?? null);

        if ($publicId === null) {
            return null;
        }

        $reference = array_filter([
            'public_id' => $publicId,
            'role' => $this->cleanString($file['role'] ?? null),
            'file_kind' => $this->cleanString($file['file_kind'] ?? null),
            'visibility' => $this->cleanString($file['visibility'] ?? null),
            'status' => $this->cleanString($file['status'] ?? null),
            'original_filename' => $this->cleanString($file['original_filename'] ?? null),
            'mime_type' => $this->cleanString($file['mime_type'] ?? null),
            'size_bytes' => is_numeric($file['size_bytes'] ?? null) ? (int) $file['size_bytes'] : null,
            'has_preview' => array_key_exists('has_preview', $file) ? (bool) $file['has_preview'] : null,
            'preview' => $this->publicPreviewReference($file['preview'] ?? null),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $reference === [] ? null : $reference;
    }

    private function publicMockupPreview(mixed $preview): ?array
    {
        if (! is_array($preview)) {
            return null;
        }

        $normalizedPlacement = $this->normalizePlacement($preview['placement'] ?? []);

        $reference = array_filter([
            'role' => $this->cleanString($preview['role'] ?? null),
            'render_type' => $this->cleanString($preview['render_type'] ?? null),
            'source_file_public_id' => $this->cleanString($preview['source_file_public_id'] ?? null),
            'route_name' => $this->cleanString($preview['route_name'] ?? null),
            'expires_in_minutes' => is_numeric($preview['expires_in_minutes'] ?? null) ? (int) $preview['expires_in_minutes'] : null,
            'placement' => $normalizedPlacement,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);

        return $reference === [] ? null : $reference;
    }

    private function publicPreviewReference(mixed $preview): ?array
    {
        if (! is_array($preview)) {
            return null;
        }

        $reference = array_filter([
            'mime_type' => $this->cleanString($preview['mime_type'] ?? null),
            'size_bytes' => is_numeric($preview['size_bytes'] ?? null) ? (int) $preview['size_bytes'] : null,
            'width' => is_numeric($preview['width'] ?? null) ? (int) $preview['width'] : null,
            'height' => is_numeric($preview['height'] ?? null) ? (int) $preview['height'] : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $reference === [] ? null : $reference;
    }

    private function canonicalizeSnapshot(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalizeSnapshot($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalizeSnapshot($item);
        }

        return $value;
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }

    private function optionLabelsByValue(array $skuOptionValues): array
    {
        $labels = [];

        foreach ($skuOptionValues as $value) {
            if (! is_array($value)) {
                continue;
            }

            $code = isset($value['code']) ? trim((string) $value['code']) : '';
            $label = isset($value['label']) ? trim((string) $value['label']) : '';

            if ($code === '') {
                continue;
            }

            $labels[$code] = $label === '' ? $code : $label;
        }

        return $labels;
    }

    private function previewReference(StoredFile $file): ?array
    {
        if (! $file->hasPreview()) {
            return null;
        }

        return array_filter([
            'mime_type' => $file->previewMimeType(),
            'size_bytes' => $file->previewSizeBytes(),
            'width' => data_get($file->previewMetadata(), 'width'),
            'height' => data_get($file->previewMetadata(), 'height'),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function cleanNote(?string $note): ?string
    {
        $clean = trim((string) $note);

        return $clean === '' ? null : $clean;
    }
}
