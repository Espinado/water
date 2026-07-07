<?php

namespace Tests\Feature;

use App\Contracts\MeterReadingRecognizer;
use App\Contracts\VisionDocumentTextDetector;
use App\Services\MeterPhotoOcrService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class FixtureMeterPhotoRecognitionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai_vision.enabled', true);
        Config::set('google_vision.enabled', false);
    }

    public function test_recognizes_cold_meter_fixture_via_ai(): void
    {
        $bytes = file_get_contents(base_path('tests/Fixtures/meters/cold-meter.png'));
        $this->assertNotFalse($bytes);

        $ai = Mockery::mock(MeterReadingRecognizer::class);
        $ai->shouldReceive('recognize')
            ->once()
            ->with($bytes, 'image/png', 'ХВС')
            ->andReturn([
                'value' => '464.952',
                'confidence' => 0.98,
                'error' => null,
                'raw' => null,
            ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->never();

        $result = (new MeterPhotoOcrService($detector, $ai))
            ->suggestSingleFromImageBytes($bytes, 'ХВС', 'image/png');

        $this->assertSame('464.952', $result['value']);
        $this->assertStringContainsString('ИИ', $result['hint']);
    }

    public function test_recognizes_hot_meter_fixture_via_ai(): void
    {
        $bytes = file_get_contents(base_path('tests/Fixtures/meters/hot-meter.png'));
        $this->assertNotFalse($bytes);

        $ai = Mockery::mock(MeterReadingRecognizer::class);
        $ai->shouldReceive('recognize')
            ->once()
            ->with($bytes, 'image/png', 'ГВС')
            ->andReturn([
                'value' => '81.976',
                'confidence' => 0.97,
                'error' => null,
                'raw' => null,
            ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->never();

        $result = (new MeterPhotoOcrService($detector, $ai))
            ->suggestSingleFromImageBytes($bytes, 'ГВС', 'image/png');

        $this->assertSame('81.976', $result['value']);
        $this->assertStringContainsString('ИИ', $result['hint']);
    }
}
