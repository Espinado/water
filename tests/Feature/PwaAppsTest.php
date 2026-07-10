<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaAppsTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_pages_are_available(): void
    {
        $this->get($this->residentUrl('/app/resident'))
            ->assertOk()
            ->assertSee('K16 — жилец');

        $this->get($this->managerUrl('/app'))
            ->assertOk()
            ->assertSee('K16 — управляющий');
    }

    public function test_manifests_are_available(): void
    {
        $residentOrigin = rtrim($this->residentUrl(), '/');
        $managerOrigin = rtrim($this->managerUrl(), '/');

        $this->get($this->residentUrl('/manifest/resident.webmanifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonFragment(['id' => "{$residentOrigin}/k16-resident"])
            ->assertJsonFragment(['start_url' => "{$residentOrigin}/app/resident/open"])
            ->assertJsonFragment(['scope' => "{$residentOrigin}/"])
            ->assertJsonFragment(['src' => '/icons/resident/icon-192.png', 'purpose' => 'any'])
            ->assertJsonFragment(['src' => '/icons/resident/icon-maskable-512.png', 'purpose' => 'maskable']);

        $this->get($this->managerUrl('/manifest.webmanifest'))
            ->assertOk()
            ->assertJsonFragment(['id' => "{$managerOrigin}/k16-manager"])
            ->assertJsonFragment(['start_url' => "{$managerOrigin}/app/open"])
            ->assertJsonFragment(['scope' => "{$managerOrigin}/"])
            ->assertJsonFragment(['src' => '/icons/manager/icon-192.png', 'purpose' => 'any']);
    }

    public function test_manifest_ids_are_distinct_per_subdomain(): void
    {
        $resident = $this->get($this->residentUrl('/manifest/resident.webmanifest'))->json('id');
        $manager = $this->get($this->managerUrl('/manifest.webmanifest'))->json('id');

        $this->assertNotSame($resident, $manager);
        $this->assertStringContainsString('k16-resident', (string) $resident);
        $this->assertStringContainsString('k16-manager', (string) $manager);
    }

    public function test_local_manifest_names_include_test(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->get($this->residentUrl('/manifest/resident.webmanifest'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'K16 — жилец test', 'short_name' => 'K16 test']);

        $this->get($this->managerUrl('/manifest.webmanifest'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'K16 — управляющий test', 'short_name' => 'K16 Pro test']);
    }

    public function test_pwa_open_sets_cookie_and_redirects_guest_to_correct_login(): void
    {
        $this->get($this->managerUrl('/app/open'))
            ->assertRedirect($this->managerUrl('/login'))
            ->assertCookie(config('pwa.cookie'), 'manager');

        $this->get($this->residentUrl('/app/resident/open'))
            ->assertRedirect($this->residentUrl('/login'))
            ->assertCookie(config('pwa.cookie'), 'resident');
    }

    public function test_pwa_open_redirects_authenticated_user_to_home(): void
    {
        $manager = User::factory()->manager()->create();
        $resident = User::factory()->create(['apartment_id' => Apartment::factory()->create()->id]);

        $this->actingAs($manager)
            ->get($this->managerUrl('/app/open'))
            ->assertRedirect($this->managerUrl('/dashboard'));

        $this->actingAs($resident)
            ->get($this->residentUrl('/app/resident/open'))
            ->assertRedirect($this->residentUrl('/dashboard'));
    }

    public function test_pwa_continue_requires_auth_and_matching_role(): void
    {
        $manager = User::factory()->manager()->create();
        $resident = User::factory()->create(['apartment_id' => Apartment::factory()->create()->id]);

        $this->actingAs($manager)
            ->get($this->managerUrl('/app/continue'))
            ->assertRedirect($this->managerUrl('/dashboard'));

        $this->actingAs($resident)
            ->get($this->residentUrl('/app/resident/continue'))
            ->assertRedirect($this->residentUrl('/dashboard'));

        auth()->logout();
        $this->flushSession();

        $this->get($this->managerUrl('/app/continue'))
            ->assertRedirect($this->managerUrl('/login'));
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

    public function test_service_worker_is_available_on_both_hosts(): void
    {
        $this->get($this->residentUrl('/sw.js'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->assertSee('addEventListener(\'fetch\'', false);

        $this->get($this->managerUrl('/sw.js'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
    }

    public function test_resident_login_rejects_manager_without_apartment(): void
    {
        $manager = User::factory()->manager()->create();

        $this->get($this->residentUrl('/login'))->assertOk();

        \Livewire\Volt\Volt::test('pages.auth.login')
            ->set('pwaApp', 'resident')
            ->set('form.email', $manager->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertNoRedirect()
            ->assertSee(__('Эта учётная запись не имеет доступа к приложению жильца'));
    }

    public function test_manager_with_apartment_can_login_to_resident_app(): void
    {
        $manager = User::factory()->manager()->create();
        $apartment = \App\Models\Apartment::factory()->create();
        $manager->update(['apartment_id' => $apartment->id]);

        \Livewire\Volt\Volt::test('pages.auth.login')
            ->set('pwaApp', 'resident')
            ->set('form.email', $manager->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($manager);
    }

    public function test_manager_logout_returns_to_manager_login(): void
    {
        $manager = User::factory()->manager()->create();

        \Livewire\Volt\Volt::actingAs($manager)
            ->test('layout.manager-header')
            ->call('logout')
            ->assertRedirect(route('login.manager', absolute: false));

        $this->assertGuest();
    }

    public function test_manager_and_resident_sessions_are_independent(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get($this->managerUrl('/dashboard'))
            ->assertOk();

        auth()->logout();
        $this->flushSession();

        $this->get($this->residentUrl('/dashboard'))
            ->assertRedirect($this->residentUrl('/login'));
    }

    public function test_legacy_manager_pwa_urls_redirect_to_subdomain(): void
    {
        $this->get($this->residentUrl('/app/manager'))
            ->assertRedirect($this->managerUrl('/app'));
    }
}
