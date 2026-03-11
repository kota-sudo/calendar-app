<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarShareController;
use App\Http\Controllers\CalendarController;

Route::get('/', function () {
    return redirect('/calendar');
});

Route::middleware('auth')->group(function () {
    Route::get('/calendar', [EventController::class, 'calendar'])->name('calendar');
    Route::get('/calendar/day', [EventController::class, 'day'])->name('calendar.day');

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');

    Route::get('/events/{id}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::post('/events/{id}/update', [EventController::class, 'update'])->name('events.update');
    Route::post('/events/{id}/delete', [EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{id}/delete-future', [EventController::class, 'deleteFuture'])->name('events.deleteFuture');
    Route::post('/events/{id}/move', [EventController::class, 'move'])->name('events.move');

    Route::get('/notifications/check', [EventController::class, 'checkNotifications'])->name('notifications.check');
    Route::get('/notifications/test', [EventController::class, 'testNotification'])->name('notifications.test');
    Route::get('/calendars/{calendar}/edit', [CalendarController::class, 'edit'])->name('calendars.edit');
Route::post('/calendars/{calendar}/update', [CalendarController::class, 'update'])->name('calendars.update');

    Route::get('/dashboard', function () {
    return redirect('/calendar');
})->middleware('verified')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/calendars/{calendar}/share', [CalendarShareController::class, 'edit'])->name('calendars.share.edit');
Route::post('/calendars/{calendar}/share', [CalendarShareController::class, 'store'])->name('calendars.share.store');
});

require __DIR__.'/auth.php';