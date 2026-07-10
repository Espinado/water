<?php

namespace Tests\Feature\Auth;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get($this->residentUrl('/login'));

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['apartment_id' => Apartment::factory()->create()->id]);

        $component = Volt::test('pages.auth.login')
            ->set('pwaApp', 'resident')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create(['apartment_id' => Apartment::factory()->create()->id]);

        $this->actingAs($user);

        $response = $this->get($this->residentUrl('/dashboard'));

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('login.resident', absolute: false));

        $this->assertGuest();
    }

    public function test_user_can_log_in_after_logout_without_csrf_error(): void
    {
        $user = User::factory()->create(['apartment_id' => Apartment::factory()->create()->id]);

        $this->actingAs($user);

        Volt::test('layout.navigation')->call('logout');

        $this->assertGuest();

        Volt::test('pages.auth.login')
            ->set('pwaApp', 'resident')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
