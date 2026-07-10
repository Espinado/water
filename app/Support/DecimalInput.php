<?php

namespace App\Support;

final class DecimalInput
{
    public static function normalize(string $value): string
    {
        $value = trim(str_replace(["\u{00A0}", ' '], '', $value));

        return str_replace(',', '.', $value);
    }

    public static function normalizeProperties(object $target, array $properties): void
    {
        foreach ($properties as $property) {
            if (! property_exists($target, $property)) {
                continue;
            }

            $value = $target->{$property};

            if (! is_string($value)) {
                continue;
            }

            $target->{$property} = self::normalize($value);
        }
    }
}
