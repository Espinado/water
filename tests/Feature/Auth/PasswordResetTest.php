<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get($this->residentUrl('/forgot-password'))
            ->assertSeeVolt('pages.auth.forgot-password')
            ->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_resident_invitation_link_points_to_resident_subdomain(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            $this->assertStringStartsWith($this->residentUrl('/reset-password/'), $url);
            $this->assertStringContainsString('app=resident', $url);

            return true;
        });
    }

    public function test_manager_invitation_link_points_to_manager_subdomain(): void
    {
        Notification::fake();

        $manager = User::factory()->manager()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $manager->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($manager, ResetPassword::class, function (ResetPassword $notification) use ($manager) {
            $url = $notification->toMail($manager)->actionUrl;

            $this->assertStringStartsWith($this->managerUrl('/reset-password/'), $url);
            $this->assertStringContainsString('app=manager', $url);

            return true;
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get($this->residentUrl('/reset-password/'.$notification->token.'?email='.urlencode($user->email).'&app=resident'))
                ->assertSeeVolt('pages.auth.reset-password')
                ->assertOk();

            return true;
        });
    }

    public function test_password_reset_marks_unverified_user_as_verified(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            Volt::test('pages.auth.reset-password', ['token' => $notification->token])
                ->set('email', $user->email)
                ->set('password', 'password')
                ->set('password_confirmation', 'password')
                ->call('resetPassword')
                ->assertHasNoErrors();

            $this->assertNotNull($user->fresh()->email_verified_at);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $component = Volt::test('pages.auth.reset-password', ['token' => $notification->token])
                ->set('email', $user->email)
                ->set('password', 'password')
                ->set('password_confirmation', 'password');

            $component->call('resetPassword');

            $component
                ->assertRedirect(route('dashboard', absolute: false))
                ->assertHasNoErrors();

            $this->assertAuthenticatedAs($user);

            return true;
        });
    }

    public function test_manager_invitation_reset_logs_in_and_redirects_to_manager_dashboard(): void
    {
        Notification::fake();

        $manager = User::factory()->manager()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $manager->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($manager, ResetPassword::class, function ($notification) use ($manager) {
            $component = Volt::test('pages.auth.reset-password', ['token' => $notification->token])
                ->set('email', $manager->email)
                ->set('password', 'password')
                ->set('password_confirmation', 'password');

            $component->call('resetPassword');

            $component
                ->assertRedirect(route('manager.dashboard', absolute: false))
                ->assertHasNoErrors();

            $this->assertAuthenticatedAs($manager);

            return true;
        });
    }
}
