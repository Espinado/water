<?php

namespace Tests\Support;

use Google\Cloud\Vision\V1\Block;
use Google\Cloud\Vision\V1\BoundingPoly;
use Google\Cloud\Vision\V1\Page;
use Google\Cloud\Vision\V1\Paragraph;
use Google\Cloud\Vision\V1\Symbol;
use Google\Cloud\Vision\V1\TextAnnotation;
use Google\Cloud\Vision\V1\Vertex;
use Google\Cloud\Vision\V1\Word;

final class VisionAnnotationTestData
{
    /**
     * @param  array<int, array{text: string, x: float, y?: float}>  $words
     */
    public static function textAnnotationFromMeterWords(array $words, ?string $plainText = null): TextAnnotation
    {
        $builtWords = [];
        foreach ($words as $spec) {
            $builtWords[] = self::wordAt($spec['text'], $spec['x'], (float) ($spec['y'] ?? 0));
        }

        $paragraph = new Paragraph(['words' => $builtWords]);
        $block = new Block(['paragraphs' => [$paragraph]]);
        $page = new Page(['blocks' => [$block]]);

        $text = $plainText ?? implode("\n", array_column($words, 'text'));

        return new TextAnnotation([
            'pages' => [$page],
            'text' => $text,
        ]);
    }

    public static function wordAt(string $digits, float $x, float $y): Word
    {
        $symbols = [];
        foreach (mb_str_split($digits) as $ch) {
            $symbols[] = new Symbol(['text' => $ch]);
        }

        return new Word([
            'symbols' => $symbols,
            'bounding_box' => new BoundingPoly([
                'vertices' => [new Vertex(['x' => $x, 'y' => $y])],
            ]),
        ]);
    }
}
