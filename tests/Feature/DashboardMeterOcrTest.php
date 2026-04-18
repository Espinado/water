<?php

namespace Tests\Feature;

use App\Contracts\VisionDocumentTextDetector;
use App\Livewire\Dashboard;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Mockery;
use Tests\Support\VisionAnnotationTestData;
use Tests\TestCase;

class DashboardMeterOcrTest extends TestCase
{
    use RefreshDatabase;

    private string $credentialsPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0, config('app.timezone')));

        $base = tempnam(sys_get_temp_dir(), 'gcv_sa_feat_');
        $this->assertNotFalse($base);
        unlink($base);
        $this->credentialsPath = $base.'.json';
        file_put_contents($this->credentialsPath, json_encode([
            'type' => 'service_account',
            'project_id' => 'feat-test',
        ]));
        config([
            'google_vision.enabled' => true,
            'google_vision.credentials_path' => $this->credentialsPath,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->credentialsPath !== '' && is_file($this->credentialsPath)) {
            @unlink($this->credentialsPath);
        }
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public function test_recognize_meter_from_photo_uses_mocked_vision_and_fills_fields(): void
    {
        $annotation = VisionAnnotationTestData::textAnnotationFromMeterWords([
            ['text' => '10', 'x' => 5],
            ['text' => '20', 'x' => 100],
        ]);

        $mock = Mockery::mock(VisionDocumentTextDetector::class);
        $mock->shouldReceive('detect')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn(['annotation' => $annotation, 'error' => null]);

        $this->app->instance(VisionDocumentTextDetector::class, $mock);

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();

        $user = User::factory()->create([
            'apartment_id' => $apartment->id,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('meterPhoto', UploadedFile::fake()->image('meters.jpg', 20, 20))
            ->call('recognizeMeterFromPhoto')
            ->assertSet('cold_m3', '10')
            ->assertSet('hot_m3', '20')
            ->assertSet('meterPhoto', null)
            ->assertSessionHas('reading_ocr_hint');
    }
}
