<?php

namespace App\Providers;

use App\Contracts\MeterReadingRecognizer;
use App\Contracts\VisionDocumentTextDetector;
use App\Models\Apartment;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\AppHost;
use App\Services\GeminiMeterReadingRecognizer;
use App\Services\GoogleCloudVisionDocumentTextDetector;
use App\Services\MeterSubmissionWindow;
use App\Services\PwaContext;
use App\Session\AppSessionManager;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('session', function ($manager, $app) {
            return new AppSessionManager($app);
        });

        $this->app->singleton(VisionDocumentTextDetector::class, GoogleCloudVisionDocumentTextDetector::class);
        $this->app->singleton(MeterReadingRecognizer::class, GeminiMeterReadingRecognizer::class);
        $this->app->singleton(AppHost::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (! $this->app->runningInConsole() && $this->app->bound('request')) {
            $request = $this->app->make('request');

            if ($request instanceof \Illuminate\Http\Request) {
                app(AppHost::class)->configureForRequest($request);
            }
        }

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $pwa = app(PwaContext::class);
            $appHost = app(AppHost::class);

            $app = $notifiable instanceof User
                ? $pwa->appKeyForUser($notifiable)
                : AppHost::RESIDENT;

            $resetRoute = $app === AppHost::MANAGER ? 'manager.password.reset' : 'password.reset';
            $path = route($resetRoute, [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
                'app' => $app,
            ], false);

            $url = $appHost->absoluteUrl($app, $path);

            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject(__('Установка пароля'))
                ->line(__('Вы получили это письмо, потому что для вашей учётной записи запрошена установка или смена пароля.'))
                ->action(__('Установить пароль'), $url)
                ->line(__('Ссылка действительна в течение :minutes минут.', ['minutes' => $minutes]))
                ->line(__('Если вы не отправляли этот запрос, просто проигнорируйте письмо — пароль не изменится.'));
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            $user = $event->user;

            if (! $user instanceof User || $user->email_verified_at !== null) {
                return;
            }

            User::query()
                ->whereKey($user->getKey())
                ->whereNull('email_verified_at')
                ->update(['email_verified_at' => now()]);
        });

        Gate::define('record-meter-reading', function (User $user, Apartment $apartment, int $year, int $month) {
            if ($user->isManager()) {
                return true;
            }
            if (! $user->isResident()) {
                return false;
            }
            if ((int) $user->apartment_id !== (int) $apartment->id) {
                return false;
            }

            if (config('water.meter_reading_gate_bypass') || config('water.submission_window_bypass')) {
                return true;
            }

            return app(MeterSubmissionWindow::class)->isOpenForResident($year, $month);
        });

        Gate::define('update-meter-reading', function (User $user, MeterReading $reading) {
            if ($user->isManager()) {
                return true;
            }
            if (! $user->isResident()) {
                return false;
            }
            if ((int) $user->apartment_id !== (int) $reading->apartment_id) {
                return false;
            }

            if (config('water.meter_reading_gate_bypass') || config('water.submission_window_bypass')) {
                return true;
            }

            return app(MeterSubmissionWindow::class)->isOpenForResident($reading->year, $reading->month);
        });
    }
}
