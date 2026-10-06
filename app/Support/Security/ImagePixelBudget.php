<?php

namespace App\Support\Security;

use Illuminate\Validation\ValidationException;

final class ImagePixelBudget
{
    // Bound decoded RGBA memory to approximately 32 MB, before preview overhead.
    public const MAX_PIXELS = 8_000_000;

    public const MAX_DIMENSION = 8_000;

    public static function validateBytes(string $bytes): void
    {
        $size = @getimagesizefromstring($bytes);
        if ($size === false || $size[0] < 1 || $size[1] < 1
            || $size[0] > self::MAX_DIMENSION || $size[1] > self::MAX_DIMENSION
            || $size[0] > intdiv(self::MAX_PIXELS, $size[1])) {
            throw ValidationException::withMessages([
                'file' => 'Images must be valid and no larger than 8 megapixels or 8000 pixels per side.',
            ]);
        }
    }
}
