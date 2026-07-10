<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AppHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanUseCurrentApp
{
    public function __construct(private AppHost $appHost) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard()->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($this->appHost->isManager($request) && ! $user->canUseManagerApp()) {
            return $this->rejectUserForCurrentApp(
                $request,
                __('Эта учётная запись не имеет доступа к приложению управляющего.'),
                'login.manager',
            );
        }

        if ($this->appHost->isResident($request) && ! $user->canUseResidentApp()) {
            return $this->rejectUserForCurrentApp(
                $request,
                __('Эта учётная запись не имеет доступа к приложению жильца.'),
                'login.resident',
            );
        }

        return $next($request);
    }

    private function rejectUserForCurrentApp(Request $request, string $message, string $loginRoute): Response
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->forgetSharedAuthCookies($request);

        return redirect()->route($loginRoute)->with('status', $message);
    }

    private function forgetSharedAuthCookies(Request $request): void
    {
        $recaller = Auth::guard()->getRecallerName();
        $legacyDomain = '.'.$this->appHost->residentHost();

        Cookie::queue(Cookie::forget($recaller));
        Cookie::queue(Cookie::forget($recaller, '/', $legacyDomain));

        foreach ([
            'water-session',
            $this->appHost->sessionCookieName(AppHost::RESIDENT),
            $this->appHost->sessionCookieName(AppHost::MANAGER),
        ] as $cookieName) {
            Cookie::queue(Cookie::forget($cookieName));
            Cookie::queue(Cookie::forget($cookieName, '/', $legacyDomain));
            Cookie::queue(Cookie::forget($cookieName, '/', $request->getHost()));
        }
    }
}
