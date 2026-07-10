<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ResidentProfileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_resident_can_open_profile(): void
    {
        $user = User::factory()->unverified()->create([
            'apartment_id' => Apartment::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->get($this->residentUrl('/profile'))
            ->assertOk()
            ->assertSee(__('Профиль'), false);
    }

    public function test_profile_route_does_not_redirect_to_dashboard(): void
    {
        $user = User::factory()->create([
            'apartment_id' => Apartment::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->get($this->residentUrl('/profile'))
            ->assertOk()
            ->assertDontSee(__('Skaitītāju rādījumi'), false);
    }

    public function test_profile_opens_after_livewire_login(): void
    {
        $user = User::factory()->create([
            'apartment_id' => Apartment::factory()->create()->id,
        ]);

        Volt::test('pages.auth.login')
            ->set('pwaApp', 'resident')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $this->assertAuthenticated();

        $this->get($this->residentUrl('/profile'))
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form');
    }
}
