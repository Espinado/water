<?php

namespace App\Services;

use Illuminate\Http\Request;

class PwaContext
{
    public function appKey(?Request $request = null): ?string
    {
        $request ??= request();

        if ($request->routeIs('manager.*', 'pwa.manager', 'login.manager')) {
            return 'manager';
        }

        if ($request->routeIs('dashboard', 'pwa.resident', 'login.resident', 'login')) {
            return 'resident';
        }

        $cookie = $request->cookie(config('pwa.cookie'));
        if (is_string($cookie) && $this->isValidApp($cookie)) {
            return $cookie;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function app(?Request $request = null): ?array
    {
        $key = $this->appKey($request);
        if ($key === null) {
            return null;
        }

        return config("pwa.apps.{$key}");
    }

    public function rememberApp(string $appKey): void
    {
        if (! $this->isValidApp($appKey)) {
            return;
        }

        cookie()->queue(
            config('pwa.cookie'),
            $appKey,
            (int) config('pwa.cookie_days') * 24 * 60,
        );
    }

    public function homeRoute(string $appKey): string
    {
        return (string) config("pwa.apps.{$appKey}.home_route", 'dashboard');
    }

    public function isValidApp(string $appKey): bool
    {
        return array_key_exists($appKey, config('pwa.apps', []));
    }
}
