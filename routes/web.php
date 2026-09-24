<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/manifest.webmanifest', ManifestController::class)->name('manifest');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

require __DIR__.'/auth.php';
require __DIR__.'/stock.php';
require __DIR__.'/flotte.php';
require __DIR__.'/financements.php';
require __DIR__.'/admin.php';
