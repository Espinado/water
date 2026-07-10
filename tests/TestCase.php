<?php

namespace Tests;

use App\Services\AppHost;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function residentUrl(string $path = '/'): string
    {
        return app(AppHost::class)->absoluteUrl(AppHost::RESIDENT, $path);
    }

    protected function managerUrl(string $path = '/'): string
    {
        return app(AppHost::class)->absoluteUrl(AppHost::MANAGER, $path);
    }
}
