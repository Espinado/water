<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;

class PwaContext
{
    public function appKey(?Request $request = null): ?string
    {
        $request ??= request();

        if ($request->routeIs('pwa.install', 'pwa.open')) {
            $app = $request->route('app');
            if (is_string($app) && $this->isValidApp($app)) {
                return $app;
            }
        }

        if ($request->routeIs('manager.*', 'login.manager')) {
            return 'manager';
        }

        if ($request->routeIs('dashboard', 'login.resident', 'login')) {
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

    public function appKeyForUser(User $user): string
    {
        return $user->role === UserRole::Manager ? 'manager' : 'resident';
    }

    public function loginRoute(string $appKey): string
    {
        return (string) config("pwa.apps.{$appKey}.login_route", 'login');
    }
}
