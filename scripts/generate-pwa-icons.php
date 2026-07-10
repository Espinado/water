<?php

declare(strict_types=1);

/**
 * Regenerate PWA PNG icons from theme colors (requires GD).
 *
 * Usage: php scripts/generate-pwa-icons.php
 */

$icons = [
    'resident' => ['fill' => [5, 150, 105], 'label' => 'K16', 'font' => 72],   // #059669
    'manager' => ['fill' => [220, 38, 38], 'label' => 'PRO', 'font' => 56],    // #dc2626
];

$sizes = [
    'icon-180.png' => 180,
    'icon-192.png' => 192,
    'icon-512.png' => 512,
    'icon-maskable-512.png' => 512,
];

foreach ($icons as $app => $meta) {
    $dir = __DIR__.'/../public/icons/'.$app;

    foreach ($sizes as $filename => $size) {
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        [$r, $g, $b] = $meta['fill'];
        $bg = imagecolorallocate($image, $r, $g, $b);
        $white = imagecolorallocate($image, 255, 255, 255);

        $radius = (int) round($size * 96 / 512);
        imagefilledrectangle($image, $radius, 0, $size - $radius - 1, $size - 1, $bg);
        imagefilledrectangle($image, 0, $radius, $size - 1, $size - $radius - 1, $bg);
        imagefilledellipse($image, $radius, $radius, $radius * 2, $radius * 2, $bg);
        imagefilledellipse($image, $size - $radius - 1, $radius, $radius * 2, $radius * 2, $bg);
        imagefilledellipse($image, $radius, $size - $radius - 1, $radius * 2, $radius * 2, $bg);
        imagefilledellipse($image, $size - $radius - 1, $size - $radius - 1, $radius * 2, $radius * 2, $bg);

        $cx = (int) ($size / 2);
        $cy = (int) round($size * 0.42);
        $dropW = (int) round($size * 0.5);
        $dropH = (int) round($size * 0.43);
        imagefilledellipse($image, $cx, $cy, $dropW, $dropH, $white);

        $font = 5;
        $label = $meta['label'];
        $textWidth = imagefontwidth($font) * strlen($label);
        $textX = (int) (($size - $textWidth) / 2);
        $textY = (int) round($size * 0.78);
        imagestring($image, $font, $textX, $textY, $label, $white);

        $path = $dir.'/'.$filename;
        imagepng($image, $path);
        imagedestroy($image);

        echo "Wrote {$path}\n";
    }
}

echo "Done.\n";
