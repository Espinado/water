<?php

namespace App\Http\Middleware;

use App\Services\AppHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigureAppHost
{
    public function __construct(private AppHost $appHost) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->appHost->configureForRequest($request);

        return $next($request);
    }
}
