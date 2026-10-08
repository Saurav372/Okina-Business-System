<?php

declare(strict_types=1);

$sourceDir = 'C:\\Users\\user\\.gemini\\antigravity-ide\\brain\\c32a3752-5fa2-4ea2-8595-6610e9bdd81c';
$targetBusinessDir = __DIR__ . '/../public/storefront/business';

$newImages = [
    'inspiration-cafe' => 'inspiration_cafe_uniform_1791449672645.jpg',
    'inspiration-workwear' => 'inspiration_workwear_studio_1791449710722.jpg',
    'inspiration-event' => 'inspiration_event_printing_1791449732905.jpg',
    'inspiration-team' => 'inspiration_team_layer_1791449764996.jpg',
    'bulk-team' => 'bulk_team_apparel_1791449792786.jpg',
    'small-order' => 'small_order_apparel_1791449818752.jpg',
];

echo "=== High-Fidelity Sharpness Image Optimization (Native Resolution, 93% Quality) ===\n\n";

// 1. Process New Generated Images at 100% Native Resolution
foreach ($newImages as $slug => $filename) {
    $srcPath = $sourceDir . DIRECTORY_SEPARATOR . $filename;
    if (!file_exists($srcPath)) {
        echo "Missing source: $srcPath\n";
        continue;
    }

    $image = imagecreatefromjpeg($srcPath);
    if (!$image) {
        continue;
    }

    $w = imagesx($image);
    $h = imagesy($image);

    // Save full resolution WebP at 93% quality (crystal clear)
    $webpPath = $targetBusinessDir . DIRECTORY_SEPARATOR . $slug . '.webp';
    imagewebp($image, $webpPath, 93);

    // Save full resolution JPG at 93% quality
    $jpgPath = $targetBusinessDir . DIRECTORY_SEPARATOR . $slug . '.jpg';
    imagejpeg($image, $jpgPath, 93);

    $webpSize = round(filesize($webpPath) / 1024, 1);
    $jpgSize = round(filesize($jpgPath) / 1024, 1);
    echo "✓ High-res $slug ({$w}x{$h}) -> WebP: {$webpSize} KB | JPG: {$jpgSize} KB\n";
}

// 2. Process Native Business PNGs at 100% Native Resolution without downscaling
$existingPngs = [
    'hero-background.png',
    'hero-team.png',
    'hero.png',
    'cafe.png',
    'team.png',
    'uniform.png',
    'event.png',
    'tee.png',
    'polo.png',
    'hoodie.png',
];

echo "\n--- Processing Core Brand Assets at 100% Native Resolution ---\n";
foreach ($existingPngs as $pngFilename) {
    $pngFile = $targetBusinessDir . DIRECTORY_SEPARATOR . $pngFilename;
    if (!file_exists($pngFile)) {
        continue;
    }

    $basename = pathinfo($pngFile, PATHINFO_FILENAME);
    $origSize = round(filesize($pngFile) / 1024, 1);
    $image = @imagecreatefrompng($pngFile);
    if (!$image) {
        continue;
    }

    $w = imagesx($image);
    $h = imagesy($image);

    // Maintain transparency
    imagealphablending($image, true);
    imagesavealpha($image, true);

    // Save full resolution WebP at 92% quality (no resampling, native resolution)
    $webpPath = $targetBusinessDir . DIRECTORY_SEPARATOR . $basename . '.webp';
    imagewebp($image, $webpPath, 92);

    $newWebpSize = round(filesize($webpPath) / 1024, 1);
    echo "✓ Crystal-Clear {$basename}.png ({$w}x{$h}, {$origSize} KB) -> {$basename}.webp ({$newWebpSize} KB)\n";
}

echo "\nHigh-fidelity optimization complete!\n";
