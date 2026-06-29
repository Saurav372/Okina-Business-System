<?php

namespace App\Support\Vendors;

use Illuminate\Support\Str;

class VendorCodeGenerator
{
    /**
     * Generate a unique vendor code.
     */
    public static function generate(): string
    {
        return 'VND-'.Str::upper(Str::random(8));
    }
}
