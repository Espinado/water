<?php

namespace App\Providers;

use App\Contracts\VisionDocumentTextDetector;
use App\Models\Apartment;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\GoogleCloudVisionDocumentTextDetector;
use App\Services\MeterSubmissionWindow;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // MySQL/MariaDB (utf8mb4): индекс по varchar(255) > 1000 байт — см. SQLSTATE 1071.
        Schema::defaultStringLength(191);

        Gate::define('record-meter-reading', function (User $user, Apartment $apartment, int $year, int $month) {
            if ($user->isManager()) {
                return true;
            }
            if (! $user->isResident() || $user->apartment_id !== $apartment->id) {
                return false;
            }

            return app(MeterSubmissionWindow::class)->isOpenForResident($year, $month);
        });

        Gate::define('update-meter-reading', function (User $user, MeterReading $reading) {
            if ($user->isManager()) {
                return true;
            }
            if (! $user->isResident() || $user->apartment_id !== $reading->apartment_id) {
                return false;
            }

            return app(MeterSubmissionWindow::class)->isOpenForResident($reading->year, $reading->month);
        });
    }
}
