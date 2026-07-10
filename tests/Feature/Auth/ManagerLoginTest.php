<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ManagerLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_login_page_renders_on_manager_host(): void
    {
        $this->get($this->managerUrl('/login'))
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_manager_can_authenticate_on_manager_host(): void
    {
        $manager = User::factory()->manager()->create([
            'email' => 'manager@water.test',
        ]);

        $this->get($this->managerUrl('/login'))
            ->assertOk();

        Volt::test('pages.auth.login')
            ->set('pwaApp', 'manager')
            ->set('form.email', $manager->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('manager.dashboard', absolute: false));

        $this->assertAuthenticatedAs($manager);
    }
}
