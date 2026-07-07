<?php

namespace App\Console\Commands;

use App\Contracts\MeterReadingRecognizer;
use App\Services\MeterPhotoOcrService;
use Illuminate\Console\Command;

class AiVisionCheckCommand extends Command
{
    protected $signature = 'ai:vision-check
                            {--live : Выполнить живой запрос к Gemini (использует эталонное фото ХВС)}
                            {--fixture= : Путь к фото для живого теста (по умолчанию tests/Fixtures/meters/cold-meter.png)}';

    protected $description = 'Проверить настройки AI-распознавания счётчиков (Gemini) и при необходимости — живой запрос к API';

    public function handle(MeterReadingRecognizer $recognizer, MeterPhotoOcrService $ocrService): int
    {
        $this->components->info('Проверка AI-зрения для счётчиков воды');
        $this->newLine();

        $allOk = true;

        $allOk = $this->checkConfig($allOk);

        if ($this->option('live')) {
            $this->newLine();
            $allOk = $this->runLiveTest($recognizer, $ocrService, $allOk);
        } else {
            $this->newLine();
            $this->components->twoColumnDetail('Живой тест API', 'пропущен (добавьте --live)');
        }

        $this->newLine();

        if ($allOk) {
            $this->components->info('Всё в порядке. Распознавание по фото должно работать через Gemini.');

            return self::SUCCESS;
        }

        $this->components->error('Есть проблемы. Исправьте .env и выполните: php artisan config:clear');

        return self::FAILURE;
    }

    protected function checkConfig(bool $allOk): bool
    {
        $this->components->twoColumnDetail('APP_ENV', (string) config('app.env'));
        $this->components->twoColumnDetail(
            'Конфиг закэширован',
            app()->configurationIsCached() ? 'да (изменения .env не применятся без config:clear)' : 'нет',
        );

        $aiEnabled = (bool) config('ai_vision.enabled');
        $this->line($this->statusLine('AI_VISION_ENABLED', $aiEnabled));
        $allOk = $allOk && $aiEnabled;

        $apiKey = trim((string) config('ai_vision.gemini.api_key'));
        $keyOk = $apiKey !== '';
        $this->line($this->statusLine('GEMINI_API_KEY', $keyOk, $keyOk ? $this->maskSecret($apiKey) : 'не задан'));
        $allOk = $allOk && $keyOk;

        $model = (string) config('ai_vision.gemini.model', '');
        $this->components->twoColumnDetail('GEMINI_MODEL', $model !== '' ? $model : '—');
        $this->components->twoColumnDetail('AI_VISION_TIMEOUT', (string) config('ai_vision.timeout', 30).' с');

        $visionEnabled = (bool) config('google_vision.enabled');
        $this->components->twoColumnDetail(
            'GOOGLE_VISION_ENABLED (fallback)',
            $visionEnabled ? 'включён' : 'выключен',
        );

        if (! $aiEnabled) {
            $this->components->warn('AI выключен — на проде будет использоваться fallback (Cloud Vision + эвристики), показания могут быть неточными.');
        }

        if ($visionEnabled && ! $aiEnabled) {
            $this->components->warn('Рекомендуется включить AI_VISION_ENABLED=true и задать GEMINI_API_KEY.');
        }

        return $allOk;
    }

    protected function runLiveTest(MeterReadingRecognizer $recognizer, MeterPhotoOcrService $ocrService, bool $allOk): bool
    {
        $this->components->info('Живой тест API');

        $fixture = (string) ($this->option('fixture') ?: base_path('tests/Fixtures/meters/cold-meter.png'));
        if (! is_file($fixture)) {
            $this->components->error('Файл для теста не найден: '.$fixture);
            $this->line('Укажите --fixture=/path/to/photo.jpg или положите cold-meter.png в tests/Fixtures/meters/.');

            return false;
        }

        $bytes = file_get_contents($fixture);
        if ($bytes === false || $bytes === '') {
            $this->components->error('Не удалось прочитать файл: '.$fixture);

            return false;
        }

        $mime = match (strtolower(pathinfo($fixture, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };

        $this->components->twoColumnDetail('Фото', basename($fixture).' ('.number_format(strlen($bytes)).' байт)');

        $started = microtime(true);
        $ai = $recognizer->recognize($bytes, $mime, 'ХВС');
        $aiMs = (int) round((microtime(true) - $started) * 1000);

        if ($ai['error'] !== null) {
            $this->components->error('Gemini: '.$ai['error']);
            $allOk = false;
        } else {
            $value = $ai['value'] ?? '—';
            $confidence = $ai['confidence'] !== null ? number_format((float) $ai['confidence'], 2) : '—';
            $this->components->twoColumnDetail('Gemini ответ', (string) $value." (confidence: {$confidence}, {$aiMs} ms)");
        }

        $started = microtime(true);
        $pipeline = $ocrService->suggestSingleFromImageBytes($bytes, 'ХВС', $mime);
        $pipeMs = (int) round((microtime(true) - $started) * 1000);

        $this->components->twoColumnDetail('MeterPhotoOcrService', ($pipeline['value'] ?? '—')." ({$pipeMs} ms)");
        $this->components->twoColumnDetail('Подсказка', $pipeline['hint']);

        if ($pipeline['value'] === null && $ai['error'] === null) {
            $this->components->warn('API ответил, но значение не распознано (возможно, нечитаемое фото).');
        }

        return $allOk;
    }

    protected function statusLine(string $label, bool $ok, ?string $detail = null): string
    {
        $icon = $ok ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $detailText = $detail ?? ($ok ? 'да' : 'нет');

        return "  {$icon} {$label}: {$detailText}";
    }

    protected function maskSecret(string $value): string
    {
        $len = strlen($value);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 4).'…'.substr($value, -4);
    }
}
