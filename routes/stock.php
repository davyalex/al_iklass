<?php

use App\Http\Controllers\Stock\AchatController;
use App\Http\Controllers\Stock\ArticleController;
use App\Http\Controllers\Stock\FournisseurController;
use App\Http\Controllers\Stock\PaiementFournisseurController;
use App\Http\Controllers\Stock\SortieStockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::middleware('permission:stock.dashboard.view')->group(function () {
        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

        Route::get('fournisseurs', [FournisseurController::class, 'index'])->name('fournisseurs.index');
        Route::get('fournisseurs/{fournisseur}', [FournisseurController::class, 'show'])->name('fournisseurs.show');

        Route::get('achats', [AchatController::class, 'index'])->name('achats.index');
        Route::get('achats/data', [AchatController::class, 'data'])->name('achats.data');

        Route::get('paiements', [PaiementFournisseurController::class, 'index'])->name('paiements.index');
        Route::get('paiements/data', [PaiementFournisseurController::class, 'data'])->name('paiements.data');

        Route::get('sorties', [SortieStockController::class, 'index'])->name('sorties.index');
        Route::get('sorties/data', [SortieStockController::class, 'data'])->name('sorties.data');
    });

    Route::middleware('permission:stock.article.manage')->group(function () {
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    });

    Route::middleware('permission:stock.fournisseur.manage')->group(function () {
        Route::post('fournisseurs', [FournisseurController::class, 'store'])->name('fournisseurs.store');
        Route::put('fournisseurs/{fournisseur}', [FournisseurController::class, 'update'])->name('fournisseurs.update');
        Route::delete('fournisseurs/{fournisseur}', [FournisseurController::class, 'destroy'])->name('fournisseurs.destroy');
    });

    Route::middleware('permission:stock.achat.manage')->group(function () {
        Route::post('achats', [AchatController::class, 'store'])->name('achats.store');
    });

    Route::middleware('permission:stock.paiement.manage')->group(function () {
        Route::post('paiements', [PaiementFournisseurController::class, 'store'])->name('paiements.store');
    });

    // Autorisation dynamique (stock.sortie.interne ou stock.sortie.vente selon la nature)
    // geree par StoreSortieRequest::authorize(), pas par un middleware de permission unique.
    Route::middleware('permission:stock.sortie.interne|stock.sortie.vente')->group(function () {
        Route::post('sorties', [SortieStockController::class, 'store'])->name('sorties.store');
    });
});
