<?php

use App\Http\Controllers\Stock\AchatController;
use App\Http\Controllers\Stock\ArticleController;
use App\Http\Controllers\Stock\BonCommandeController;
use App\Http\Controllers\Stock\CategorieArticleController;
use App\Http\Controllers\Stock\EtatStockController;
use App\Http\Controllers\Stock\FournisseurController;
use App\Http\Controllers\Stock\InventaireController;
use App\Http\Controllers\Stock\MouvementStockController;
use App\Http\Controllers\Stock\PaiementFournisseurController;
use App\Http\Controllers\Stock\SortieStockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::middleware('permission:stock.tableau_bord.voir')->group(function () {
        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/export/excel', [ArticleController::class, 'exportExcel'])->name('articles.export.excel');
        Route::get('articles/export/pdf', [ArticleController::class, 'exportPdf'])->name('articles.export.pdf');
        Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

        Route::get('etat-stock', [EtatStockController::class, 'index'])->name('etat-stock.index');
        Route::get('etat-stock/data', [EtatStockController::class, 'data'])->name('etat-stock.data');
        Route::get('etat-stock/kpis', [EtatStockController::class, 'kpis'])->name('etat-stock.kpis');
        Route::get('etat-stock/export/excel', [EtatStockController::class, 'exportExcel'])->name('etat-stock.export.excel');
        Route::get('etat-stock/export/pdf', [EtatStockController::class, 'exportPdf'])->name('etat-stock.export.pdf');
        Route::get('etat-stock/{article}', [EtatStockController::class, 'detail'])->name('etat-stock.detail');

        Route::get('mouvements', [MouvementStockController::class, 'index'])->name('mouvements.index');
        Route::get('mouvements/data', [MouvementStockController::class, 'data'])->name('mouvements.data');
        Route::get('mouvements/export/excel', [MouvementStockController::class, 'exportExcel'])->name('mouvements.export.excel');
        Route::get('mouvements/export/pdf', [MouvementStockController::class, 'exportPdf'])->name('mouvements.export.pdf');

        Route::get('fournisseurs', [FournisseurController::class, 'index'])->name('fournisseurs.index');
        Route::get('fournisseurs/{fournisseur}/compte', [FournisseurController::class, 'compte'])->name('fournisseurs.compte');
        Route::get('fournisseurs/{fournisseur}/compte/pdf', [FournisseurController::class, 'comptePdf'])->name('fournisseurs.compte.pdf');
        Route::get('fournisseurs/{fournisseur}', [FournisseurController::class, 'show'])->name('fournisseurs.show');

        Route::get('bons-commande', [BonCommandeController::class, 'index'])->name('bons-commande.index');
        Route::get('bons-commande/data', [BonCommandeController::class, 'data'])->name('bons-commande.data');
        Route::get('bons-commande/export/excel', [BonCommandeController::class, 'exportExcel'])->name('bons-commande.export.excel');
        Route::get('bons-commande/export/pdf', [BonCommandeController::class, 'exportPdf'])->name('bons-commande.export.pdf');
        Route::get('bons-commande/{bonCommande}/pdf', [BonCommandeController::class, 'pdf'])->name('bons-commande.pdf');
        Route::get('bons-commande/{bonCommande}/excel', [BonCommandeController::class, 'exportExcelSingle'])->name('bons-commande.export.excel.single');
        Route::get('bons-commande/{bonCommande}', [BonCommandeController::class, 'show'])->name('bons-commande.show');

        Route::get('achats', [AchatController::class, 'index'])->name('achats.index');
        Route::get('achats/data', [AchatController::class, 'data'])->name('achats.data');
        Route::get('achats/kpis', [AchatController::class, 'kpis'])->name('achats.kpis');
        Route::get('achats/export/excel', [AchatController::class, 'exportExcel'])->name('achats.export.excel');
        Route::get('achats/export/pdf', [AchatController::class, 'exportPdf'])->name('achats.export.pdf');
        Route::get('achats/{achat}/pdf', [AchatController::class, 'pdf'])->name('achats.pdf');
        Route::get('achats/{achat}/excel', [AchatController::class, 'exportExcelSingle'])->name('achats.export.excel.single');
        Route::get('achats/{achat}', [AchatController::class, 'show'])->name('achats.show');

        Route::get('paiements', [PaiementFournisseurController::class, 'index'])->name('paiements.index');
        Route::get('paiements/data', [PaiementFournisseurController::class, 'data'])->name('paiements.data');
        Route::get('paiements/export/excel', [PaiementFournisseurController::class, 'exportExcel'])->name('paiements.export.excel');
        Route::get('paiements/export/pdf', [PaiementFournisseurController::class, 'exportPdf'])->name('paiements.export.pdf');

        Route::get('sorties', [SortieStockController::class, 'index'])->name('sorties.index');
        Route::get('sorties/data', [SortieStockController::class, 'data'])->name('sorties.data');
        Route::get('sorties/kpis', [SortieStockController::class, 'kpis'])->name('sorties.kpis');
        Route::get('sorties/export/excel', [SortieStockController::class, 'exportExcel'])->name('sorties.export.excel');
        Route::get('sorties/export/pdf', [SortieStockController::class, 'exportPdf'])->name('sorties.export.pdf');
        Route::get('sorties/{sortie}', [SortieStockController::class, 'show'])->name('sorties.show');
        Route::get('sorties/{sortie}/pdf', [SortieStockController::class, 'pdf'])->name('sorties.pdf');
        Route::get('sorties/{sortie}/excel', [SortieStockController::class, 'exportExcelSingle'])->name('sorties.export.excel.single');

        Route::get('inventaires', [InventaireController::class, 'index'])->name('inventaires.index');
        Route::get('inventaires/data', [InventaireController::class, 'data'])->name('inventaires.data');
        Route::get('inventaires/export/excel', [InventaireController::class, 'exportExcel'])->name('inventaires.export.excel');
        Route::get('inventaires/export/pdf', [InventaireController::class, 'exportPdf'])->name('inventaires.export.pdf');
        Route::get('inventaires/{inventaire}', [InventaireController::class, 'show'])->name('inventaires.show');
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

    Route::middleware('permission:stock.inventaire.gerer')->group(function () {
        Route::post('inventaires', [InventaireController::class, 'store'])->name('inventaires.store');
        Route::put('inventaires/lignes/{ligne}', [InventaireController::class, 'updateLigne'])->name('inventaires.lignes.update');
        Route::post('inventaires/{inventaire}/valider', [InventaireController::class, 'valider'])->name('inventaires.valider');
        Route::delete('inventaires/{inventaire}', [InventaireController::class, 'destroy'])->name('inventaires.destroy');
    });
});
