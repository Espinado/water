<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

class GeneratePwaIconsCommand extends Command
{
    protected $signature = 'pwa:generate-icons';

    protected $description = 'Сгенерировать PNG-иконки PWA (180, 192, 512 и maskable) из макетов SVG';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->components->error('Требуется расширение PHP GD.');

            return self::FAILURE;
        }

        $apps = [
            'resident' => [
                'bg' => [2, 132, 199],
                'label' => 'K16',
                'artwork' => 'drop',
            ],
            'manager' => [
                'bg' => [5, 150, 105],
                'label' => 'PRO',
                'artwork' => 'drop',
            ],
        ];

        foreach ($apps as $app => $config) {
            $dir = "public/icons/{$app}";

            foreach ([180, 192, 512] as $size) {
                $this->writeIcon("{$dir}/icon-{$size}.png", $size, $config, false);
            }

            $this->writeIcon("{$dir}/icon-maskable-512.png", 512, $config, true);
        }

        $this->components->info('PNG-иконки созданы в public/icons/resident и public/icons/manager');

        return self::SUCCESS;
    }

    /**
     * @param  array{bg: array{0:int,1:int,2:int}, label: string, artwork: string}  $config
     */
    protected function writeIcon(string $path, int $size, array $config, bool $maskable): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);

        $bg = imagecolorallocate($img, ...$config['bg']);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        $white = imagecolorallocate($img, 255, 255, 255);
        $scale = $maskable ? 0.72 : 1.0;
        $offset = (int) round($size * (1 - $scale) / 2);
        $inner = (int) round($size * $scale);

        if ($maskable) {
            $this->fillRoundedRect($img, 0, 0, $size, $size, $this->s(96, $size), $bg);
        } else {
            $this->fillRoundedRect($img, 0, 0, $size, $size, $this->s(96, $size), $bg);
        }

        if ($config['artwork'] === 'drop') {
            $this->drawWaterDrop($img, $offset, $offset, $inner, $white);
        } else {
            $this->drawHouse($img, $offset, $offset, $inner, $white);
        }

        $this->drawLabel($img, $size, $config['label'], $white, $maskable);

        imagepng($img, $path);
        imagedestroy($img);
    }

    protected function s(float $value, int $size): int
    {
        return (int) round($value * $size / 512);
    }

    protected function fillRoundedRect(GdImage $img, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
        imagefilledellipse($img, $x + $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
    }

    protected function drawWaterDrop(GdImage $img, int $offsetX, int $offsetY, int $inner, int $color): void
    {
        $scale = $inner / 512;
        $cx = $offsetX + (int) round(256 * $scale);
        $topY = $offsetY + (int) round(96 * $scale);
        $bulgeY = $offsetY + (int) round(220 * $scale);
        $bottomY = $offsetY + (int) round(316 * $scale);
        $halfWidth = (int) round(128 * $scale);

        imagefilledellipse(
            $img,
            $cx,
            (int) round(($bulgeY + $bottomY) / 2),
            $halfWidth * 2,
            max(2, $bottomY - $bulgeY + (int) round(48 * $scale)),
            $color,
        );

        imagefilledpolygon($img, [
            $cx,
            $topY,
            $cx - $halfWidth,
            $bulgeY,
            $cx + $halfWidth,
            $bulgeY,
        ], $color);
    }

    protected function drawHouse(GdImage $img, int $offsetX, int $offsetY, int $inner, int $color): void
    {
        $points = [
            128, 360,
            128, 200,
            256, 128,
            384, 200,
            384, 360,
            304, 360,
            304, 264,
            208, 264,
            208, 360,
        ];

        $scaled = [];

        foreach ($points as $index => $value) {
            $axis = $index % 2 === 0 ? $offsetX : $offsetY;
            $scaled[] = (int) round($axis + ($value * $inner / 512));
        }

        imagefilledpolygon($img, $scaled, $color);
    }

    protected function drawLabel(GdImage $img, int $size, string $label, int $color, bool $maskable): void
    {
        $font = $this->resolveFontPath();

        if ($font !== null) {
            $fontSize = $this->s($maskable ? 44 : 56, $size);
            $box = imagettfbbox($fontSize, 0, $font, $label);
            $textWidth = abs($box[2] - $box[0]);
            $textHeight = abs($box[7] - $box[1]);
            $x = (int) (($size - $textWidth) / 2);
            $y = (int) round($size * ($maskable ? 0.9 : 0.84) + $textHeight / 2);

            imagettftext($img, $fontSize, 0, $x, $y, $color, $font, $label);

            return;
        }

        $builtIn = 5;
        $textWidth = imagefontwidth($builtIn) * strlen($label);
        $textHeight = imagefontheight($builtIn);
        imagestring(
            $img,
            $builtIn,
            (int) (($size - $textWidth) / 2),
            (int) (($size - $textHeight) / 2),
            $label,
            $color,
        );
    }

    protected function resolveFontPath(): ?string
    {
        $candidates = [
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/Arial Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
