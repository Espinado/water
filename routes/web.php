<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PwaInstallController;
use App\Http\Controllers\PwaManifestController;
use App\Http\Controllers\PwaServiceWorkerController;
use App\Livewire\Dashboard;
use App\Livewire\ManagerProfilePage;
use App\Livewire\ProfilePage;
use App\Livewire\Manager\ApartmentReadingsHistory;
use App\Livewire\Manager\HouseholdPanel;
use App\Livewire\Manager\ManagerDashboard;
use App\Livewire\Manager\ManagerTeam;
use App\Livewire\Manager\MeterReadings;
use App\Livewire\Manager\ServiceProviders;
use App\Livewire\Manager\SupplierInvoices;
use App\Services\AppHost;
use Illuminate\Support\Facades\Route;

$appHost = app(AppHost::class);
$residentHost = $appHost->residentHost();
$managerHost = $appHost->managerHost();

Route::domain($managerHost)->group(function (): void {
    Route::redirect('/', '/dashboard');

    Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

    Route::get('manifest.webmanifest', [PwaManifestController::class, 'manager'])
        ->name('manager.pwa.manifest');
    Route::get('sw.js', [PwaServiceWorkerController::class, 'show'])->name('manager.pwa.service-worker');
    Route::get('app', [PwaInstallController::class, 'showManager'])->name('manager.pwa.install');
    Route::get('app/open', [PwaInstallController::class, 'openManager'])->name('manager.pwa.open');
    Route::get('app/continue', [PwaInstallController::class, 'continueManager'])
        ->middleware('auth')
        ->name('manager.pwa.continue');

    Route::middleware(['auth', 'verified', 'manager'])->group(function (): void {
        Route::get('dashboard', ManagerDashboard::class)->name('manager.dashboard');
        Route::get('setup', HouseholdPanel::class)->name('manager.setup');
        Route::get('apartments', function () {
            return redirect()->route('manager.setup', request()->query());
        })->name('manager.apartments');
        Route::get('readings', MeterReadings::class)->name('manager.readings');
        Route::get('readings/apartment/{apartment}', ApartmentReadingsHistory::class)->name('manager.readings.apartment');
        Route::get('suppliers', ServiceProviders::class)->name('manager.suppliers');
        Route::get('invoices', SupplierInvoices::class)->name('manager.invoices');
        Route::get('team', ManagerTeam::class)->name('manager.team');
        Route::get('profile', ManagerProfilePage::class)->name('manager.profile');
    });

    require __DIR__.'/auth-manager.php';
});

Route::domain($residentHost)->group(function () use ($appHost): void {
    Route::redirect('/', '/dashboard');

    Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

    Route::get('manifest/{app}.webmanifest', [PwaManifestController::class, 'show'])
        ->whereIn('app', ['resident'])
        ->name('pwa.manifest');
    Route::get('sw.js', [PwaServiceWorkerController::class, 'show'])->name('pwa.service-worker');
    Route::get('app/{app}', [PwaInstallController::class, 'show'])
        ->whereIn('app', ['resident'])
        ->name('pwa.install');
    Route::get('app/{app}/open', [PwaInstallController::class, 'open'])
        ->whereIn('app', ['resident'])
        ->name('pwa.open');
    Route::get('app/{app}/continue', [PwaInstallController::class, 'continue'])
        ->whereIn('app', ['resident'])
        ->middleware('auth')
        ->name('pwa.continue');

    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::get('dashboard', Dashboard::class)->name('dashboard');

        Route::get('profile', ProfilePage::class)->name('profile');
    });

    Route::redirect('manager', $appHost->absoluteUrl(AppHost::MANAGER, '/dashboard'));
    Route::get('manager/{path?}', function (?string $path = null) use ($appHost) {
        $suffix = $path !== null && $path !== '' ? '/'.ltrim($path, '/') : '/dashboard';

        return redirect($appHost->absoluteUrl(AppHost::MANAGER, $suffix), 301);
    })->where('path', '.*');

    Route::redirect('app/manager', $appHost->absoluteUrl(AppHost::MANAGER, '/app'));
    Route::redirect('app/manager/open', $appHost->absoluteUrl(AppHost::MANAGER, '/app/open'));
    Route::redirect('manifest/manager.webmanifest', $appHost->absoluteUrl(AppHost::MANAGER, '/manifest.webmanifest'));

    require __DIR__.'/auth-resident.php';
});
