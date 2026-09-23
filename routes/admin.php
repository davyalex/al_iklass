<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CaisseController;
use App\Http\Controllers\Admin\ParametreController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UniteController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('permission:utilisateurs.voir')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware('permission:utilisateurs.gerer')->group(function () {
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('permission:roles.voir')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    });

    Route::middleware('permission:roles.gerer')->group(function () {
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.update-permissions');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('permission:unites.voir')->group(function () {
        Route::get('unites', [UniteController::class, 'index'])->name('unites.index');
    });

    Route::middleware('permission:unites.gerer')->group(function () {
        Route::post('unites', [UniteController::class, 'store'])->name('unites.store');
        Route::put('unites/{unite}', [UniteController::class, 'update'])->name('unites.update');
        Route::delete('unites/{unite}', [UniteController::class, 'destroy'])->name('unites.destroy');
    });

    Route::middleware('permission:audit.voir')->group(function () {
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('audit/data', [AuditLogController::class, 'data'])->name('audit.data');
        Route::get('audit/export/excel', [AuditLogController::class, 'exportExcel'])->name('audit.export.excel');
        Route::get('audit/export/pdf', [AuditLogController::class, 'exportPdf'])->name('audit.export.pdf');
    });

    Route::middleware('permission:caisse.voir')->group(function () {
        Route::get('caisses', [CaisseController::class, 'index'])->name('caisses.index');
        Route::get('caisses/data', [CaisseController::class, 'data'])->name('caisses.data');
        Route::get('caisses/export/excel', [CaisseController::class, 'exportExcel'])->name('caisses.export.excel');
        Route::get('caisses/export/pdf', [CaisseController::class, 'exportPdf'])->name('caisses.export.pdf');
    });

    Route::middleware('permission:parametres.voir')->group(function () {
        Route::get('parametres', [ParametreController::class, 'index'])->name('parametres.index');
    });

    Route::middleware('permission:parametres.gerer')->group(function () {
        Route::put('parametres/{parametre}', [ParametreController::class, 'update'])->name('parametres.update');
        Route::post('parametres/logo', [ParametreController::class, 'uploaderLogo'])->name('parametres.logo');
    });
});
