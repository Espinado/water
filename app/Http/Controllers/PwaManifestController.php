<?php

namespace App\Http\Controllers;

use App\Services\AppHost;
use App\Services\PwaContext;
use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function show(string $app, PwaContext $pwa): JsonResponse
    {
        return $this->manifest($app, $pwa);
    }

    public function manager(PwaContext $pwa): JsonResponse
    {
        return $this->manifest(AppHost::MANAGER, $pwa);
    }

    protected function manifest(string $app, PwaContext $pwa): JsonResponse
    {
        abort_unless($pwa->isValidApp($app), 404);

        $config = $pwa->appConfig($app);
        $iconsPath = $config['icons'];

        return response()->json([
            'id' => $pwa->manifestId($app),
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'description' => $config['description'],
            'start_url' => $pwa->manifestStartUrl($app),
            'scope' => $pwa->manifestScope($app),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => $config['theme_color'],
            'background_color' => $config['background_color'],
            'lang' => str_replace('_', '-', app()->getLocale()),
            'icons' => [
                [
                    'src' => "/{$iconsPath}/icon-192.png",
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => "/{$iconsPath}/icon-512.png",
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => "/{$iconsPath}/icon-maskable-512.png",
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => "/{$iconsPath}/icon.svg",
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
