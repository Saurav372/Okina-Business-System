<?php

namespace App\Support\Vendors;

use App\Models\Vendor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use RuntimeException;

class VendorCodeGenerator
{
    public const PREFIX = 'VND-';

    /**
     * Generate a candidate vendor code matching VND-[A-Z0-9]{6}.
     */
    public static function generate(): string
    {
        return self::PREFIX.Str::upper(Str::random(6));
    }

    /**
     * Attempt executing creation callback with auto-generated vendor codes,
     * retrying only on vendor_code unique constraint collisions.
     *
     * @template T
     *
     * @param  callable(string): T  $creator
     * @return T
     */
    public static function executeWithRetry(callable $creator, int $maxAttempts = 3): mixed
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $candidateCode = self::generate();

            try {
                return $creator($candidateCode);
            } catch (QueryException $e) {
                $lastException = $e;
                if (self::isVendorCodeCollision($e) && $attempts < $maxAttempts) {
                    continue;
                }
                throw $e;
            }
        }

        throw new RuntimeException(
            "Failed to generate a unique vendor code after {$maxAttempts} attempts.",
            0,
            $lastException
        );
    }

    /**
     * Verify that a QueryException represents specifically a vendor_code unique collision.
     */
    public static function isVendorCodeCollision(QueryException $e): bool
    {
        $sqlState = (string) $e->getCode();
        $message = $e->getMessage();

        $isIntegrityViolation = $sqlState === '23000'
            || str_contains($message, '1062 Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed');

        if (! $isIntegrityViolation) {
            return false;
        }

        return str_contains($message, 'vendors_vendor_code_unique')
            || str_contains($message, 'vendors.vendor_code');
    }
}
