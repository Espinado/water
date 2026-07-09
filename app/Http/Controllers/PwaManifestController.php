<?php

namespace App\Http\Controllers;

use App\Services\PwaContext;
use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function show(string $app, PwaContext $pwa): JsonResponse
    {
        abort_unless($pwa->isValidApp($app), 404);

        $config = config("pwa.apps.{$app}");
        $iconsPath = $config['icons'];

        return response()->json([
            'id' => $config['id'],
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'description' => $config['description'],
            'start_url' => $config['start_url'],
            'scope' => $config['scope'],
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => $config['theme_color'],
            'background_color' => $config['background_color'],
            'lang' => str_replace('_', '-', app()->getLocale()),
            'icons' => [
                [
                    'src' => asset("{$iconsPath}/icon.svg"),
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset("{$iconsPath}/icon-192.png"),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset("{$iconsPath}/icon-512.png"),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
