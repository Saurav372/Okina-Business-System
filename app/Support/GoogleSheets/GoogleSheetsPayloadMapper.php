<?php

namespace App\Support\GoogleSheets;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class GoogleSheetsPayloadMapper
{
    /**
     * Map an Eloquent model to its Google Sheets flat array representation.
     *
     * @return array<string, string|int|float|bool|null>
     *                                                   Associative array keyed by configured machine column names.
     */
    public function map(Model $model): array
    {
        $modelClass = get_class($model);
        $config = config("sheets.entities.{$modelClass}");

        if (! $config) {
            // InvalidArgumentException is appropriate here. If this integration grows,
            // consider a dedicated UnsupportedGoogleSheetsModelException for clearer
            // error reporting and more targeted catch blocks in dispatch infrastructure.
            throw new InvalidArgumentException("Model class [{$modelClass}] is not supported for Google Sheets mapping.");
        }

        $columns = $config['columns'] ?? [];

        $payload = [];
        foreach ($columns as $machineKey => $label) {
            $raw = $this->resolveField($model, $machineKey);
            $normalized = $this->normalizeValue($raw);
            $formatted = $this->formatValue($normalized);
            $payload[$machineKey] = $formatted;
        }

        return $payload;
    }

    /**
     * Resolve column human-readable header labels for a given model type.
     */
    public function headers(string $modelClass): array
    {
        $config = config("sheets.entities.{$modelClass}");

        if (! $config) {
            throw new InvalidArgumentException("Model class [{$modelClass}] is not supported for Google Sheets mapping.");
        }

        return array_values($config['columns'] ?? []);
    }

    /**
     * Resolve the target sheet name for the model class.
     */
    public function sheet(string $modelClass): string
    {
        $config = config("sheets.entities.{$modelClass}");

        if (! $config) {
            throw new InvalidArgumentException("Model class [{$modelClass}] is not supported.");
        }

        return $config['sheet'];
    }

    /**
     * Explicit field resolver — only configured keys are read, preventing
     * accidental attribute leakage and enforcing no lazy-loading on relations.
     */
    protected function resolveField(Model $model, string $key): mixed
    {
        // Relation-derived fields — safe eager-loading guard, return null if not loaded
        if ($key === 'customer_name') {
            return $model->relationLoaded('customer') ? $model->customer?->display_name : null;
        }

        if ($key === 'order_public_id') {
            return $model->relationLoaded('order') ? $model->order?->public_id : null;
        }

        if ($key === 'assigned_staff') {
            return $model->relationLoaded('assignedTo') ? $model->assignedTo?->name : null;
        }

        if ($key === 'vendor_code') {
            return $model->relationLoaded('vendor') ? $model->vendor?->vendor_code : null;
        }

        if ($key === 'sku_code') {
            return $model->relationLoaded('productSku') ? $model->productSku?->sku_code : null;
        }

        // Virtual fields — mapped from minor-unit attributes
        if ($key === 'balance_on_hand') {
            return $model->getAttribute('after_on_hand_quantity');
        }

        if ($key === 'total_amount') {
            $minor = $model->getAttribute('total_amount_minor');

            return is_numeric($minor) ? round($minor / 100, 2) : null;
        }

        if ($key === 'amount') {
            // Payment model uses amount_minor
            $minor = $model->getAttribute('amount_minor');

            return is_numeric($minor) ? round($minor / 100, 2) : null;
        }

        // Enum-cast fields — getAttribute handles enum unwrapping via casts
        if ($key === 'type') {
            return $model->getAttribute('movement_type');
        }

        if ($key === 'reason') {
            return $model->getAttribute('reason_code');
        }

        // Direct model attributes (only allow explicitly listed keys)
        $allowed = [
            'id', 'public_id', 'contact_name', 'display_name', 'email', 'phone',
            'source', 'status', 'payment_status', 'courier_name', 'shipping_method',
            'quantity', 'created_at', 'due_at', 'lead_id', 'provider', 'method',
        ];

        if (in_array($key, $allowed, true)) {
            return $model->getAttribute($key);
        }

        return null;
    }

    /**
     * Normalize complex objects (enums, datetimes) to basic representations.
     */
    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            // Explicitly clone and convert to UTC before formatting
            return Carbon::instance($value)->clone()->setTimezone('UTC');
        }

        return $value;
    }

    /**
     * Format values to scalar-only, sheet-safe outputs.
     */
    protected function formatValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        // DateTime objects after normalization step (already in UTC)
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s\Z');
        }

        // Any array or object reaching this stage is an unsupported value — emit null
        // to preserve the flat payload contract. Normalisation should prevent this in
        // practice; this guard is the final safety net against unexpected types.
        if (is_array($value) || is_object($value)) {
            return null;
        }

        return is_numeric($value) ? $value + 0 : (string) $value;
    }
}
