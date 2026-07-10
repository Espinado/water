<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Manager\HouseholdPanel;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use App\Services\MeterReadingSubmissionNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerApartmentSubmissionPollTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_poll_shows_toast_when_resident_submits_reading(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, config('app.timezone')));

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '7']);

        $manager = User::factory()->manager()->create();
        $resident = User::factory()->create(['apartment_id' => $apartment->id]);

        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->set('cold_m3', '100')
            ->set('hot_m3', '50')
            ->call('saveReading')
            ->assertHasNoErrors();

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->call('openBuilding', $building->id)
            ->set('statusYear', 2026)
            ->set('statusMonth', 4)
            ->call('pollSubmissionUpdates')
            ->assertDispatched('manager-submission-toast', message: 'Квартира № 7 сдала показания');

        Carbon::setTestNow();
    }

    public function test_resident_save_notifies_submission_service(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, config('app.timezone')));

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '3']);
        $resident = User::factory()->create(['apartment_id' => $apartment->id]);

        Livewire::actingAs($resident)
            ->test(Dashboard::class)
            ->set('cold_m3', '10')
            ->set('hot_m3', '20')
            ->call('saveReading');

        $events = app(MeterReadingSubmissionNotifier::class)->pullForBuildingPeriod((int) $building->id, 2026, 4);

        $this->assertCount(1, $events);
        $this->assertSame('3', $events[0]['apartment_number']);

        Carbon::setTestNow();
    }

    public function test_setup_default_filter_is_all(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->assertSet('statusFilter', 'all');
    }

    public function test_setup_honors_filter_query_param(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->withQueryParams(['filter' => 'debt'])
            ->test(HouseholdPanel::class)
            ->assertSet('statusFilter', 'debt');
    }
}
