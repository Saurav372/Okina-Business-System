<?php

namespace App\Support\Notification;

class NotificationContentSanitizer
{
    public const LIST_PREVIEW_MAX = 200;

    public const DETAIL_PREVIEW_MAX = 10000;

    /**
     * Mask recipient email addresses (e.g. j**n@example.com, a*@example.com, *@example.com).
     */
    public static function maskAddress(?string $address): string
    {
        if (empty($address)) {
            return 'N/A';
        }

        $address = trim($address);

        if (str_contains($address, '@')) {
            $parts = explode('@', $address, 2);
            $local = $parts[0];
            $domain = $parts[1] ?? '';

            $length = mb_strlen($local, 'UTF-8');
            if ($length <= 1) {
                $maskedLocal = '*';
            } elseif ($length === 2) {
                $maskedLocal = mb_substr($local, 0, 1, 'UTF-8').'*';
            } else {
                $first = mb_substr($local, 0, 1, 'UTF-8');
                $last = mb_substr($local, -1, 1, 'UTF-8');
                $maskedLocal = $first.str_repeat('*', max(1, $length - 2)).$last;
            }

            return $maskedLocal.'@'.$domain;
        }

        // Phone number masking (+91 ****** 3210)
        $digits = preg_replace('/[^0-9\+]/', '', $address);
        $len = strlen($digits);
        if ($len > 4) {
            $prefix = substr($digits, 0, 3);
            $suffix = substr($digits, -4);

            return $prefix.' '.str_repeat('*', max(4, $len - 7)).' '.$suffix;
        }

        return '****';
    }

    /**
     * Sanitize body content, strip entire URL query parameters (signed URLs/tokens), redact secrets, and truncate.
     */
    public static function sanitizeBody(?string $body, int $maxLength = self::DETAIL_PREVIEW_MAX): string
    {
        if (empty($body)) {
            return '';
        }

        // Strip HTML tags to ensure plain text output
        $plain = strip_tags($body);

        // Entire URL query-string redaction (https://example.com/reset?[REDACTED])
        $plain = preg_replace('/(https?:\/\/[^\s\?]+)\?[^\s]+/i', '$1?[REDACTED]', $plain);

        // Redact OTP and authorization patterns
        $plain = preg_replace('/\b(otp|code|token|password|secret|key)=([a-z0-9_\-]+)/i', '$1=[REDACTED]', $plain);
        $plain = preg_replace('/\b\d{4,8}\b(?=.*(otp|code|verification))/i', '[REDACTED]', $plain);

        // Truncate to maximum allowed length
        if (mb_strlen($plain, 'UTF-8') > $maxLength) {
            $plain = mb_substr($plain, 0, $maxLength, 'UTF-8').'... [TRUNCATED]';
        }

        return trim($plain);
    }
}
