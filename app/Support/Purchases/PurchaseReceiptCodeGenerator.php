<?php

namespace App\Support\Purchases;

use Illuminate\Database\QueryException;

class PurchaseReceiptCodeGenerator
{
    /**
     * Generate a new unique Purchase Receipt code in format PR-YYYYMM-XXXXXX.
     */
    public static function generate(): string
    {
        $yearMonth = now()->format('Ym');
        $randomHex = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));

        return "PR-{$yearMonth}-{$randomHex}";
    }

    /**
     * Determine if a QueryException is specifically caused by a receipt_number index collision.
     */
    public static function isReceiptNumberCollision(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());
        $sqlState = (string) $e->getCode();

        $isIntegrityViolation = ($sqlState === '23000') || str_contains($message, 'integrity constraint violation');
        $hasConstraintName = str_contains($message, 'purchase_receipts_receipt_number_unique') || str_contains($message, 'receipt_number');

        return $isIntegrityViolation && $hasConstraintName;
    }

    /**
     * Execute a creation callback with concurrency retry logic for receipt_number collisions.
     *
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public static function executeWithRetry(callable $callback, int $maxAttempts = 3)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            $receiptNumber = static::generate();

            try {
                return $callback($receiptNumber);
            } catch (QueryException $e) {
                $lastException = $e;

                if (static::isReceiptNumberCollision($e) && $attempt < $maxAttempts) {
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException;
    }
}
