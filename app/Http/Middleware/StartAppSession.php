<?php

namespace App\Http\Middleware;

use App\Services\AppHost;
use App\Session\AppSessionManager;
use Closure;
use Illuminate\Session\Middleware\StartSession as Middleware;

class StartAppSession extends Middleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function handle($request, Closure $next)
    {
        app(AppHost::class)->configureForRequest($request);

        if ($this->manager instanceof AppSessionManager) {
            $this->manager->resetDriver();
        }

        return parent::handle($request, $next);
    }
}
