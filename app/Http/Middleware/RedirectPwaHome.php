<?php

namespace App\Http\Middleware;

use App\Services\PwaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPwaHome
{
    public function __construct(private PwaContext $pwa) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $appKey = $request->cookie(config('pwa.cookie'));
        if (! is_string($appKey) || ! $this->pwa->isValidApp($appKey)) {
            return $next($request);
        }

        if ($appKey === 'manager' && $user->isManager() && $request->is('dashboard')) {
            return redirect()->route('manager.dashboard');
        }

        if ($appKey === 'resident' && $user->isResident() && $request->is('manager', 'manager/*')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
