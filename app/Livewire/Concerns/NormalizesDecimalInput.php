<?php

namespace App\Livewire\Concerns;

use App\Support\DecimalInput;

trait NormalizesDecimalInput
{
    public function updated($property): void
    {
        $fields = $this->decimalInputProperties ?? [];

        if (! in_array($property, $fields, true)) {
            return;
        }

        $value = $this->{$property};

        if (! is_string($value)) {
            return;
        }

        $normalized = DecimalInput::normalize($value);

        if ($normalized !== $value) {
            $this->{$property} = $normalized;
        }
    }
}
