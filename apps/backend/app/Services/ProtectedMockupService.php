<?php

namespace App\Services;

use App\Models\CustomerAccount;
use App\Models\Product;
use App\Models\StoredFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProtectedMockupService
{
    public const WATERMARK_VERSION = 'okina-protected-v1';

    private const CANVAS_WIDTH = 1200;

    private const CANVAS_HEIGHT = 900;

    public function create(
        Product $product,
        StoredFile $source,
        CustomerAccount $actor,
        string $colorCode,
        string $printPosition,
        array $placement,
    ): StoredFile {
        $bytes = $this->renderPng($product, $source, $colorCode, $printPosition, $placement);
        $publicId = 'FIL-'.Str::upper(Str::random(16));
        $storedFilename = Str::lower(Str::random(40)).'.png';
        $storagePath = 'files/'.$publicId.'/'.$storedFilename;
        $disk = Storage::disk('private');

        try {
            $disk->put($storagePath, $bytes);

            return StoredFile::query()->create([
                'public_id' => $publicId,
                'customer_id' => $actor->customer_id,
                'uploaded_by_customer_id' => $actor->customer_id,
                'storage_disk' => 'private',
                'storage_path' => $storagePath,
                'original_filename' => Str::slug($product->slug.'-protected-preview'),
                'stored_filename' => $storedFilename,
                'extension' => 'png',
                'mime_type' => 'image/png',
                'size_bytes' => strlen($bytes),
                'checksum_sha256' => hash('sha256', $bytes),
                'file_kind' => StoredFile::KIND_MOCKUP,
                'visibility' => StoredFile::VISIBILITY_CUSTOMER_VISIBLE,
                'status' => StoredFile::STATUS_ACTIVE,
                'scan_status' => StoredFile::SCAN_SKIPPED,
                'metadata' => [
                    'protected_mockup' => [
                        'source_file_public_id' => $source->public_id,
                        'product_slug' => $product->slug,
                        'color_code' => $colorCode,
                        'print_position' => $printPosition,
                        'placement' => $placement,
                        'watermark_version' => self::WATERMARK_VERSION,
                        'rendered_at' => now()->toIso8601String(),
                    ],
                ],
            ]);
        } catch (Throwable $throwable) {
            $disk->delete($storagePath);
            throw $throwable;
        }
    }

    private function renderPng(
        Product $product,
        StoredFile $source,
        string $colorCode,
        string $printPosition,
        array $placement,
    ): string {
        if (! function_exists('imagecreatetruecolor')) {
            throw ValidationException::withMessages([
                'preview' => 'Protected preview rendering is temporarily unavailable.',
            ]);
        }

        $sourceDisk = Storage::disk($source->previewStorageDisk() ?? $source->storage_disk);
        $sourcePath = $source->previewPath() ?? $source->storage_path;

        if (! $sourceDisk->exists($sourcePath)) {
            throw ValidationException::withMessages([
                'design_file' => 'The uploaded artwork could not be found.',
            ]);
        }

        $artwork = @imagecreatefromstring($sourceDisk->get($sourcePath));

        if ($artwork === false) {
            throw ValidationException::withMessages([
                'design_file' => 'The uploaded artwork cannot be used for this preview.',
            ]);
        }

        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        imagealphablending($canvas, true);
        imageantialias($canvas, true);

        $paper = imagecolorallocate($canvas, 244, 241, 235);
        $surface = imagecolorallocate($canvas, 255, 255, 255);
        $ink = imagecolorallocate($canvas, 31, 29, 27);
        $muted = imagecolorallocate($canvas, 105, 98, 91);
        imagefill($canvas, 0, 0, $paper);
        $this->roundedRectangle($canvas, 42, 42, 1158, 858, 32, $surface);

        $templateFile = resource_path('mockups/tshirt-'.Str::slug($colorCode).'.png');
        if (! is_file($templateFile)) {
            $templateFile = resource_path('mockups/tshirt-black.png');
        }

        if (is_file($templateFile)) {
            $garmentImg = @imagecreatefrompng($templateFile);
            if ($garmentImg !== false) {
                imagealphablending($garmentImg, true);
                imagesavealpha($garmentImg, true);
                imagecopyresampled($canvas, $garmentImg, 150, 120, 0, 0, 900, 680, imagesx($garmentImg), imagesy($garmentImg));
                imagedestroy($garmentImg);
            }
        } else {
            $garmentRgb = $this->garmentColor($colorCode);
            $garment = imagecolorallocate($canvas, ...$garmentRgb);
            $garmentDark = imagecolorallocate($canvas, ...$this->shade($garmentRgb, -34));
            $garmentLight = imagecolorallocate($canvas, ...$this->shade($garmentRgb, 24));
            $shadow = imagecolorallocatealpha($canvas, 24, 20, 18, 103);
            $seamRgb = $this->shade($garmentRgb, -50);
            $seam = imagecolorallocatealpha($canvas, $seamRgb[0], $seamRgb[1], $seamRgb[2], 55);

            $shirt = [
                418, 190, 348, 214, 245, 274, 132, 409, 236, 484,
                302, 410, 318, 744, 370, 780, 830, 780, 882, 744,
                898, 410, 964, 484, 1068, 409, 955, 274, 852, 214,
                782, 190, 735, 236, 680, 258, 600, 266, 520, 258,
                465, 236,
            ];
            $shirtShadow = [];
            foreach (array_chunk($shirt, 2) as [$x, $y]) {
                $shirtShadow[] = $x + 10;
                $shirtShadow[] = $y + 16;
            }

            imagefilledellipse($canvas, 600, 790, 560, 55, $shadow);
            imagefilledpolygon($canvas, $shirtShadow, $shadow);
            imagefilledpolygon($canvas, $shirt, $garmentDark);

            $body = [
                418, 190, 465, 236, 520, 258, 600, 266, 680, 258,
                735, 236, 782, 190, 820, 245, 882, 744, 830, 780,
                370, 780, 318, 744, 380, 245,
            ];
            imagefilledpolygon($canvas, $body, $garment);

            $leftHighlight = [380, 245, 418, 190, 465, 236, 505, 256, 458, 752, 370, 780, 318, 744];
            imagefilledpolygon($canvas, $leftHighlight, $garmentLight);

            imagefilledellipse($canvas, 600, 207, 192, 130, $garmentDark);
            imagefilledellipse($canvas, 600, 198, 142, 98, $paper);
            imagearc($canvas, 600, 207, 192, 130, 8, 172, $seam);
            imageline($canvas, 372, 775, 828, 775, $seam);
            imageline($canvas, 319, 411, 237, 483, $seam);
            imageline($canvas, 881, 411, 963, 483, $seam);
        }

        $area = $this->printArea($printPosition);
        $this->compositeArtwork($canvas, $artwork, $area, $placement);

        $title = Str::limit($product->name, 42, '');
        imagestring($canvas, 5, 82, 76, strtoupper($title), $ink);
        imagestring($canvas, 3, 84, 105, strtoupper(str_replace('_', ' ', $printPosition)).' PLACEMENT', $muted);
        imagestring($canvas, 3, 932, 80, 'PROTECTED PREVIEW', $muted);

        $this->drawWatermark($canvas, $source->public_id);

        ob_start();
        imagepng($canvas, null, 6);
        $bytes = ob_get_clean();

        imagedestroy($artwork);
        imagedestroy($canvas);

        if ($bytes === false || $bytes === '') {
            throw ValidationException::withMessages([
                'preview' => 'The protected preview could not be generated.',
            ]);
        }

        return $bytes;
    }

    private function compositeArtwork($canvas, $artwork, array $area, array $placement): void
    {
        $sourceWidth = max(1, imagesx($artwork));
        $sourceHeight = max(1, imagesy($artwork));
        $scale = max(0.6, min(1.2, (float) ($placement['scale'] ?? 1.0)));
        $xPercent = max(10, min(90, (float) ($placement['x'] ?? 50)));
        $yPercent = max(10, min(90, (float) ($placement['y'] ?? 50)));
        $fit = min(($area['width'] * 0.8 * $scale) / $sourceWidth, ($area['height'] * 0.8 * $scale) / $sourceHeight);
        $targetWidth = max(24, (int) round($sourceWidth * $fit));
        $targetHeight = max(24, (int) round($sourceHeight * $fit));
        $centerX = $area['x'] + (($xPercent / 100) * $area['width']);
        $centerY = $area['y'] + (($yPercent / 100) * $area['height']);
        $targetX = (int) round(max($area['x'], min($area['x'] + $area['width'] - $targetWidth, $centerX - ($targetWidth / 2))));
        $targetY = (int) round(max($area['y'], min($area['y'] + $area['height'] - $targetHeight, $centerY - ($targetHeight / 2))));

        imagealphablending($artwork, true);
        imagesavealpha($artwork, true);
        imagecopyresampled(
            $canvas,
            $artwork,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );
    }

    private function drawWatermark($canvas, string $sourcePublicId): void
    {
        $logoPath = resource_path('brand/okina-watermark-mark.png');
        $logo = is_file($logoPath) ? @imagecreatefrompng($logoPath) : false;
        if ($logo !== false) {
            imagealphablending($logo, true);
            imagesavealpha($logo, true);
        }

        $label = imagecreatetruecolor(350, 58);
        imagealphablending($label, false);
        imagesavealpha($label, true);
        $transparent = imagecolorallocatealpha($label, 255, 255, 255, 127);
        imagefill($label, 0, 0, $transparent);
        imagealphablending($label, true);
        $text = imagecolorallocatealpha($label, 125, 25, 25, 76);
        if ($logo !== false) {
            imagecopyresampled($label, $logo, 6, 5, 0, 0, 48, 48, imagesx($logo), imagesy($logo));
        }
        imagestring($label, 5, 62, 21, 'OKINA CRAFT / PREVIEW', $text);
        $rotated = imagerotate($label, 24, $transparent);
        imagesavealpha($rotated, true);

        for ($y = -100; $y < self::CANVAS_HEIGHT + 160; $y += 155) {
            for ($x = -180; $x < self::CANVAS_WIDTH + 260; $x += 360) {
                imagecopy($canvas, $rotated, $x, $y, 0, 0, imagesx($rotated), imagesy($rotated));
            }
        }

        $band = imagecolorallocatealpha($canvas, 22, 20, 18, 52);
        imagefilledrectangle($canvas, 0, 409, self::CANVAS_WIDTH, 493, $band);
        $white = imagecolorallocatealpha($canvas, 255, 255, 255, 20);
        $reference = strtoupper(substr(str_replace('-', '', $sourcePublicId), -8));
        if ($logo !== false) {
            imagecopyresampled($canvas, $logo, 306, 416, 0, 0, 68, 68, imagesx($logo), imagesy($logo));
        }
        $this->scaledString($canvas, 'PROTECTED PREVIEW', 394, 425, 2, $white);
        imagestring($canvas, 3, 532, 472, 'REF '.$reference.' / NOT FOR PRODUCTION', $white);

        imagedestroy($label);
        imagedestroy($rotated);
        if ($logo !== false) {
            imagedestroy($logo);
        }
    }

    private function scaledString($canvas, string $text, int $x, int $y, int $scale, int $color): void
    {
        $width = (imagefontwidth(5) * strlen($text)) + 12;
        $height = imagefontheight(5) + 12;
        $source = imagecreatetruecolor($width, $height);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        $transparent = imagecolorallocatealpha($source, 255, 255, 255, 127);
        imagefill($source, 0, 0, $transparent);
        imagealphablending($source, true);
        $sourceColor = imagecolorallocatealpha($source, 255, 255, 255, 18);
        imagestring($source, 5, 6, 6, $text, $sourceColor);
        imagecopyresampled($canvas, $source, $x, $y, 0, 0, $width * $scale, $height * $scale, $width, $height);
        imagedestroy($source);
    }

    private function printArea(string $position): array
    {
        return match ($position) {
            'left_chest' => ['x' => 438, 'y' => 300, 'width' => 105, 'height' => 120],
            'right_chest' => ['x' => 657, 'y' => 300, 'width' => 105, 'height' => 120],
            'sleeve' => ['x' => 230, 'y' => 304, 'width' => 105, 'height' => 120],
            'back' => ['x' => 432, 'y' => 292, 'width' => 336, 'height' => 360],
            default => ['x' => 438, 'y' => 302, 'width' => 324, 'height' => 340],
        };
    }

    private function garmentColor(string $colorCode): array
    {
        return match (Str::lower($colorCode)) {
            'ink', 'black', 'charcoal' => [35, 35, 36],
            'paper', 'white', 'cream' => [238, 235, 226],
            'navy', 'navy_blue' => [30, 45, 74],
            'red', 'scarlet' => [166, 39, 42],
            'royal', 'blue' => [39, 83, 154],
            'green', 'forest' => [43, 89, 66],
            'maroon' => [100, 34, 48],
            'yellow', 'gold' => [220, 176, 48],
            default => [116, 112, 106],
        };
    }

    private function shade(array $rgb, int $amount): array
    {
        return array_map(static fn (int $channel): int => max(0, min(255, $channel + $amount)), $rgb);
    }

    private function roundedRectangle($image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
