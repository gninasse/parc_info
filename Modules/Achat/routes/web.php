<?php

use Illuminate\Support\Facades\Route;
use Modules\Achat\Http\Controllers\ApiController;
use Modules\Achat\Http\Controllers\BonCommandeController;
use Modules\Achat\Http\Controllers\BonCommandePdfController;
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
    | A-02 (liste) et A-03 (création / édition d'un brouillon). Les routes
    | LITTÉRALES sont déclarées avant tout segment /{id} — sinon /create
    | serait capté comme un identifiant. Chaque route porte sa permission
    | serveur, y compris .data et la référence de prix (SFD §8).
    */
    Route::prefix('bons-commande')->name('bons-commande.')->group(function () {
        Route::get('/', [BonCommandeController::class, 'index'])->name('index');
        Route::get('/data', [BonCommandeController::class, 'getData'])->name('data');
        Route::get('/create', [BonCommandeController::class, 'create'])->name('create');
        Route::get('/reference-prix/{article}', [BonCommandeController::class, 'referencePrix'])->name('reference-prix');

        Route::post('/', [BonCommandeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [BonCommandeController::class, 'edit'])->name('edit');
        Route::get('/{id}/recapitulatif', [BonCommandeController::class, 'recapitulatif'])->name('recapitulatif');
        Route::put('/{id}', [BonCommandeController::class, 'update'])->name('update');
        Route::delete('/{id}', [BonCommandeController::class, 'destroy'])->name('destroy');

        // Circuit BROUILLON ⇄ SOUMIS (SFD §7.1) — chaque transition est un
        // POST distinct, avec sa propre permission serveur.
        Route::post('/{id}/soumettre', [BonCommandeController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/renvoyer', [BonCommandeController::class, 'renvoyer'])->name('renvoyer');
        Route::post('/{id}/reprendre', [BonCommandeController::class, 'reprendre'])->name('reprendre');

        // PDF — l'objet juridique imprimable (SPEC_UX §17). Lecture :
        // permission index, comme les fiches (SFD §5).
        Route::get('/{id}/pdf', [BonCommandePdfController::class, 'pdf'])->name('pdf');

        // Visa (SFD §7.2) : les signaux de SW-02 puis la validation elle-même.
        Route::get('/{id}/signaux', [BonCommandeController::class, 'signaux'])->name('signaux');
        Route::post('/{id}/valider', [BonCommandeController::class, 'valider'])->name('valider');

        // Fin de vie (SFD §7.5) : M-07 annuler, M-03 clôturer le reliquat.
        Route::post('/{id}/annuler', [BonCommandeController::class, 'annuler'])->name('annuler');
        Route::post('/{id}/cloturer', [BonCommandeController::class, 'cloturer'])->name('cloturer');

        // A-04 — la fiche, en DERNIER : /{id} capterait tout segment littéral
        // déclaré après lui.
        Route::get('/{id}', [BonCommandeController::class, 'show'])->name('show');
    });

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
