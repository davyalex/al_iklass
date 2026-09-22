<?php

use App\Http\Controllers\Flotte\GestionnaireController;
use App\Http\Controllers\Flotte\VehiculeController;
use App\Http\Controllers\Flotte\VersementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'reinitialiser.statut.journalier'])->prefix('flotte')->name('flotte.')->group(function () {
    Route::middleware('permission:flotte.vehicule.voir|flotte.vehicule.voir_affectes|flotte.vehicule.remise_circulation')->group(function () {
        Route::get('vehicules', [VehiculeController::class, 'index'])->name('vehicules.index');
        Route::get('vehicules/{vehicule}/historique', [VehiculeController::class, 'historique'])->name('vehicules.historique');
        Route::get('vehicules/{vehicule}', [VehiculeController::class, 'show'])->name('vehicules.show');
    });

    Route::middleware('permission:flotte.vehicule.voir')->group(function () {
        Route::get('gestionnaires', [GestionnaireController::class, 'index'])->name('gestionnaires.index');
        Route::get('gestionnaires/{gestionnaire}/compte', [GestionnaireController::class, 'compte'])->name('gestionnaires.compte');
    });

    // Un gestionnaire (flotte.versement.gerer) consulte et enregistre
    // uniquement ses propres versements ; flotte.vehicule.voir/gerer donne
    // la vue et la création pour tous (admin).
    Route::middleware('permission:flotte.vehicule.voir|flotte.versement.gerer')->group(function () {
        Route::get('versements', [VersementController::class, 'index'])->name('versements.index');
        Route::get('versements/kpis', [VersementController::class, 'kpis'])->name('versements.kpis');
        Route::get('versements/data', [VersementController::class, 'data'])->name('versements.data');
        Route::get('versements/export/excel', [VersementController::class, 'exportExcel'])->name('versements.export.excel');
        Route::get('versements/export/pdf', [VersementController::class, 'exportPdf'])->name('versements.export.pdf');
    });

    Route::middleware('permission:flotte.vehicule.gerer|flotte.versement.gerer')->group(function () {
        Route::post('versements', [VersementController::class, 'store'])->name('versements.store');
    });

    Route::middleware('permission:flotte.vehicule.gerer')->group(function () {
        Route::post('vehicules', [VehiculeController::class, 'store'])->name('vehicules.store');
        Route::put('vehicules/{vehicule}', [VehiculeController::class, 'update'])->name('vehicules.update');
        Route::delete('vehicules/{vehicule}', [VehiculeController::class, 'destroy'])->name('vehicules.destroy');
    });

    Route::middleware('permission:flotte.vehicule.gerer|flotte.vehicule.statut.gerer')->group(function () {
        Route::patch('vehicules/{vehicule}/statut', [VehiculeController::class, 'changerStatut'])->name('vehicules.statut');
    });

    Route::middleware('permission:flotte.vehicule.gerer|flotte.vehicule.remise_circulation')->group(function () {
        Route::post('vehicules/{vehicule}/remise-circulation', [VehiculeController::class, 'remiseEnCirculation'])->name('vehicules.remise-circulation');
    });
});
