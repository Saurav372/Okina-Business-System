<?php

namespace App\Support\Idempotency;

use Illuminate\Support\Str;

final class IdempotencyKeyGenerator
{
    private const PREFIX = 'idempotency:';

    private const MAX_LENGTH = 120;

    /**
     * Build a stable duplicate-prevention key for a shared operation.
     *
     * Keys stay readable when possible and fall back to a hash when the
     * normalized value would exceed the approved storage length.
     *
     * @param  array<int, mixed>  $parts
     */
    public function make(string $operation, array $parts = []): string
    {
        $normalizedOperation = $this->normalizeOperation($operation);
        $normalizedParts = collect($parts)
            ->map(fn ($part): string => $this->normalizePart($part))
            ->filter(static fn (string $part): bool => $part !== '')
            ->implode(':');

        $composed = $normalizedParts === ''
            ? $normalizedOperation
            : $normalizedOperation.':'.$normalizedParts;

        $key = self::PREFIX.$composed;

        if (strlen($key) <= self::MAX_LENGTH) {
            return $key;
        }

        $digest = substr(sha1($key), 0, 32);

        return self::PREFIX.$normalizedOperation.':'.$digest;
    }

    public function normalizeOperation(mixed $value): string
    {
        return Str::of($this->stringify($value))
            ->replace(['|', ':', '/', '\\'], ' ')
            ->squish()
            ->snake()
            ->lower()
            ->toString();
    }

    public function normalizePart(mixed $value): string
    {
        return Str::of($this->stringify($value))
            ->replace(['|', ':', '/', '\\', ' '], '-')
            ->squish()
            ->lower()
            ->toString();
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        } elseif (is_object($value) && ! method_exists($value, '__toString')) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) $value;
    }
}
