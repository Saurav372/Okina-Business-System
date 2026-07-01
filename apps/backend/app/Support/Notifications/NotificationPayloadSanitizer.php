<?php

namespace App\Support\Notifications;

class NotificationPayloadSanitizer
{
    /**
     * Curated deny-list of keys to mask.
     *
     * @var array<int, string>
     */
    protected const DENY_LIST = [
        'password',
        'password_hash',
        'api_key',
        'secret',
        'access_token',
        'refresh_token',
        'bearer_token',
        'client_secret',
        'private_key',
        'cvv',
        'card_number',
        'authorization',
        'credential',
    ];

    /**
     * Recursively sanitize payload by masking sensitive deny-list keys.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        return $this->sanitizeRecursive($payload);
    }

    /**
     * Recursively traverse and mask keys matching the deny-list.
     *
     * @param  array<mixed>  $array
     * @return array<mixed>
     */
    protected function sanitizeRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::DENY_LIST, true)) {
                $array[$key] = '[MASKED]';
            } elseif (is_array($value)) {
                $array[$key] = $this->sanitizeRecursive($value);
            }
        }

        return $array;
    }
}
