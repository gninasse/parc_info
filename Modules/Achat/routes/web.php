<?php

use Illuminate\Support\Facades\Route;
use Modules\Achat\Http\Controllers\ApiController;
use Modules\Achat\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Routes du module Achat (SFD §8)
|--------------------------------------------------------------------------
| Préfixe /achat, noms achat.*, `auth` + permission SERVEUR sur TOUTES les
| routes (.data, cascades, PDF, exports, API compris) ; routes littérales
| avant /{id}.
*/

Route::middleware(['auth'])->prefix('achat')->name('achat.')->group(function () {
    // A-01 — Tableau de bord
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    | API inter-modules (API_Inter_Modules.md §4) — permission achat.api.view
    | portée par le contrôleur. Consommée par Stock (raccordement PRQ-05) et
    | par les écrans Achat. Routes littérales avant /{id}.
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::prefix('bons-commande')->name('bons-commande.')->group(function () {
            Route::get('/a-livrer', [ApiController::class, 'bonsCommandeALivrer'])->name('a-livrer');
            Route::get('/resoudre', [ApiController::class, 'resoudre'])->name('resoudre');
            Route::get('/{id}/lignes-a-livrer', [ApiController::class, 'lignesALivrer'])->name('lignes-a-livrer');
            Route::get('/{id}/receptions', [ApiController::class, 'receptions'])->name('receptions');
        });

        Route::get('/articles/{id}/historique-prix', [ApiController::class, 'historiquePrix'])->name('articles.historique-prix');
        Route::get('/fournisseurs/{id}/cumul-mois', [ApiController::class, 'cumulMois'])->name('fournisseurs.cumul-mois');
    });
});
