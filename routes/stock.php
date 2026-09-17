<?php

use App\Http\Controllers\Stock\ArticleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::middleware('permission:stock.dashboard.view')->group(function () {
        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    });

    Route::middleware('permission:stock.article.manage')->group(function () {
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    });
});
