<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalogue\Http\Controllers\ApiController;
use Modules\Catalogue\Http\Controllers\ArticleController;
use Modules\Catalogue\Http\Controllers\CategorieController;
use Modules\Catalogue\Http\Controllers\FournisseurController;

Route::middleware(['auth'])->prefix('catalogue')->name('catalogue.')->group(function () {
    // API inter-modules (SFD §2.5) — routes littérales avant tout /{id}
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/articles', [ApiController::class, 'articles'])->name('articles');
        Route::get('/articles/{id}', [ApiController::class, 'article'])->name('articles.show');
        // §2.4 — historique du prix indicatif : alimente la décomposition du
        // prix d'Achat (PO-01) et son signal « référence modifiée » (A14).
        Route::get('/articles/{id}/journal-prix', [ApiController::class, 'journalPrix'])->name('articles.journal-prix');
        Route::get('/categories', [ApiController::class, 'categories'])->name('categories');
        Route::get('/fournisseurs', [ApiController::class, 'fournisseurs'])->name('fournisseurs');
    });

    // Articles — routes littérales (data + cascades de la modale) avant /{id}
    Route::prefix('articles')->name('articles.')->group(function () {
        Route::get('/', [ArticleController::class, 'index'])->name('index');
        Route::get('/data', [ArticleController::class, 'getData'])->name('data');
        Route::get('/categories-cascade', [ArticleController::class, 'getCategoriesCascade'])->name('categories-cascade');
        Route::get('/marques', [ArticleController::class, 'getMarques'])->name('marques');
        Route::get('/categories-equipements', [ArticleController::class, 'getCategoriesEquipements'])->name('categories-equipements');
        Route::get('/logiciels', [ArticleController::class, 'getLogiciels'])->name('logiciels');
        Route::post('/', [ArticleController::class, 'store'])->name('store');
        Route::get('/{id}', [ArticleController::class, 'show'])->name('show');
        Route::put('/{id}', [ArticleController::class, 'update'])->name('update');
        Route::delete('/{id}', [ArticleController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [ArticleController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Catégories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategorieController::class, 'index'])->name('index');
        Route::get('/data', [CategorieController::class, 'getData'])->name('data');
        Route::get('/parents', [CategorieController::class, 'getParents'])->name('parents');
        Route::post('/', [CategorieController::class, 'store'])->name('store');
        Route::get('/{id}', [CategorieController::class, 'show'])->name('show');
        Route::put('/{id}', [CategorieController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategorieController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [CategorieController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Fournisseurs
    Route::prefix('fournisseurs')->name('fournisseurs.')->group(function () {
        Route::get('/', [FournisseurController::class, 'index'])->name('index');
        Route::get('/data', [FournisseurController::class, 'getData'])->name('data');
        Route::post('/', [FournisseurController::class, 'store'])->name('store');
        Route::get('/{id}', [FournisseurController::class, 'show'])->name('show');
        Route::put('/{id}', [FournisseurController::class, 'update'])->name('update');
        Route::delete('/{id}', [FournisseurController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [FournisseurController::class, 'toggleStatus'])->name('toggle-status');
    });
});
