<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiVisionCheckCommandTest extends TestCase
{
    public function test_fails_when_ai_disabled(): void
    {
        Config::set('ai_vision.enabled', false);
        Config::set('ai_vision.gemini.api_key', '');

        $this->artisan('ai:vision-check')
            ->expectsOutputToContain('AI_VISION_ENABLED')
            ->assertFailed();
    }

    public function test_passes_when_ai_configured(): void
    {
        Config::set('ai_vision.enabled', true);
        Config::set('ai_vision.gemini.api_key', 'test-key-12345678');
        Config::set('ai_vision.gemini.model', 'gemini-2.5-flash');

        $this->artisan('ai:vision-check')
            ->expectsOutputToContain('GEMINI_MODEL')
            ->assertSuccessful();
    }
}
