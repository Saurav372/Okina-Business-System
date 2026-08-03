<?php

namespace App\Support\Money;

use InvalidArgumentException;

class MoneyParser
{
    /**
     * Regex enforcing strict major currency amount format:
     * - Optional leading zero(s) preceding digits
     * - Optional decimal portion with 1 or 2 digits
     * - Rejects signs (+/-), commas, trailing/leading dots, internal spaces, or >2 decimals.
     */
    public const STRICT_AMOUNT_REGEX = '/^\d+(?:\.\d{1,2})?$/';

    /**
     * Parse a user-submitted amount string into integer minor units (paisa/cents).
     *
     * @throws InvalidArgumentException
     */
    public static function toMinorUnits(string $amount): int
    {
        $trimmed = trim($amount);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Amount cannot be empty.');
        }

        if (! preg_match(self::STRICT_AMOUNT_REGEX, $trimmed)) {
            throw new InvalidArgumentException('Amount must be a valid non-negative number with at most 2 decimal places.');
        }

        // Split whole and fraction parts
        $parts = explode('.', $trimmed);
        $whole = ltrim($parts[0], '0') ?: '0';
        $fraction = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '00';

        // Maximum supported minor unit value: 99,999,999,999 (₹999,999,999.99)
        if (strlen($whole) > 9) {
            throw new InvalidArgumentException('Amount exceeds maximum allowed limit of ₹999,999,999.99.');
        }

        $minorString = $whole.$fraction;
        $minorValue = (int) $minorString;

        if ($minorValue < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }

        return $minorValue;
    }
}
