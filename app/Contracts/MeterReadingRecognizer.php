<?php

namespace App\Contracts;

interface MeterReadingRecognizer
{
    /**
     * Распознаёт показание одного счётчика с фотографии табло.
     *
     * @param  string  $imageBinary  бинарное содержимое изображения
     * @param  string  $mimeType  MIME изображения (image/jpeg, image/png, ...)
     * @param  string  $meterLabel  человекочитаемая метка счётчика (например, «ХВС»)
     * @return array{value: ?string, confidence: ?float, error: ?string, raw: ?string}
     *                                                                                 value — показание строкой (точка как разделитель) или null;
     *                                                                                 error — сообщение для пользователя при неуспехе, иначе null
     */
    public function recognize(string $imageBinary, string $mimeType, string $meterLabel): array;
}
