<?php

namespace Tests\Unit;

use App\Support\DecimalInput;
use PHPUnit\Framework\TestCase;

class DecimalInputTest extends TestCase
{
    public function test_comma_is_converted_to_dot(): void
    {
        $this->assertSame('12.34', DecimalInput::normalize('12,34'));
    }

    public function test_spaces_are_removed(): void
    {
        $this->assertSame('12.5', DecimalInput::normalize(' 12 , 5 '));
    }

    public function test_dot_is_kept(): void
    {
        $this->assertSame('45.84', DecimalInput::normalize('45.84'));
    }

    public function test_normalize_properties_updates_object_fields(): void
    {
        $target = (object) ['cold_m3' => '10,25', 'hot_m3' => '3.1'];

        DecimalInput::normalizeProperties($target, ['cold_m3', 'hot_m3']);

        $this->assertSame('10.25', $target->cold_m3);
        $this->assertSame('3.1', $target->hot_m3);
    }
}
