<?php

namespace App\Support\Vendors;

use App\Models\Vendor;
use Illuminate\Support\Str;

class VendorCodeGenerator
{
    /**
     * Generate a unique vendor code.
     */
    public static function generate(): string
    {
        do {
            $code = 'VND-'.Str::upper(Str::random(8));
        } while (Vendor::where('vendor_code', $code)->exists());

        return $code;
    }
}
