<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePwaIconsCommand extends Command
{
    protected $signature = 'pwa:generate-icons';

    protected $description = 'Сгенерировать PNG-иконки PWA (192 и 512) для приложений жильца и управляющего';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->components->error('Требуется расширение PHP GD.');

            return self::FAILURE;
        }

        $this->writeIcon('public/icons/resident', 192, [2, 132, 199], 'K16');
        $this->writeIcon('public/icons/resident', 512, [2, 132, 199], 'K16');
        $this->writeIcon('public/icons/manager', 192, [5, 150, 105], 'PRO');
        $this->writeIcon('public/icons/manager', 512, [5, 150, 105], 'PRO');

        $this->components->info('PNG-иконки созданы в public/icons/resident и public/icons/manager');

        return self::SUCCESS;
    }

    /**
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    protected function writeIcon(string $dir, int $size, array $rgb, string $label): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $img = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        $white = imagecolorallocate($img, 255, 255, 255);
        $font = 5;
        $tw = imagefontwidth($font) * strlen($label);
        $th = imagefontheight($font);
        imagestring($img, $font, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), $label, $white);

        imagepng($img, "{$dir}/icon-{$size}.png");
        imagedestroy($img);
    }
}
