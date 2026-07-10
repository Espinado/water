<?php

use App\Http\Middleware\EnsureAccessNotSuspended;
use App\Http\Middleware\EnsureUserCanUseCurrentApp;
use App\Http\Middleware\EnsureUserIsManager;
use App\Http\Middleware\ConfigureAppHost;
use App\Http\Middleware\SetLocale;
use App\Services\AppHost;
use App\Services\PwaContext;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'manager' => EnsureUserIsManager::class,
        ]);

        $middleware->web(prepend: [
            ConfigureAppHost::class,
        ]);

        $middleware->web(replace: [
            \Illuminate\Session\Middleware\StartSession::class => \App\Http\Middleware\StartAppSession::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            EnsureUserCanUseCurrentApp::class,
            EnsureAccessNotSuspended::class,
        ]);

        $middleware->appendToPriorityList(
            Authenticate::class,
            EnsureUserCanUseCurrentApp::class,
        );

        $middleware->appendToPriorityList(
            EnsureUserCanUseCurrentApp::class,
            EnsureAccessNotSuspended::class,
        );

        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            $appHost = app(AppHost::class);
            $user = Auth::guard()->user();

            if ($appHost->isManager($request)) {
                return $user?->canUseManagerApp()
                    ? route('manager.dashboard')
                    : route('login.manager');
            }

            return $user?->canUseResidentApp()
                ? route('dashboard')
                : route('login.resident');
        });

        $middleware->redirectGuestsTo(function (Request $request) {
            $appHost = app(AppHost::class);

            if ($appHost->isManager($request)) {
                return route('login.manager');
            }

            $pwa = app(PwaContext::class);
            $appKey = $pwa->appKey($request);

            if ($appKey === AppHost::RESIDENT) {
                return route('login.resident');
            }

            return route('login.resident');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
