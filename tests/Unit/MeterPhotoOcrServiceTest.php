<?php

namespace Tests\Unit;

use App\Contracts\MeterReadingRecognizer;
use App\Contracts\VisionDocumentTextDetector;
use App\Services\MeterPhotoOcrService;
use Google\Cloud\Vision\V1\TextAnnotation;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\Support\VisionAnnotationTestData;
use Tests\TestCase;

class MeterPhotoOcrServiceTest extends TestCase
{
    private string $credentialsPath = '';

    protected function setUp(): void
    {
        parent::setUp();
        $base = tempnam(sys_get_temp_dir(), 'gcv_sa_');
        if ($base !== false) {
            unlink($base);
            $this->credentialsPath = $base.'.json';
        } else {
            $this->credentialsPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gcv_sa_'.uniqid('', true).'.json';
        }
        file_put_contents($this->credentialsPath, json_encode([
            'type' => 'service_account',
            'project_id' => 'test-project',
        ]));
    }

    protected function tearDown(): void
    {
        if ($this->credentialsPath !== '' && is_file($this->credentialsPath)) {
            @unlink($this->credentialsPath);
        }
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_message_when_vision_disabled(): void
    {
        Config::set('google_vision.enabled', false);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->never();

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('binary');

        $this->assertNull($result['cold']);
        $this->assertStringContainsString('GOOGLE_VISION_ENABLED', $result['hint']);
    }

    public function test_returns_message_when_credentials_file_missing(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', '/nonexistent/path/key.json');

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->never();

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('binary');

        $this->assertStringContainsString('GOOGLE_APPLICATION_CREDENTIALS', $result['hint']);
    }

    public function test_returns_message_when_credentials_not_valid_json(): void
    {
        Config::set('google_vision.enabled', true);
        file_put_contents($this->credentialsPath, 'not-json{');
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->never();

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('binary');

        $this->assertStringContainsString('JSON', $result['hint']);
    }

    public function test_orders_two_readings_by_horizontal_position(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $annotation = VisionAnnotationTestData::textAnnotationFromMeterWords([
            ['text' => '200', 'x' => 400, 'y' => 0],
            ['text' => '100', 'x' => 20, 'y' => 0],
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')
            ->once()
            ->with('img-bytes', Mockery::type('array'))
            ->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('img-bytes');

        $this->assertSame('100', $result['cold']);
        $this->assertSame('200', $result['hot']);
        $this->assertStringContainsString('два числа', $result['hint']);
    }

    public function test_propagates_detector_api_error(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')
            ->once()
            ->andReturn(['annotation' => null, 'error' => 'Vision API: quota exceeded']);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('x');

        $this->assertSame('Vision API: quota exceeded', $result['hint']);
    }

    public function test_null_annotation_means_no_text_on_photo(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')
            ->once()
            ->andReturn(['annotation' => null, 'error' => null]);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('x');

        $this->assertStringContainsString('не найден текст', $result['hint']);
    }

    public function test_fallback_extracts_long_digit_sequences_from_plain_text(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        // Значения < 1_000_000 — иначе normalizeMeterToken отбрасывает «лишние» цифры.
        $annotation = new TextAnnotation([
            'pages' => [],
            'text' => 'Показания: 1234 и 888888 м³',
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')
            ->once()
            ->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('x');

        $this->assertSame('888888', $result['cold'], 'longer digit run is preferred first');
        $this->assertSame('1234', $result['hot']);
    }

    public function test_detector_exception_returns_user_hint(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')
            ->once()
            ->andThrow(new \RuntimeException('network down'));

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('x');

        $this->assertStringContainsString('network down', $result['hint']);
    }

    public function test_single_numeric_word_sets_hint_for_second_manual(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $annotation = VisionAnnotationTestData::textAnnotationFromMeterWords([
            ['text' => '42', 'x' => 0],
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->once()->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestFromImageBytes('x');

        $this->assertSame('42', $result['cold']);
        $this->assertNull($result['hot']);
        $this->assertStringContainsString('одно число', $result['hint']);
    }

    public function test_single_extract_prefers_five_plus_three_dial_before_m3_over_serial(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $annotation = new TextAnnotation([
            'pages' => [],
            'text' => '006929;11 CE M11 00462,412 m³',
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->once()->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestSingleFromImageBytes('x', 'ХВС');

        $this->assertSame('462.412', $result['value']);
    }

    public function test_single_extract_reads_space_between_whole_and_fraction_before_m3(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $annotation = new TextAnnotation([
            'pages' => [],
            'text' => '32279740-2012 PoWoGaz 00080 792 m³',
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->once()->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestSingleFromImageBytes('x', 'ГВС');

        $this->assertSame('80.792', $result['value']);
    }

    public function test_single_extract_joins_per_digit_ocr_runs_to_five_plus_three(): void
    {
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $annotation = new TextAnnotation([
            'pages' => [],
            'text' => '006929;11 CE 0 0 4 6 2 4 1 2 m³',
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->once()->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector);
        $result = $service->suggestSingleFromImageBytes('x', 'ХВС');

        $this->assertSame('462.412', $result['value']);
    }

    public function test_prefers_ai_recognizer_result_over_cloud_vision(): void
    {
        Config::set('ai_vision.enabled', true);
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $ai = Mockery::mock(MeterReadingRecognizer::class);
        $ai->shouldReceive('recognize')
            ->once()
            ->with('img', 'image/jpeg', 'ХВС')
            ->andReturn(['value' => '462.412', 'confidence' => 0.9, 'error' => null, 'raw' => null]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->never();

        $service = new MeterPhotoOcrService($detector, $ai);
        $result = $service->suggestSingleFromImageBytes('img', 'ХВС', 'image/jpeg');

        $this->assertSame('462.412', $result['value']);
        $this->assertStringContainsString('ИИ', $result['hint']);
    }

    public function test_falls_back_to_cloud_vision_when_ai_errors(): void
    {
        Config::set('ai_vision.enabled', true);
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $ai = Mockery::mock(MeterReadingRecognizer::class);
        $ai->shouldReceive('recognize')
            ->once()
            ->andReturn(['value' => null, 'confidence' => null, 'error' => 'Gemini API (429): quota', 'raw' => null]);

        $annotation = new TextAnnotation([
            'pages' => [],
            'text' => '32279740-2012 PoWoGaz 00080 792 m³',
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->once()->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector, $ai);
        $result = $service->suggestSingleFromImageBytes('img', 'ГВС', 'image/jpeg');

        $this->assertSame('80.792', $result['value']);
    }

    public function test_shows_ai_error_when_cloud_vision_disabled(): void
    {
        Config::set('ai_vision.enabled', true);
        Config::set('google_vision.enabled', false);

        $ai = Mockery::mock(MeterReadingRecognizer::class);
        $ai->shouldReceive('recognize')
            ->once()
            ->andReturn(['value' => null, 'confidence' => null, 'error' => 'Не задан GEMINI_API_KEY.', 'raw' => null]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->never();

        $service = new MeterPhotoOcrService($detector, $ai);
        $result = $service->suggestSingleFromImageBytes('img', 'ХВС', 'image/jpeg');

        $this->assertNull($result['value']);
        $this->assertStringContainsString('GEMINI_API_KEY', $result['hint']);
    }

    public function test_ai_disabled_uses_cloud_vision_directly(): void
    {
        Config::set('ai_vision.enabled', false);
        Config::set('google_vision.enabled', true);
        Config::set('google_vision.credentials_path', $this->credentialsPath);

        $ai = Mockery::mock(MeterReadingRecognizer::class);
        $ai->shouldReceive('recognize')->never();

        $annotation = new TextAnnotation([
            'pages' => [],
            'text' => '00080 792 m³',
        ]);

        $detector = Mockery::mock(VisionDocumentTextDetector::class);
        $detector->shouldReceive('detect')->once()->andReturn(['annotation' => $annotation, 'error' => null]);

        $service = new MeterPhotoOcrService($detector, $ai);
        $result = $service->suggestSingleFromImageBytes('img', 'ГВС', 'image/jpeg');

        $this->assertSame('80.792', $result['value']);
    }
}
