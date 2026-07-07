<?php

use App\Livewire\Dashboard;
use App\Livewire\Manager\ApartmentReadingsHistory;
use App\Livewire\Manager\ApartmentTable;
use App\Livewire\Manager\HouseholdPanel;
use App\Livewire\Manager\MeterReadings;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    Route::middleware('manager')->group(function () {
        Route::get('manager', HouseholdPanel::class)->name('manager.panel');
        Route::get('manager/apartments', ApartmentTable::class)->name('manager.apartments');
        Route::get('manager/readings', MeterReadings::class)->name('manager.readings');
        Route::get('manager/readings/apartment/{apartment}', ApartmentReadingsHistory::class)->name('manager.readings.apartment');
    });
});

require __DIR__.'/auth.php';
