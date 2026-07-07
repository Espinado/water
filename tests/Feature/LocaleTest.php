<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_defaults_to_russian_without_browser_language(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Личный кабинет', false);

        $this->assertSame('ru', app()->getLocale());
    }

    public function test_resident_uses_latvian_from_browser_when_supported(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->withHeader('Accept-Language', 'lv-LV,lv;q=0.9')
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Personīgais kabinets', false);

        $this->assertSame('lv', app()->getLocale());
    }

    public function test_resident_falls_back_to_latvian_for_unsupported_browser_language(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Personīgais kabinets', false);

        $this->assertSame('lv', app()->getLocale());
    }

    public function test_resident_uses_russian_from_browser_when_supported(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Личный кабинет', false);

        $this->assertSame('ru', app()->getLocale());
    }

    public function test_locale_switch_persists_in_session_and_user_profile(): void
    {
        $resident = User::factory()->create(['locale' => null]);

        $this->actingAs($resident)
            ->from('/dashboard')
            ->get('/locale/lv')
            ->assertRedirect('/dashboard');

        $resident->refresh();
        $this->assertSame('lv', $resident->locale);
        $this->assertSame('lv', session('locale'));
    }

    public function test_user_profile_locale_overrides_browser_language(): void
    {
        $resident = User::factory()->create(['locale' => 'ru']);

        $this->actingAs($resident)
            ->withHeader('Accept-Language', 'lv-LV,lv;q=0.9')
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Личный кабинет', false);

        $this->assertSame('ru', app()->getLocale());
    }

    public function test_guest_can_switch_locale_via_session(): void
    {
        $this->get('/login')
            ->assertOk();

        $this->from('/login')
            ->get('/locale/lv')
            ->assertRedirect('/login');

        $this->assertSame('lv', session('locale'));

        $this->get('/login')
            ->assertOk()
            ->assertSee('Pieteikties', false);
    }
}
