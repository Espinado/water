<?php

use App\Http\Controllers\PwaInstallController;
use App\Http\Controllers\PwaManifestController;
use App\Http\Controllers\PwaServiceWorkerController;
use App\Livewire\Dashboard;
use App\Livewire\Manager\ApartmentReadingsHistory;
use App\Livewire\Manager\ApartmentTable;
use App\Livewire\Manager\HouseholdPanel;
use App\Livewire\Manager\ManagerDashboard;
use App\Livewire\Manager\ManagerTeam;
use App\Livewire\Manager\MeterReadings;
use App\Livewire\Manager\ServiceProviders;
use App\Livewire\Manager\SupplierInvoices;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('manifest/{app}.webmanifest', [PwaManifestController::class, 'show'])
    ->whereIn('app', ['resident', 'manager'])
    ->name('pwa.manifest');
Route::get('sw.js', [PwaServiceWorkerController::class, 'show'])->name('pwa.service-worker');
Route::get('app/{app}', [PwaInstallController::class, 'show'])
    ->whereIn('app', ['resident', 'manager'])
    ->name('pwa.install');
Route::get('app/{app}/open', [PwaInstallController::class, 'open'])
    ->whereIn('app', ['resident', 'manager'])
    ->name('pwa.open');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    Route::middleware('manager')->group(function () {
        Route::get('manager', ManagerDashboard::class)->name('manager.dashboard');
        Route::get('manager/setup', HouseholdPanel::class)->name('manager.setup');
        Route::get('manager/apartments', ApartmentTable::class)->name('manager.apartments');
        Route::get('manager/readings', MeterReadings::class)->name('manager.readings');
        Route::get('manager/readings/apartment/{apartment}', ApartmentReadingsHistory::class)->name('manager.readings.apartment');
        Route::get('manager/suppliers', ServiceProviders::class)->name('manager.suppliers');
        Route::get('manager/invoices', SupplierInvoices::class)->name('manager.invoices');
        Route::get('manager/team', ManagerTeam::class)->name('manager.team');
    });
});

require __DIR__.'/auth.php';
