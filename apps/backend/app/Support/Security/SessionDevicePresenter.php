<?php

namespace App\Support\Security;

use Carbon\CarbonImmutable;

class SessionDevicePresenter
{
    /**
     * Parse session database record into an AdminSessionView DTO.
     */
    public static function present(object $sessionRow, string $currentSessionId): AdminSessionView
    {
        $userAgent = (string) ($sessionRow->user_agent ?? '');
        $browser = self::detectBrowser($userAgent);
        $platform = self::detectPlatform($userAgent);

        $tz = config('app.timezone', 'Asia/Kolkata');
        $lastActiveAt = CarbonImmutable::createFromTimestamp((int) $sessionRow->last_activity, $tz);
        $lastActiveLabel = $lastActiveAt->diffForHumans();

        // Hash raw session ID using SHA-256 (never expose raw session tokens to Blade or APIs)
        $identifierHash = hash('sha256', (string) $sessionRow->id);
        $isCurrent = hash_equals((string) $sessionRow->id, $currentSessionId);

        return new AdminSessionView(
            identifierHash: $identifierHash,
            browser: $browser,
            platform: $platform,
            ipAddress: (string) ($sessionRow->ip_address ?? 'Unknown IP'),
            lastActiveAt: $lastActiveAt,
            lastActiveLabel: $lastActiveLabel,
            isCurrent: $isCurrent
        );
    }

    private static function detectBrowser(string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Browser';
        }

        if (preg_match('/Edg\/([0-9\.]+)/i', $userAgent)) {
            return 'Microsoft Edge';
        }
        if (preg_match('/OPR\/([0-9\.]+)/i', $userAgent) || preg_match('/Opera/i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/Chrome\/([0-9\.]+)/i', $userAgent)) {
            return 'Google Chrome';
        }
        if (preg_match('/Firefox\/([0-9\.]+)/i', $userAgent)) {
            return 'Mozilla Firefox';
        }
        if (preg_match('/Safari\/([0-9\.]+)/i', $userAgent)) {
            return 'Apple Safari';
        }

        return 'Unknown Browser';
    }

    private static function detectPlatform(string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Platform';
        }

        if (preg_match('/Windows/i', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Unknown Platform';
    }
}
