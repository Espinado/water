<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppHost
{
    public const RESIDENT = 'resident';

    public const MANAGER = 'manager';

    public function residentUrl(): string
    {
        return (string) config('domains.resident_url');
    }

    public function managerUrl(): string
    {
        return (string) config('domains.manager_url');
    }

    public function residentHost(): string
    {
        return (string) parse_url($this->residentUrl(), PHP_URL_HOST);
    }

    public function managerHost(): string
    {
        return (string) parse_url($this->managerUrl(), PHP_URL_HOST);
    }

    public function urlFor(string $app): string
    {
        return $app === self::MANAGER ? $this->managerUrl() : $this->residentUrl();
    }

    public function hostFor(string $app): string
    {
        return $app === self::MANAGER ? $this->managerHost() : $this->residentHost();
    }

    public function forRequest(?Request $request = null): string
    {
        $request ??= request();

        return $request->getHost() === $this->managerHost()
            ? self::MANAGER
            : self::RESIDENT;
    }

    public function isManager(?Request $request = null): bool
    {
        return $this->forRequest($request) === self::MANAGER;
    }

    public function isResident(?Request $request = null): bool
    {
        return $this->forRequest($request) === self::RESIDENT;
    }

    public function sessionCookieName(string $app): string
    {
        $configured = config("domains.session_cookies.{$app}");

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $slug = Str::slug((string) config('app.name', 'laravel'));

        return "{$slug}-{$app}-session";
    }

    public function absoluteUrl(string $app, string $path = '/'): string
    {
        $base = rtrim($this->urlFor($app), '/');
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? $base.'/' : $base.$path;
    }

    public function configureForRequest(Request $request): void
    {
        $app = $this->forRequest($request);

        config([
            'session.cookie' => $this->sessionCookieName($app),
        ]);

        $rootUrl = $this->urlFor($app);
        config(['app.url' => $rootUrl]);
        url()->forceRootUrl($rootUrl);

        $scheme = parse_url($rootUrl, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            url()->forceScheme($scheme);
        }
    }
}
