<?php

namespace App\Services;

use App\Contracts\MeterReadingRecognizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiMeterReadingRecognizer implements MeterReadingRecognizer
{
    /**
     * {@inheritDoc}
     */
    public function recognize(string $imageBinary, string $mimeType, string $meterLabel): array
    {
        if (! config('ai_vision.enabled')) {
            return $this->fail('ИИ-распознавание отключено. Включите AI_VISION_ENABLED и укажите GEMINI_API_KEY в .env.');
        }

        $apiKey = trim((string) config('ai_vision.gemini.api_key'));
        if ($apiKey === '') {
            return $this->fail('Не задан GEMINI_API_KEY. Получите ключ в Google AI Studio (aistudio.google.com/apikey) и пропишите в .env.');
        }

        if ($imageBinary === '') {
            return $this->fail('Пустое изображение — нечего распознавать.');
        }

        $model = (string) config('ai_vision.gemini.model', 'gemini-2.0-flash');
        $endpoint = (string) config('ai_vision.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
        $timeout = (int) config('ai_vision.timeout', 30);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post($endpoint.'/models/'.$model.':generateContent', [
                    'contents' => [[
                        'parts' => [
                            ['text' => $this->prompt($meterLabel)],
                            ['inline_data' => [
                                'mime_type' => $mimeType !== '' ? $mimeType : 'image/jpeg',
                                'data' => base64_encode($imageBinary),
                            ]],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'value' => ['type' => 'string'],
                                'readable' => ['type' => 'boolean'],
                                'confidence' => ['type' => 'number'],
                            ],
                            'required' => ['value', 'readable'],
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            return $this->fail('Не удалось обратиться к Gemini: '.$e->getMessage());
        }

        if ($response->failed()) {
            $apiMessage = (string) data_get($response->json(), 'error.message', '');
            $detail = $apiMessage !== '' ? $apiMessage : mb_substr((string) $response->body(), 0, 200);

            return $this->fail('Gemini API ('.$response->status().'): '.$detail);
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if ($text === '') {
            $finish = (string) data_get($response->json(), 'candidates.0.finishReason', '');
            $hint = $finish !== '' ? ' (finishReason: '.$finish.')' : '';

            return $this->fail('Gemini не вернул текст ответа'.$hint.'.');
        }

        $parsed = $this->decodeModelJson($text);
        if ($parsed === null) {
            return $this->fail('Не удалось разобрать ответ Gemini. Введите значение вручную.', $text);
        }

        $readable = (bool) ($parsed['readable'] ?? false);
        $value = trim((string) ($parsed['value'] ?? ''));

        if (! $readable || $value === '') {
            return [
                'value' => null,
                'confidence' => $this->confidence($parsed),
                'error' => null,
                'raw' => $text,
            ];
        }

        return [
            'value' => $value,
            'confidence' => $this->confidence($parsed),
            'error' => null,
            'raw' => $text,
        ];
    }

    protected function prompt(string $meterLabel): string
    {
        return <<<PROMPT
        Ты считываешь показание бытового счётчика воды ({$meterLabel}) с фотографии его табло.

        На барабанном (одометровом) табло: чёрные цифры — целые кубометры (m³), красные цифры (обычно 3 последних) — дробная часть (тысячные доли m³). Итоговое показание = чёрные, точка, красные. Например, чёрные 00462 и красные 412 → 462.412.

        Правила:
        - Считывай ТОЛЬКО главное табло расхода. Игнорируй серийный/паспортный номер, штрихкод, год, диаметр, мелкие круговые стрелочные шкалы (x0.1, x0.01 и т.п.).
        - Убери ведущие нули из целой части. Дробную часть оставь как есть (до 3 знаков).
        - Разделитель — точка. Без пробелов, без единиц измерения.
        - Если табло не видно, размыто или прочитать нельзя — верни readable=false и value="".

        Верни строго JSON: {"value": "<число, например 462.412>", "readable": true|false, "confidence": <0..1>}.
        PROMPT;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeModelJson(string $text): ?array
    {
        $decoded = json_decode(trim($text), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // На случай, если модель обернула JSON в ```json ... ``` или добавила текст.
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function confidence(array $parsed): ?float
    {
        return isset($parsed['confidence']) && is_numeric($parsed['confidence'])
            ? (float) $parsed['confidence']
            : null;
    }

    /**
     * @return array{value: null, confidence: null, error: string, raw: ?string}
     */
    protected function fail(string $error, ?string $raw = null): array
    {
        return [
            'value' => null,
            'confidence' => null,
            'error' => $error,
            'raw' => $raw,
        ];
    }
}
