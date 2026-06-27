<?php

use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [MonitoringController::class, 'dashboard'])->name('dashboard');
    Route::get('/riwayat', [MonitoringController::class, 'history'])->name('history');
    Route::delete('/riwayat', [MonitoringController::class, 'destroyHistory'])->name('history.destroy');
    Route::get('/api/latest-reading', [MonitoringController::class, 'latest'])->name('latest.reading');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
