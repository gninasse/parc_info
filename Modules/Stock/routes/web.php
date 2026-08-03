<?php

use Illuminate\Support\Facades\Route;
use Modules\Stock\Http\Controllers\DashboardController;
use Modules\Stock\Http\Controllers\MagasinController;
use Modules\Stock\Http\Controllers\NiveauController;

Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Magasins (référentiel — UX §9) : routes littérales avant /{id}
    Route::prefix('magasins')->name('magasins.')->group(function () {
        Route::get('/', [MagasinController::class, 'index'])->name('index');
        Route::get('/data', [MagasinController::class, 'getData'])->name('data');
        Route::get('/sites-disponibles', [MagasinController::class, 'getSitesDisponibles'])->name('sites-disponibles');
        Route::get('/locaux', [MagasinController::class, 'getLocaux'])->name('locaux');
        Route::get('/responsables', [MagasinController::class, 'getResponsables'])->name('responsables');
        Route::post('/', [MagasinController::class, 'store'])->name('store');
        Route::get('/{id}', [MagasinController::class, 'show'])->name('show');
        Route::get('/{id}/equipements-data', [MagasinController::class, 'getEquipementsData'])->name('equipements-data');
        Route::get('/{id}/mouvements-data', [MagasinController::class, 'getMouvementsData'])->name('mouvements-data');
        Route::put('/{id}', [MagasinController::class, 'update'])->name('update');
        Route::delete('/{id}', [MagasinController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [MagasinController::class, 'toggleStatus'])->name('toggle-status');
    });

    // État des stocks (UX §2)
    Route::prefix('niveaux')->name('niveaux.')->group(function () {
        Route::get('/', [NiveauController::class, 'index'])->name('index');
        Route::get('/data', [NiveauController::class, 'getData'])->name('data');
        Route::get('/export', [NiveauController::class, 'export'])->name('export');
        Route::post('/seuil-article', [NiveauController::class, 'seuilArticle'])->name('seuil-article');
        Route::patch('/{id}/seuil', [NiveauController::class, 'seuil'])->name('seuil');
    });
});
