<?php

namespace Tests\Unit;

use App\Models\Apartment;
use App\Models\Building;
use App\Services\MeterReadingSubmissionNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MeterReadingSubmissionNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_and_pull_for_matching_period(): void
    {
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '12']);

        $notifier = app(MeterReadingSubmissionNotifier::class);
        $notifier->notify($apartment, 2026, 4, enteredByManager: false);

        $events = $notifier->pullForBuildingPeriod((int) $building->id, 2026, 4);

        $this->assertCount(1, $events);
        $this->assertSame('12', $events[0]['apartment_number']);
        $this->assertSame([], $notifier->pullForBuildingPeriod((int) $building->id, 2026, 4));
    }

    public function test_does_not_notify_when_entered_by_manager(): void
    {
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();

        app(MeterReadingSubmissionNotifier::class)->notify($apartment, 2026, 4, enteredByManager: true);

        $this->assertSame([], app(MeterReadingSubmissionNotifier::class)->pullForBuildingPeriod((int) $building->id, 2026, 4));
    }

    public function test_keeps_events_for_other_periods_in_cache(): void
    {
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '5']);

        $notifier = app(MeterReadingSubmissionNotifier::class);
        $notifier->notify($apartment, 2026, 3, enteredByManager: false);
        $notifier->notify($apartment, 2026, 4, enteredByManager: false);

        $this->assertCount(1, $notifier->pullForBuildingPeriod((int) $building->id, 2026, 4));
        $this->assertCount(1, $notifier->pullForBuildingPeriod((int) $building->id, 2026, 3));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }
}
