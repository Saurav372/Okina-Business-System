<?php

namespace App\Support\Audit;

final class AuditPayloadPolicy
{
    /**
     * @var array<int, string>
     */
    private array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'password_hash',
        'remember_token',
        'reset_token',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'private_key',
        'gateway_payload',
        'webhook_payload',
        'payment_credentials',
        'card_number',
        'card_cvv',
        'cvv',
        'otp',
        'raw_payload',
        'file_contents',
        'private_file_contents',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $sanitized[$key] = $this->sanitizeValue((string) $key, $value);
        }

        return $sanitized;
    }

    private function sanitizeValue(string $key, mixed $value): mixed
    {
        if ($this->isSensitiveKey($key)) {
            return '[redacted]';
        }

        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $childKey => $childValue) {
            $sanitized[$childKey] = $this->sanitizeValue((string) $childKey, $childValue);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array(strtolower($key), $this->sensitiveKeys, true);
    }
}
