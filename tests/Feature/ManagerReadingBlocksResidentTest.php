<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Manager\MeterReadings;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerReadingBlocksResidentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_manager_entry_blocks_resident_resubmission_in_production_mode(): void
    {
        // Рабочий режим: окно открыто, но без байпаса.
        config()->set('water.submission_window_bypass', false);
        config()->set('water.meter_reading_gate_bypass', false);

        // 29 апреля — окно за апрель открыто для жильца.
        Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, config('app.timezone')));

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '7']);
        $manager = User::factory()->manager()->create();
        $resident = User::factory()->create(['apartment_id' => $apartment->id]);

        // До ввода управляющим жилец может сдавать показания.
        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->assertSet('residentSubmittedForCurrentPeriod', false);

        // Управляющий вводит показания за апрель 2026.
        Livewire::actingAs($manager)
            ->test(MeterReadings::class)
            ->set('building_id', $building->id)
            ->set('year', 2026)
            ->set('month', 4)
            ->call('startEditApartment', $apartment->id)
            ->set('edit_cold', '100')
            ->set('edit_hot', '50')
            ->call('saveEditingApartment')
            ->assertHasNoErrors();

        // Теперь у жильца повторная сдача закрыта.
        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->assertSet('residentSubmittedForCurrentPeriod', true);
    }

    public function test_manager_entry_does_not_block_resident_in_test_mode(): void
    {
        config()->set('water.submission_window_bypass', true);

        Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, config('app.timezone')));

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '8']);
        $manager = User::factory()->manager()->create();
        $resident = User::factory()->create(['apartment_id' => $apartment->id]);

        Livewire::actingAs($manager)
            ->test(MeterReadings::class)
            ->set('building_id', $building->id)
            ->set('year', 2026)
            ->set('month', 4)
            ->call('startEditApartment', $apartment->id)
            ->set('edit_cold', '100')
            ->set('edit_hot', '50')
            ->call('saveEditingApartment')
            ->assertHasNoErrors();

        // В тестовом режиме блок остаётся открытым.
        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->assertSet('residentSubmittedForCurrentPeriod', false);
    }

    public function test_meter_input_warning_visible_only_during_active_submission_window(): void
    {
        config()->set('water.submission_window_bypass', false);
        config()->set('water.meter_reading_gate_bypass', false);

        Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, config('app.timezone')));

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '9']);
        $resident = User::factory()->create(['apartment_id' => $apartment->id]);

        $instruction = 'откроется камера';

        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->assertSet('residentMeterInputActive', true)
            ->assertSee(__('Важно'))
            ->assertSee($instruction);

        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->set('cold_m3', '100')
            ->set('hot_m3', '50')
            ->call('saveReading')
            ->assertHasNoErrors()
            ->assertSet('residentMeterInputActive', false)
            ->assertDontSee($instruction)
            ->assertSee(__('Показания за этот период приняты. Форма ввода закрыта.'));

        Carbon::setTestNow(Carbon::create(2026, 4, 20, 12, 0, 0, config('app.timezone')));

        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->assertSet('residentMeterInputActive', false)
            ->assertDontSee($instruction);
    }
}
