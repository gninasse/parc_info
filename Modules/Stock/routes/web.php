<?php

use Illuminate\Support\Facades\Route;
use Modules\Stock\Http\Controllers\DashboardController;
use Modules\Stock\Http\Controllers\EntreeController;
use Modules\Stock\Http\Controllers\MagasinController;
use Modules\Stock\Http\Controllers\SortieController;
use Modules\Stock\Http\Controllers\StockArticleController;
use Modules\Stock\Http\Controllers\TransfertController;

/*
|--------------------------------------------------------------------------
| Routes web du module Stock
|--------------------------------------------------------------------------
| Préfixe « stock/ » et noms « stock.* » appliqués par RouteServiceProvider.
| PATTERNS §14 — les routes « data » sont déclarées avant les resources.
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Tableau de bord ────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');

    // ── F1 — Magasins ──────────────────────────────────────────────────────
    Route::get('magasins/data', [MagasinController::class, 'getData'])->name('magasins.data');
    Route::get('magasins/employes', [MagasinController::class, 'employes'])->name('magasins.employes');
    Route::post('magasins/{magasin}/activer', [MagasinController::class, 'activer'])->name('magasins.activer');
    Route::post('magasins/{magasin}/desactiver', [MagasinController::class, 'desactiver'])->name('magasins.desactiver');
    Route::post('magasins/{magasin}/responsables', [MagasinController::class, 'ajouterResponsable'])->name('magasins.responsables.store');
    Route::delete('magasins/{magasin}/responsables/{responsable}', [MagasinController::class, 'retirerResponsable'])->name('magasins.responsables.destroy');
    Route::resource('magasins', MagasinController::class)->except(['create', 'edit']);

    // ── F2 — Stock par article ─────────────────────────────────────────────
    Route::get('articles/data', [StockArticleController::class, 'getData'])->name('articles.data');
    Route::get('articles/disponibles', [StockArticleController::class, 'articlesDisponibles'])->name('articles.disponibles');
    Route::post('articles/initialiser', [StockArticleController::class, 'initialiser'])->name('articles.initialiser');
    Route::get('articles/{article}/lots', [StockArticleController::class, 'lots'])->name('articles.lots');
    Route::get('articles/{article}', [StockArticleController::class, 'show'])->name('articles.show');
    Route::get('articles', [StockArticleController::class, 'index'])->name('articles.index');

    // ── F3 — Entrées ───────────────────────────────────────────────────────
    Route::get('entrees/data', [EntreeController::class, 'getData'])->name('entrees.data');
    Route::get('entrees/articles', [EntreeController::class, 'articles'])->name('entrees.articles');
    Route::resource('entrees', EntreeController::class)
        ->only(['index', 'show', 'store', 'destroy'])
        ->parameters(['entrees' => 'mouvement']);

    // ── F4 — Sorties ───────────────────────────────────────────────────────
    Route::get('sorties/data', [SortieController::class, 'getData'])->name('sorties.data');
    Route::get('sorties/articles', [SortieController::class, 'articles'])->name('sorties.articles');
    Route::get('sorties/cibles', [SortieController::class, 'cibles'])->name('sorties.cibles');
    Route::post('sorties/regularisation', [SortieController::class, 'regularisation'])->name('sorties.regularisation');
    Route::resource('sorties', SortieController::class)
        ->only(['index', 'show', 'store'])
        ->parameters(['sorties' => 'mouvement']);

    // ── F5 — Transferts ────────────────────────────────────────────────────
    Route::get('transferts/data', [TransfertController::class, 'getData'])->name('transferts.data');
    Route::get('transferts/articles', [TransfertController::class, 'articles'])->name('transferts.articles');
    Route::post('transferts/{transfert}/valider', [TransfertController::class, 'valider'])->name('transferts.valider');
    Route::post('transferts/{transfert}/rejeter', [TransfertController::class, 'rejeter'])->name('transferts.rejeter');
    Route::post('transferts/{transfert}/annuler', [TransfertController::class, 'annuler'])->name('transferts.annuler');
    Route::resource('transferts', TransfertController::class)->only(['index', 'show', 'store']);
});
