<?php

namespace Tests\Unit;

use App\Services\GeminiMeterReadingRecognizer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiMeterReadingRecognizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ai_vision.enabled', true);
        Config::set('ai_vision.provider', 'gemini');
        Config::set('ai_vision.timeout', 30);
        Config::set('ai_vision.gemini.api_key', 'test-key');
        Config::set('ai_vision.gemini.model', 'gemini-2.5-flash');
        Config::set('ai_vision.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
    }

    public function test_returns_error_when_disabled_and_makes_no_request(): void
    {
        Config::set('ai_vision.enabled', false);
        Http::fake();

        $result = (new GeminiMeterReadingRecognizer)->recognize('bytes', 'image/jpeg', 'ХВС');

        $this->assertNull($result['value']);
        $this->assertStringContainsString('AI_VISION_ENABLED', $result['error']);
        Http::assertNothingSent();
    }

    public function test_returns_error_when_api_key_missing_and_makes_no_request(): void
    {
        Config::set('ai_vision.gemini.api_key', '');
        Http::fake();

        $result = (new GeminiMeterReadingRecognizer)->recognize('bytes', 'image/jpeg', 'ХВС');

        $this->assertNull($result['value']);
        $this->assertStringContainsString('GEMINI_API_KEY', $result['error']);
        Http::assertNothingSent();
    }

    public function test_parses_reading_value_from_model_json(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode(['value' => '462.412', 'readable' => true, 'confidence' => 0.95]),
                    ]]],
                    'finishReason' => 'STOP',
                ]],
            ], 200),
        ]);

        $result = (new GeminiMeterReadingRecognizer)->recognize('bytes', 'image/jpeg', 'ХВС');

        $this->assertSame('462.412', $result['value']);
        $this->assertSame(0.95, $result['confidence']);
        $this->assertNull($result['error']);
    }

    public function test_parses_json_wrapped_in_code_fence(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => "```json\n{\"value\": \"80.792\", \"readable\": true}\n```",
                    ]]],
                ]],
            ], 200),
        ]);

        $result = (new GeminiMeterReadingRecognizer)->recognize('bytes', 'image/jpeg', 'ГВС');

        $this->assertSame('80.792', $result['value']);
        $this->assertNull($result['error']);
    }

    public function test_unreadable_dial_returns_null_value_without_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode(['value' => '', 'readable' => false]),
                    ]]],
                ]],
            ], 200),
        ]);

        $result = (new GeminiMeterReadingRecognizer)->recognize('bytes', 'image/jpeg', 'ХВС');

        $this->assertNull($result['value']);
        $this->assertNull($result['error']);
    }

    public function test_api_failure_returns_user_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'quota exceeded'],
            ], 429),
        ]);

        $result = (new GeminiMeterReadingRecognizer)->recognize('bytes', 'image/jpeg', 'ХВС');

        $this->assertNull($result['value']);
        $this->assertStringContainsString('429', $result['error']);
        $this->assertStringContainsString('quota exceeded', $result['error']);
    }

    public function test_sends_api_key_header_and_inline_image(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode(['value' => '1.5', 'readable' => true])]]],
                ]],
            ], 200),
        ]);

        (new GeminiMeterReadingRecognizer)->recognize('rawbytes', 'image/png', 'ХВС');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($request->url(), 'gemini-2.5-flash:generateContent')
                && data_get($body, 'contents.0.parts.1.inline_data.mime_type') === 'image/png'
                && data_get($body, 'contents.0.parts.1.inline_data.data') === base64_encode('rawbytes')
                && data_get($body, 'generationConfig.responseMimeType') === 'application/json';
        });
    }
}
