<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class PwaServiceWorkerController extends Controller
{
    public function show(): Response
    {
        $content = <<<'JS'
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});
JS;

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
