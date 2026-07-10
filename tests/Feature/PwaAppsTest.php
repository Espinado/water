<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaAppsTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_pages_are_available(): void
    {
        $this->get('/app/resident')
            ->assertOk()
            ->assertSee('K16 — жилец');

        $this->get('/app/manager')
            ->assertOk()
            ->assertSee('K16 — управляющий');
    }

    public function test_manifests_are_available(): void
    {
        $this->get('/manifest/resident.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonFragment(['id' => 'k16-resident', 'start_url' => '/dashboard'])
            ->assertJsonFragment(['src' => '/icons/resident/icon-192.png', 'purpose' => 'any'])
            ->assertJsonFragment(['src' => '/icons/resident/icon-maskable-512.png', 'purpose' => 'maskable']);

        $this->get('/manifest/manager.webmanifest')
            ->assertOk()
            ->assertJsonFragment(['id' => 'k16-manager', 'start_url' => '/manager'])
            ->assertJsonFragment(['src' => '/icons/manager/icon-192.png', 'purpose' => 'any']);
    }

    public function test_pwa_icon_files_exist(): void
    {
        foreach (['resident', 'manager'] as $app) {
            foreach (['icon-180.png', 'icon-192.png', 'icon-512.png', 'icon-maskable-512.png'] as $file) {
                $path = public_path("icons/{$app}/{$file}");
                $this->assertFileExists($path, "Missing {$path}");
                $this->assertGreaterThan(500, filesize($path), "Icon too small: {$path}");
            }

            $this->assertFileExists(public_path("icons/{$app}/icon.svg"));
        }
    }

    public function test_service_worker_is_available(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->assertSee('addEventListener(\'fetch\'', false);
    }

    public function test_resident_login_rejects_manager_account(): void
    {
        $manager = User::factory()->manager()->create();

        $this->get('/login/resident')->assertOk();

        \Livewire\Volt\Volt::test('pages.auth.login')
            ->set('pwaApp', 'resident')
            ->set('form.email', $manager->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('login.manager', absolute: false));
    }

    public function test_manager_pwa_redirects_manager_from_dashboard(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->withCookie('pwa_app', 'manager')
            ->get('/dashboard')
            ->assertRedirect(route('manager.dashboard'));
    }

    public function test_resident_pwa_redirects_resident_from_manager_routes(): void
    {
        $resident = User::factory()->create(['apartment_id' => null]);

        $this->actingAs($resident)
            ->withCookie('pwa_app', 'resident')
            ->get('/manager')
            ->assertRedirect(route('dashboard'));
    }
}
