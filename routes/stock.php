<?php

use App\Http\Controllers\Stock\AchatController;
use App\Http\Controllers\Stock\ArticleController;
use App\Http\Controllers\Stock\BonCommandeController;
use App\Http\Controllers\Stock\CategorieArticleController;
use App\Http\Controllers\Stock\FournisseurController;
use App\Http\Controllers\Stock\PaiementFournisseurController;
use App\Http\Controllers\Stock\SortieStockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::middleware('permission:stock.tableau_bord.voir')->group(function () {
        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/export/excel', [ArticleController::class, 'exportExcel'])->name('articles.export.excel');
        Route::get('articles/export/pdf', [ArticleController::class, 'exportPdf'])->name('articles.export.pdf');
        Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

        Route::get('fournisseurs', [FournisseurController::class, 'index'])->name('fournisseurs.index');
        Route::get('fournisseurs/{fournisseur}', [FournisseurController::class, 'show'])->name('fournisseurs.show');

        Route::get('bons-commande', [BonCommandeController::class, 'index'])->name('bons-commande.index');
        Route::get('bons-commande/data', [BonCommandeController::class, 'data'])->name('bons-commande.data');
        Route::get('bons-commande/{bonCommande}', [BonCommandeController::class, 'show'])->name('bons-commande.show');
        Route::get('bons-commande/{bonCommande}/pdf', [BonCommandeController::class, 'pdf'])->name('bons-commande.pdf');

        Route::get('achats', [AchatController::class, 'index'])->name('achats.index');
        Route::get('achats/data', [AchatController::class, 'data'])->name('achats.data');
        Route::get('achats/export/excel', [AchatController::class, 'exportExcel'])->name('achats.export.excel');
        Route::get('achats/export/pdf', [AchatController::class, 'exportPdf'])->name('achats.export.pdf');
        Route::get('achats/{achat}/pdf', [AchatController::class, 'pdf'])->name('achats.pdf');
        Route::get('achats/{achat}', [AchatController::class, 'show'])->name('achats.show');

        Route::get('paiements', [PaiementFournisseurController::class, 'index'])->name('paiements.index');
        Route::get('paiements/data', [PaiementFournisseurController::class, 'data'])->name('paiements.data');
        Route::get('paiements/export/excel', [PaiementFournisseurController::class, 'exportExcel'])->name('paiements.export.excel');
        Route::get('paiements/export/pdf', [PaiementFournisseurController::class, 'exportPdf'])->name('paiements.export.pdf');

        Route::get('sorties', [SortieStockController::class, 'index'])->name('sorties.index');
        Route::get('sorties/data', [SortieStockController::class, 'data'])->name('sorties.data');
        Route::get('sorties/export/excel', [SortieStockController::class, 'exportExcel'])->name('sorties.export.excel');
        Route::get('sorties/export/pdf', [SortieStockController::class, 'exportPdf'])->name('sorties.export.pdf');
    });

    Route::middleware('permission:stock.article.gerer')->group(function () {
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');

        Route::post('categories-article', [CategorieArticleController::class, 'store'])->name('categories-article.store');
        Route::put('categories-article/{categorieArticle}', [CategorieArticleController::class, 'update'])->name('categories-article.update');
        Route::delete('categories-article/{categorieArticle}', [CategorieArticleController::class, 'destroy'])->name('categories-article.destroy');
    });

    Route::middleware('permission:stock.fournisseur.gerer')->group(function () {
        Route::post('fournisseurs', [FournisseurController::class, 'store'])->name('fournisseurs.store');
        Route::put('fournisseurs/{fournisseur}', [FournisseurController::class, 'update'])->name('fournisseurs.update');
        Route::delete('fournisseurs/{fournisseur}', [FournisseurController::class, 'destroy'])->name('fournisseurs.destroy');
    });

    Route::middleware('permission:stock.bon_commande.gerer')->group(function () {
        Route::post('bons-commande', [BonCommandeController::class, 'store'])->name('bons-commande.store');
        Route::post('bons-commande/{bonCommande}/annuler', [BonCommandeController::class, 'annuler'])->name('bons-commande.annuler');
        Route::delete('bons-commande/{bonCommande}', [BonCommandeController::class, 'destroy'])->name('bons-commande.destroy');
    });

    Route::middleware('permission:stock.achat.gerer')->group(function () {
        Route::post('achats', [AchatController::class, 'store'])->name('achats.store');
    });

    Route::middleware('permission:stock.paiement.gerer')->group(function () {
        Route::post('paiements', [PaiementFournisseurController::class, 'store'])->name('paiements.store');
    });

    // Autorisation dynamique (stock.sortie.interne ou stock.sortie.vente selon la nature)
    // geree par StoreSortieRequest::authorize(), pas par un middleware de permission unique.
    Route::middleware('permission:stock.sortie.interne|stock.sortie.vente')->group(function () {
        Route::post('sorties', [SortieStockController::class, 'store'])->name('sorties.store');
    });
});
