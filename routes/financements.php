<?php

use App\Http\Controllers\Financement\FinancementController;
use App\Http\Controllers\Financement\PreteurController;
use App\Http\Controllers\Financement\RemboursementController;
use App\Http\Controllers\Financement\TypePreteurController;
use Illuminate\Support\Facades\Route;

// Prêts & financements : emprunts contractés par l'entreprise (banque ou
// personne) et leurs remboursements. Structure calquée sur le module
// Fournisseur (préteur = fournisseur, emprunt = achat, remboursement =
// paiement), y compris la page "compte" par prêteur (KPI + historique).
Route::middleware(['auth'])->prefix('financements')->name('financements.')->group(function () {
    Route::middleware('permission:financements.voir')->group(function () {
        Route::get('preteurs', [PreteurController::class, 'index'])->name('preteurs.index');
        Route::get('preteurs/{preteur}/compte', [PreteurController::class, 'compte'])->name('preteurs.compte');
        Route::get('preteurs/{preteur}/compte/pdf', [PreteurController::class, 'comptePdf'])->name('preteurs.compte.pdf');
        Route::get('preteurs/{preteur}', [PreteurController::class, 'show'])->name('preteurs.show');

        Route::get('emprunts', [FinancementController::class, 'index'])->name('financements.index');
        Route::get('emprunts/data', [FinancementController::class, 'data'])->name('financements.data');
        Route::get('emprunts/kpis', [FinancementController::class, 'kpis'])->name('financements.kpis');
        Route::get('emprunts/export/excel', [FinancementController::class, 'exportExcel'])->name('financements.export.excel');
        Route::get('emprunts/export/pdf', [FinancementController::class, 'exportPdf'])->name('financements.export.pdf');
        Route::get('emprunts/{financement}', [FinancementController::class, 'show'])->name('financements.show');

        Route::get('remboursements', [RemboursementController::class, 'index'])->name('remboursements.index');
        Route::get('remboursements/data', [RemboursementController::class, 'data'])->name('remboursements.data');
        Route::get('remboursements/export/excel', [RemboursementController::class, 'exportExcel'])->name('remboursements.export.excel');
        Route::get('remboursements/export/pdf', [RemboursementController::class, 'exportPdf'])->name('remboursements.export.pdf');
    });

    Route::middleware('permission:financements.preteur.gerer')->group(function () {
        Route::post('preteurs', [PreteurController::class, 'store'])->name('preteurs.store');
        Route::put('preteurs/{preteur}', [PreteurController::class, 'update'])->name('preteurs.update');
        Route::delete('preteurs/{preteur}', [PreteurController::class, 'destroy'])->name('preteurs.destroy');
    });

    Route::middleware('permission:financements.gerer')->group(function () {
        Route::post('emprunts', [FinancementController::class, 'store'])->name('financements.store');
    });

    Route::middleware('permission:financements.rembourser')->group(function () {
        Route::post('remboursements', [RemboursementController::class, 'store'])->name('remboursements.store');
    });

    Route::middleware('permission:financements.type.gerer')->group(function () {
        Route::post('types', [TypePreteurController::class, 'store'])->name('types.store');
        Route::put('types/{type}', [TypePreteurController::class, 'update'])->name('types.update');
    });
});
