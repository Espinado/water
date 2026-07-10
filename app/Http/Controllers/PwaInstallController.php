<?php

namespace App\Http\Controllers;

use App\Services\PwaContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PwaInstallController extends Controller
{
    public function show(string $app, PwaContext $pwa): View
    {
        abort_unless($pwa->isValidApp($app), 404);

        $pwa->rememberApp($app);

        return view('pwa.install', [
            'appKey' => $app,
            'appConfig' => config("pwa.apps.{$app}"),
            'welcome' => request()->boolean('welcome'),
            'authenticated' => auth()->check(),
        ]);
    }

    public function open(string $app, PwaContext $pwa): RedirectResponse
    {
        abort_unless($pwa->isValidApp($app), 404);

        $pwa->rememberApp($app);

        if (auth()->check()) {
            $user = auth()->user();
            if ($app === 'manager' && $user->isManager()) {
                return redirect()->route('manager.dashboard');
            }
            if ($app === 'resident' && $user->isResident()) {
                return redirect()->route('dashboard');
            }

            auth()->logout();
            request()->session()->regenerateToken();
        }

        return redirect()->route($pwa->loginRoute($app));
    }

    public function continue(string $app, PwaContext $pwa): RedirectResponse
    {
        abort_unless($pwa->isValidApp($app), 404);
        abort_unless(auth()->check(), 403);

        $pwa->rememberApp($app);
        $user = auth()->user();

        if ($app === 'manager' && $user->isManager()) {
            return redirect()->route('manager.dashboard');
        }

        if ($app === 'resident' && $user->isResident()) {
            return redirect()->route('dashboard');
        }

        abort(403);
    }
}
