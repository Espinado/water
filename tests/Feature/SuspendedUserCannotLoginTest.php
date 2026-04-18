<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SuspendedUserCannotLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'access_suspended_at' => now(),
        ]);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component->assertHasErrors('form.email');
        $this->assertGuest();
    }
}
