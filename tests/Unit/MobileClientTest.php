<?php

namespace Tests\Unit;

use App\Support\MobileClient;
use Illuminate\Http\Request;
use Tests\TestCase;

class MobileClientTest extends TestCase
{
    public function test_detects_mobile_user_agents(): void
    {
        $request = Request::create('/', 'GET', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        ]);

        $this->assertTrue(MobileClient::isMobileRequest($request));
    }

    public function test_rejects_desktop_user_agents(): void
    {
        $request = Request::create('/', 'GET', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        ]);

        $this->assertFalse(MobileClient::isMobileRequest($request));
    }
}
