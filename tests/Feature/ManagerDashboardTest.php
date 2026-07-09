<?php

namespace Tests\Feature;

use App\Livewire\Manager\ManagerDashboard;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\MeterReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_dashboard(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/manager')
            ->assertOk()
            ->assertSee(__('Главная'));
    }

    public function test_dashboard_shows_debt_count_for_selected_building(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, config('app.timezone')));

        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'K16']);
        $aptSubmitted = Apartment::factory()->for($building)->create(['number' => '1']);
        Apartment::factory()->for($building)->create(['number' => '2']);

        MeterReading::query()->create([
            'apartment_id' => $aptSubmitted->id,
            'year' => 2026,
            'month' => 4,
            'cold_m3' => 100,
            'hot_m3' => 50,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => false,
        ]);

        Livewire::actingAs($manager)
            ->test(ManagerDashboard::class)
            ->set('building_id', $building->id)
            ->set('statusYear', 2026)
            ->set('statusMonth', 4)
            ->tap(function ($component) {
                $stats = $component->instance()->stats;
                $this->assertSame(1, $stats['debt']);
                $this->assertSame(1, $stats['submitted']);
                $this->assertSame(2, $stats['total']);
            });

        Carbon::setTestNow();
    }
}
