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
            ->assertJsonFragment(['id' => 'k16-resident', 'start_url' => '/dashboard']);

        $this->get('/manifest/manager.webmanifest')
            ->assertOk()
            ->assertJsonFragment(['id' => 'k16-manager', 'start_url' => '/manager']);
    }

    public function test_service_worker_is_available(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
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
