<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventAttendanceController;
use App\Http\Controllers\EventInterestController;
use App\Http\Controllers\EventVisualController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/events')->name('home');

Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/data', [EventController::class, 'data'])->name('events.data');
Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

Route::inertia('events-visual-1', 'Events/VisualOne')->name('events.visual1');
Route::get('events-visual-1/data', [EventVisualController::class, 'gridData'])->name('events.visual1.data');
Route::get('events-visual-1/locations', [EventVisualController::class, 'locationSuggestions'])->name('events.visual1.locations');
Route::get('events-visual-1/address', [EventVisualController::class, 'resolveAddress'])->name('events.visual1.address');

Route::middleware('auth')->group(function () {
    Route::post('events-visual-1/attendances/{event}', [EventAttendanceController::class, 'store'])
        ->name('events.visual1.attendances.store');
    Route::delete('events-visual-1/attendances/{event}', [EventAttendanceController::class, 'destroy'])
        ->name('events.visual1.attendances.destroy');
    Route::post('events-visual-1/interests/{event}', [EventInterestController::class, 'store'])
        ->name('events.visual1.interests.store');
    Route::delete('events-visual-1/interests/{event}', [EventInterestController::class, 'destroy'])
        ->name('events.visual1.interests.destroy');
});
Route::inertia('events-visual-2', 'Events/VisualTwo')->name('events.visual2');
Route::get('events-visual-2/data', [EventVisualController::class, 'calendarData'])->name('events.visual2.data');
Route::get('events-visual-2/locations', [EventVisualController::class, 'locationSuggestions'])->name('events.visual2.locations');
Route::get('events-visual-2/address', [EventVisualController::class, 'resolveAddress'])->name('events.visual2.address');

Route::middleware('auth')->group(function () {
    Route::post('events-visual-2/attendances/{event}', [EventAttendanceController::class, 'store'])
        ->name('events.visual2.attendances.store');
    Route::delete('events-visual-2/attendances/{event}', [EventAttendanceController::class, 'destroy'])
        ->name('events.visual2.attendances.destroy');
    Route::post('events-visual-2/interests/{event}', [EventInterestController::class, 'store'])
        ->name('events.visual2.interests.store');
    Route::delete('events-visual-2/interests/{event}', [EventInterestController::class, 'destroy'])
        ->name('events.visual2.interests.destroy');
});

Route::inertia('dashboard', 'Dashboard')->name('dashboard');

require __DIR__.'/settings.php';
