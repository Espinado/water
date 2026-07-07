<?php

namespace Tests\Feature;

use App\Livewire\Manager\ApartmentReadingsHistory;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\MeterReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerApartmentReadingsHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_entry_period_defaults_to_resident_submission_window(): void
    {
        // 8 июля: окно приёма открыто за июнь (28 июня — 15 июля).
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 12, 0, 0, config('app.timezone')));

        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->assertSet('entryYear', 2026)
            ->assertSet('entryMonth', 6)
            ->assertDontSee('id="entry-month"', false);
    }

    public function test_manager_entry_for_actionable_period_blocks_resident_form(): void
    {
        config()->set('water.submission_window_bypass', false);

        Carbon::setTestNow(Carbon::create(2026, 7, 8, 12, 0, 0, config('app.timezone')));

        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);
        $resident = User::factory()->create(['apartment_id' => $apartment->id]);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->set('entry_cold', '100')
            ->set('entry_hot', '50')
            ->call('saveEntry')
            ->assertHasNoErrors()
            ->assertSet('entryAlreadySubmitted', true);

        Livewire::actingAs($resident)
            ->test(\App\Livewire\Dashboard::class)
            ->assertSet('residentSubmittedForCurrentPeriod', true);
    }

    public function test_entry_form_hidden_when_reading_exists_and_editable_via_table(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 12, 0, 0, config('app.timezone')));

        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        $reading = MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 100,
            'hot_m3' => 50,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            // Форма ввода скрыта, кнопка Редактировать доступна в таблице.
            ->assertSet('entryAlreadySubmitted', true)
            ->assertDontSee('id="entry-cold"', false)
            ->call('startEdit', $reading->id)
            ->assertSet('editingId', $reading->id)
            ->set('edit_cold', '150.5')
            ->set('edit_hot', '75.25')
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $this->assertDatabaseHas('meter_readings', [
            'id' => $reading->id,
            'cold_m3' => 150.5,
            'hot_m3' => 75.25,
        ]);
    }

    public function test_defaults_to_latest_reading_period(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'K19']);
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2025,
            'month' => 3,
            'cold_m3' => 103,
            'hot_m3' => 53,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 4,
            'cold_m3' => 110,
            'hot_m3' => 60,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->assertSet('filterYear', 2026)
            ->assertSet('filterMonth', 4)
            ->assertSee('110.000');
    }

    public function test_filters_by_selected_year_and_month(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'K19']);
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2025,
            'month' => 3,
            'cold_m3' => 103,
            'hot_m3' => 53,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2025,
            'month' => 4,
            'cold_m3' => 104,
            'hot_m3' => 54,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->set('filterYear', 2025)
            ->set('filterMonth', 3)
            ->assertSee('103.000')
            ->assertDontSee('104.000');

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->set('filterYear', 2024)
            ->assertSet('filterMonth', 1)
            ->assertSee(__('Показаний за выбранный период нет.'));
    }

    public function test_manager_can_enter_reading_manually(): void
    {
        // Вне окна приёма управляющий может выбрать любой период.
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 12, 0, 0, config('app.timezone')));

        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->set('entryYear', 2026)
            ->set('entryMonth', 5)
            ->set('entry_cold', '123.5')
            ->set('entry_hot', '77.25')
            ->call('saveEntry')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('meter_readings', [
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 5,
            'entered_by_manager' => true,
        ]);
    }
}
