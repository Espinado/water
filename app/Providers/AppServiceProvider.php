<?php

namespace App\Providers;

use App\Contracts\MeterReadingRecognizer;
use App\Contracts\VisionDocumentTextDetector;
use App\Models\Apartment;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\GeminiMeterReadingRecognizer;
use App\Services\GoogleCloudVisionDocumentTextDetector;
use App\Services\MeterSubmissionWindow;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VisionDocumentTextDetector::class, GoogleCloudVisionDocumentTextDetector::class);
        $this->app->singleton(MeterReadingRecognizer::class, GeminiMeterReadingRecognizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // MySQL/MariaDB (utf8mb4): индекс по varchar(255) > 1000 байт — см. SQLSTATE 1071.
        Schema::defaultStringLength(191);

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject(__('Установка пароля'))
                ->line(__('Вы получили это письмо, потому что для вашей учётной записи запрошена установка или смена пароля.'))
                ->line(__('Так бывает, когда управляющий дома добавил вас в систему, либо когда вы сами запросили восстановление доступа.'))
                ->action(__('Установить пароль'), $url)
                ->line(__('Ссылка действительна в течение :minutes минут.', ['minutes' => $minutes]))
                ->line(__('Если вы не отправляли этот запрос, просто проигнорируйте письмо — пароль не изменится.'));
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
