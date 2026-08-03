<?php

namespace App\Support\Audit;

use JsonSerializable;

class AuditPayloadSanitizer
{
    private const MAX_DEPTH = 10;

    private const SENSITIVE_KEYS = [
        'password',
        'passwordconfirmation',
        'currentpassword',
        'token',
        'accesstoken',
        'refreshtoken',
        'authorization',
        'cookie',
        'sessionid',
        'secret',
        'apikey',
        'storagepath',
        'privatekey',
        'clientsecret',
        'otp',
        'verificationcode',
        'recoverycodes',
    ];

    /**
     * Recursively sanitize payload arrays and objects.
     */
    public static function sanitize(mixed $data, int $depth = 1): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return '[DEPTH_LIMIT_REACHED]';
        }

        if (is_null($data) || is_scalar($data)) {
            return $data;
        }

        if ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        if (is_object($data)) {
            if (method_exists($data, '__toString')) {
                return (string) $data;
            }

            return '[UNSERIALIZABLE]';
        }

        if (! is_array($data)) {
            return '[UNSERIALIZABLE]';
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            $stringKey = (string) $key;
            $normalizedKey = preg_replace('/[^a-z0-9]/i', '', strtolower($stringKey));

            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            $sanitized[$key] = self::sanitize($value, $depth + 1);
        }

        return $sanitized;
    }
}
