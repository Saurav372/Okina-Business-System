<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MarketingTrackingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get a setting by key with optional default.
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return $setting->value ?? $default;
    }

    /**
     * Set/update a setting by key.
     */
    public static function setSetting(string $key, mixed $value): static
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Mask an API secret token, e.g. "EAAB••••••••••••••••7xQ"
     */
    public static function maskToken(?string $token): string
    {
        if (empty($token)) {
            return '';
        }

        $length = strlen($token);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        $prefix = substr($token, 0, 4);
        $suffix = substr($token, -3);

        return $prefix . str_repeat('•', min(16, $length - 7)) . $suffix;
    }

    /**
     * Get decrypted Meta CAPI Access Token.
     */
    public static function getMetaCapiToken(): ?string
    {
        $capiSettings = static::getSetting('meta_capi', []);
        $raw = $capiSettings['access_token'] ?? null;

        if (empty($raw)) {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw; // Return plain text if not encrypted yet
        }
    }

    /**
     * Store encrypted Meta CAPI Access Token.
     */
    public static function encryptToken(?string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        return Crypt::encryptString($token);
    }
}
