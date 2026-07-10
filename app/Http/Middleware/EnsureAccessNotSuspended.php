<?php

namespace App\Http\Middleware;

use App\Services\AppHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccessNotSuspended
{
    public function __construct(private AppHost $appHost) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->access_suspended_at !== null) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $loginRoute = $this->appHost->isManager($request)
                ? 'login.manager'
                : 'login.resident';

            return redirect()->route($loginRoute)
                ->with('status', __('Доступ для вашей учётной записи отключён. Обратитесь к управляющему.'));
        }

        return $next($request);
    }
}
