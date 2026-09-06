<?php

namespace App\Support\Expenses;

use App\Models\ExpenseAttachment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExpenseAttachmentCodeGenerator
{
    /**
     * Generate unique attachment public ID format: ATT-YYYYMM-XXXXXX
     */
    public static function generate(): string
    {
        $prefix = 'ATT-'.Carbon::now()->format('Ym').'-';
        $random = Str::upper(Str::random(6));

        return $prefix.$random;
    }

    /**
     * Wrap database insertion query and retry on unique index collision.
     *
     * @throws QueryException
     */
    public static function executeWithRetry(callable $creator, int $maxAttempts = 3): ExpenseAttachment
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                return $creator();
            } catch (QueryException $e) {
                $isUniqueCollision = str_contains($e->getMessage(), 'expense_attachments_public_id_unique')
                    || str_contains($e->getMessage(), 'UNIQUE constraint failed: expense_attachments.public_id');

                if ($isUniqueCollision && $attempts < $maxAttempts) {
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Failed to generate a unique expense attachment public ID after multiple attempts.');
    }
}
