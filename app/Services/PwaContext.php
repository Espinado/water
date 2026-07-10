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
        $appHost = app(AppHost::class);

        if ($appHost->isManager($request)) {
            return AppHost::MANAGER;
        }

        if ($appHost->isResident($request)) {
            if ($request->routeIs('pwa.install', 'pwa.open')) {
                $app = $request->route('app');
                if (is_string($app) && $this->isValidApp($app)) {
                    return $app;
                }
            }

            return AppHost::RESIDENT;
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

        return $this->appConfig($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function appConfig(string $appKey): array
    {
        $config = config("pwa.apps.{$appKey}");

        if (! $this->usesLocalPwaNames()) {
            return $config;
        }

        return array_merge($config, [
            'name' => $this->localPwaName((string) $config['name']),
            'short_name' => $this->localPwaName((string) $config['short_name']),
        ]);
    }

    public function usesLocalPwaNames(): bool
    {
        return app()->environment('local');
    }

    protected function localPwaName(string $name): string
    {
        if (preg_match('/\btest\b/i', $name)) {
            return $name;
        }

        return "{$name} test";
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
        return $user->role === UserRole::Manager ? AppHost::MANAGER : AppHost::RESIDENT;
    }

    public function loginRoute(string $appKey): string
    {
        return (string) config("pwa.apps.{$appKey}.login_route", 'login');
    }

    public function manifestRoute(string $appKey): string
    {
        return (string) config("pwa.apps.{$appKey}.manifest_route", 'pwa.manifest');
    }

    public function manifestOrigin(string $appKey): string
    {
        return rtrim(app(AppHost::class)->urlFor($appKey), '/');
    }

    public function manifestId(string $appKey): string
    {
        $config = $this->appConfig($appKey);

        return $this->manifestOrigin($appKey).'/'.$config['id'];
    }

    public function manifestStartUrl(string $appKey): string
    {
        $config = $this->appConfig($appKey);

        return $this->manifestOrigin($appKey).$config['start_url'];
    }

    public function manifestScope(string $appKey): string
    {
        return $this->manifestOrigin($appKey).'/';
    }

    public function manifestUrl(string $appKey): string
    {
        $manifestRoute = $this->manifestRoute($appKey);

        if ($appKey === AppHost::MANAGER) {
            return route($manifestRoute);
        }

        return route($manifestRoute, ['app' => $appKey]);
    }

    public function installRoute(string $appKey): string
    {
        return (string) config("pwa.apps.{$appKey}.install_route", 'pwa.install');
    }

    public function openRoute(string $appKey): string
    {
        return (string) config("pwa.apps.{$appKey}.open_route", 'pwa.open');
    }

    public function continueRoute(string $appKey): string
    {
        return (string) config("pwa.apps.{$appKey}.continue_route", 'pwa.continue');
    }

    public function installUrl(string $appKey, array $query = []): string
    {
        $appHost = app(AppHost::class);

        if ($appKey === AppHost::MANAGER) {
            $url = $appHost->absoluteUrl(AppHost::MANAGER, '/app');
        } else {
            $url = route($this->installRoute($appKey), ['app' => $appKey]);
        }

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return $url;
    }

    public function continueUrl(string $appKey): string
    {
        if ($appKey === AppHost::MANAGER) {
            return route($this->continueRoute($appKey));
        }

        return route($this->continueRoute($appKey), ['app' => $appKey]);
    }

    public function openUrl(string $appKey): string
    {
        $appHost = app(AppHost::class);

        if ($appKey === AppHost::MANAGER) {
            return $appHost->absoluteUrl(AppHost::MANAGER, '/app/open');
        }

        return route($this->openRoute($appKey), ['app' => $appKey]);
    }
}
