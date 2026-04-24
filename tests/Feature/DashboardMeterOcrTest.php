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

    public function test_recognize_cold_and_hot_meters_from_separate_photos(): void
    {
        $coldAnnotation = VisionAnnotationTestData::textAnnotationFromMeterWords([
            ['text' => '10', 'x' => 5],
        ], plainText: '00010 m3');
        $hotAnnotation = VisionAnnotationTestData::textAnnotationFromMeterWords([
            ['text' => '20', 'x' => 5],
        ], plainText: '00020 m3');

        $mock = Mockery::mock(VisionDocumentTextDetector::class);
        $mock->shouldReceive('detect')
            ->twice()
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn(
                ['annotation' => $coldAnnotation, 'error' => null],
                ['annotation' => $hotAnnotation, 'error' => null],
            );

        $this->app->instance(VisionDocumentTextDetector::class, $mock);

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();

        $user = User::factory()->create([
            'apartment_id' => $apartment->id,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('coldMeterPhoto', UploadedFile::fake()->image('cold.jpg', 20, 20))
            ->call('recognizeColdMeterFromPhoto')
            ->assertSet('cold_m3', '10')
            ->assertSet('coldMeterPhoto', null)
            ->set('hotMeterPhoto', UploadedFile::fake()->image('hot.jpg', 20, 20))
            ->call('recognizeHotMeterFromPhoto')
            ->assertSet('hot_m3', '20')
            ->assertSet('hotMeterPhoto', null)
            ->assertSee('Подставлено распознанное значение');
    }
}
