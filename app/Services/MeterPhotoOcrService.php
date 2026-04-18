<?php

namespace App\Services;

use App\Contracts\VisionDocumentTextDetector;
use Google\Cloud\Vision\V1\TextAnnotation;
use Google\Cloud\Vision\V1\Word;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeterPhotoOcrService
{
    public function __construct(
        protected VisionDocumentTextDetector $documentTextDetector,
    ) {}

    /**
     * @return array{cold: ?string, hot: ?string, hint: string, raw_snippet: ?string}
     */
    public function suggestFromImageBytes(string $imageBinary): array
    {
        if (! config('google_vision.enabled')) {
            return $this->ocrResult(null, null, 'Распознавание отключено. Включите GOOGLE_VISION_ENABLED и укажите ключ в GOOGLE_APPLICATION_CREDENTIALS.');
        }

        $path = $this->resolveGoogleCredentialsPath();
        if ($path === '') {
            return $this->ocrResult(
                null,
                null,
                'Не найден или не читается файл ключа Google. В .env задайте GOOGLE_APPLICATION_CREDENTIALS абсолютным путём '
                .'(например: '.storage_path('app/private/ваш-ключ.json').'), положите JSON в storage/app/private/ и проверьте права chmod для пользователя веб-сервера.',
            );
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            return $this->ocrResult(null, null, 'Файл ключа Google не является корректным JSON.');
        }

        try {
            $detected = $this->documentTextDetector->detect($imageBinary, $json);

            if ($detected['error'] !== null) {
                return $this->ocrResult(null, null, $detected['error']);
            }

            $annotation = $detected['annotation'];
            if ($annotation === null) {
                return $this->ocrResult(null, null, 'На фото не найден текст. Сделайте крупнее цифры и без бликов.');
            }

            $ordered = $this->orderedMeterValuesFromAnnotation($annotation);
            if (count($ordered) === 0) {
                $snippet = mb_substr($annotation->getText(), 0, 200);

                return $this->ocrResult(
                    null,
                    null,
                    'Текст найден, но похожие на показания числа не распознаны. Введите значения вручную.',
                    $snippet !== '' ? $snippet : null,
                );
            }

            $cold = $ordered[0] ?? null;
            $hot = $ordered[1] ?? null;

            $hint = count($ordered) >= 2
                ? 'Подставлены первые два числа слева направо (сверху вниз). Проверьте и при необходимости исправьте.'
                : 'Найдено одно число — заполните второе показание вручную.';

            return $this->ocrResult($cold, $hot, $hint);
        } catch (Throwable $e) {
            Log::warning('Meter OCR failed', ['exception' => $e]);

            return $this->ocrResult(null, null, 'Ошибка распознавания: '.$e->getMessage());
        }
    }

    /**
     * @return array{cold: ?string, hot: ?string, hint: string, raw_snippet: ?string}
     */
    /**
     * Абсолютный путь к JSON ключу или пустая строка.
     */
    protected function resolveGoogleCredentialsPath(): string
    {
        $raw = trim((string) config('google_vision.credentials_path'));
        if ($raw === '') {
            return '';
        }

        $normalized = str_replace('\\', DIRECTORY_SEPARATOR, $raw);
        $privateDir = storage_path('app'.DIRECTORY_SEPARATOR.'private');
        $fileName = basename($normalized);

        $candidates = [$normalized];

        if (! $this->isAbsoluteFilesystemPath($normalized)) {
            $candidates[] = base_path(str_replace('/', DIRECTORY_SEPARATOR, ltrim($raw, '/')));
        }

        $candidates[] = $privateDir.DIRECTORY_SEPARATOR.$fileName;

        $candidates = array_unique(array_filter($candidates));

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    protected function isAbsoluteFilesystemPath(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    protected function ocrResult(?string $cold, ?string $hot, string $hint, ?string $rawSnippet = null): array
    {
        return [
            'cold' => $cold,
            'hot' => $hot,
            'hint' => $hint,
            'raw_snippet' => $rawSnippet,
        ];
    }

    /**
     * @return list<string> нормализованные значения (точка как десятичный разделитель), по порядку чтения
     */
    protected function orderedMeterValuesFromAnnotation(TextAnnotation $annotation): array
    {
        $candidates = [];

        foreach ($annotation->getPages() as $page) {
            foreach ($page->getBlocks() as $block) {
                foreach ($block->getParagraphs() as $paragraph) {
                    foreach ($paragraph->getWords() as $word) {
                        $text = $this->wordText($word);
                        $normalized = $this->normalizeMeterToken($text);
                        if ($normalized === null) {
                            continue;
                        }

                        $key = $this->readingSortKey($word);
                        $candidates[] = ['value' => $normalized, 'key' => $key];
                    }
                }
            }
        }

        if ($candidates === []) {
            return $this->fallbackValuesFromPlainText($annotation->getText());
        }

        usort($candidates, fn (array $a, array $b) => $a['key'] <=> $b['key']);

        $out = [];
        $seen = [];
        foreach ($candidates as $c) {
            $v = $c['value'];
            if (isset($seen[$v])) {
                continue;
            }
            $seen[$v] = true;
            $out[] = $v;
            if (count($out) >= 2) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    protected function fallbackValuesFromPlainText(string $text): array
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if (preg_match_all('/\d+[.,]\d+|\d{4,}/u', $text, $m)) {
            $vals = [];
            foreach ($m[0] as $raw) {
                $n = $this->normalizeMeterToken($raw);
                if ($n !== null) {
                    $vals[] = $n;
                }
            }

            $vals = array_values(array_unique($vals));
            usort($vals, fn (string $a, string $b) => strlen($b) <=> strlen($a));

            return array_slice($vals, 0, 2);
        }

        return [];
    }

    protected function wordText(Word $word): string
    {
        $s = '';
        foreach ($word->getSymbols() as $symbol) {
            $s .= $symbol->getText();
        }

        return trim($s);
    }

    protected function readingSortKey(Word $word): float
    {
        $box = $word->getBoundingBox();
        if ($box === null || $box->getVertices()->count() === 0) {
            return 0.0;
        }

        $v = $box->getVertices()->offsetGet(0);
        $y = (float) ($v?->getY() ?? 0);
        $x = (float) ($v?->getX() ?? 0);

        return $y * 10_000 + $x;
    }

    protected function normalizeMeterToken(string $raw): ?string
    {
        $t = trim(str_replace([' ', "\u{00A0}"], '', $raw));
        $t = str_replace(',', '.', $t);
        $t = preg_replace('/[^0-9.]/u', '', $t) ?? '';

        if ($t === '' || ! preg_match('/^\d+(\.\d+)?$/', $t)) {
            return null;
        }

        if (! is_numeric($t)) {
            return null;
        }

        $f = (float) $t;
        if ($f < 0 || $f > 1_000_000) {
            return null;
        }

        return rtrim(rtrim(number_format($f, 3, '.', ''), '0'), '.') ?: '0';
    }
}
