<?php

declare(strict_types=1);

$workspace = dirname(__DIR__);
$sourcePath = $workspace.'/apps/frontend/public/brand/okina-logo.png';
$frontendTarget = $workspace.'/apps/frontend/public/brand/okina-watermark-mark.png';
$backendDirectory = $workspace.'/apps/backend/resources/brand';
$backendTarget = $backendDirectory.'/okina-watermark-mark.png';

if (! function_exists('imagecreatefrompng')) {
    fwrite(STDERR, "The PHP GD extension is required.\n");
    exit(1);
}

$source = imagecreatefrompng($sourcePath);
if ($source === false) {
    fwrite(STDERR, "The source logo could not be opened.\n");
    exit(1);
}

$size = 320;
$output = imagecreatetruecolor($size, $size);
imagealphablending($output, false);
imagesavealpha($output, true);
$transparent = imagecolorallocatealpha($output, 255, 255, 255, 127);
imagefill($output, 0, 0, $transparent);

// The supplied lockup is vertical. This crop isolates the flame-and-ring mark.
imagecopyresampled($output, $source, 0, 0, 118, 112, $size, $size, 844, 844);

for ($y = 0; $y < $size; $y++) {
    for ($x = 0; $x < $size; $x++) {
        $rgba = imagecolorat($output, $x, $y);
        $red = ($rgba >> 16) & 0xff;
        $green = ($rgba >> 8) & 0xff;
        $blue = $rgba & 0xff;
        $whiteness = min($red, $green, $blue);

        if ($whiteness >= 250) {
            imagesetpixel($output, $x, $y, $transparent);
            continue;
        }

        if ($whiteness > 220) {
            $alpha = min(126, (int) round((($whiteness - 220) / 30) * 126));
            $color = imagecolorallocatealpha($output, $red, $green, $blue, $alpha);
            imagesetpixel($output, $x, $y, $color);
        }
    }
}

if (! is_dir($backendDirectory)) {
    mkdir($backendDirectory, 0775, true);
}

imagepng($output, $frontendTarget, 8);
copy($frontendTarget, $backendTarget);

imagedestroy($source);
imagedestroy($output);

echo "Generated branded watermark marks.\n";
