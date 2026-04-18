<?php

namespace App\Contracts;

use Google\Cloud\Vision\V1\TextAnnotation;

interface VisionDocumentTextDetector
{
    /**
     * @param  array<string, mixed>  $serviceAccountJson
     * @return array{annotation: ?TextAnnotation, error: ?string} error — сообщение для пользователя; annotation — при успехе
     */
    public function detect(string $imageBinary, array $serviceAccountJson): array;
}
