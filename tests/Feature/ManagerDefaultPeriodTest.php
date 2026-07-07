<?php

namespace Tests\Feature;

use App\Livewire\Manager\MeterReadings;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerDefaultPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session()->flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_defaults_to_opening_month_at_window_start(): void
    {
        // 28 апреля — окно за апрель только открылось.
        Carbon::setTestNow(Carbon::create(2026, 4, 28, 12, 0, 0, config('app.timezone')));

        Livewire::actingAs(User::factory()->manager()->create())
            ->test(MeterReadings::class)
            ->assertSet('year', 2026)
            ->assertSet('month', 4);
    }

    public function test_defaults_to_previous_month_early_next_month(): void
    {
        // 5 мая — всё ещё окно за апрель (до 10 мая включительно).
        Carbon::setTestNow(Carbon::create(2026, 5, 5, 12, 0, 0, config('app.timezone')));

        Livewire::actingAs(User::factory()->manager()->create())
            ->test(MeterReadings::class)
            ->assertSet('year', 2026)
            ->assertSet('month', 4);
    }

    public function test_falls_back_to_current_month_outside_window(): void
    {
        // 20 мая — окно закрыто (11–27), период не определён → текущий календарный месяц.
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 12, 0, 0, config('app.timezone')));

        Livewire::actingAs(User::factory()->manager()->create())
            ->test(MeterReadings::class)
            ->assertSet('year', 2026)
            ->assertSet('month', 5);
    }

    public function test_resets_stale_session_to_actionable_period_during_window(): void
    {
        // 8 июля — окно за июнь; в сессии ошибочно июль.
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 12, 0, 0, config('app.timezone')));
        session(['manager.period_year' => 2026, 'manager.period_month' => 7]);

        Livewire::actingAs(User::factory()->manager()->create())
            ->test(MeterReadings::class)
            ->assertSet('year', 2026)
            ->assertSet('month', 6);
    }

    public function test_cannot_change_period_during_open_window(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 12, 0, 0, config('app.timezone')));

        Livewire::actingAs(User::factory()->manager()->create())
            ->test(MeterReadings::class)
            ->set('month', 7)
            ->assertSet('month', 6);
    }
}
