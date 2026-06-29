<?php

namespace App\Support\Purchases;

use App\Models\VendorOrder;
use Illuminate\Support\Str;

class PurchaseOrderCodeGenerator
{
    public const PREFIX = 'PO-';

    /**
     * Generate a unique purchase order public ID.
     */
    public static function generate(): string
    {
        do {
            $code = self::PREFIX.Str::upper(Str::random(8));
        } while (VendorOrder::where('public_id', $code)->exists());

        return $code;
    }
}
