<?php

use Illuminate\Support\Facades\Route;
use Modules\Achat\Http\Controllers\AchatController;
use Modules\Achat\Http\Controllers\ArticleController;
use Modules\Achat\Http\Controllers\BonCommandeController;
use Modules\Achat\Http\Controllers\BordereauLivraisonController;
use Modules\Achat\Http\Controllers\DocumentController;
use Modules\Achat\Http\Controllers\StatistiquesController;
use Modules\Achat\Http\Controllers\StockController;

/*
|--------------------------------------------------------------------------
| Routes du module Achat
|--------------------------------------------------------------------------
| Préfixe « achat/ », noms « achat.* » (RouteServiceProvider).
|
| PATTERNS §14 — Les routes « data » et les actions nommées sont déclarées
| AVANT les resources, faute de quoi « bons-commande/data » serait capturé par
| « bons-commande/{bon_commande} ».
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Tableau de bord ────────────────────────────────────────────────────
    Route::get('/', [AchatController::class, 'index'])->name('dashboard.index');

    // ── Catalogue des articles ─────────────────────────────────────────────
    Route::get('articles/data', [ArticleController::class, 'getData'])->name('articles.data');
    Route::post('articles/{article}/toggle-actif', [ArticleController::class, 'toggleActif'])->name('articles.toggle-actif');
    Route::post('articles/{article}/dupliquer', [ArticleController::class, 'dupliquer'])->name('articles.dupliquer');
    Route::resource('articles', ArticleController::class)
        ->except(['create', 'edit'])
        ->names('articles');

    // ── Bons de commande ───────────────────────────────────────────────────
    Route::get('bons-commande/data', [BonCommandeController::class, 'getData'])->name('bons-commande.data');
    Route::get('bons-commande/{bon_commande}/lignes-a-livrer', [BordereauLivraisonController::class, 'lignesALivrer'])->name('bons-commande.lignes-a-livrer');
    Route::get('bons-commande/{bon_commande}/imprimer', [BonCommandeController::class, 'imprimer'])->name('bons-commande.imprimer');
    Route::post('bons-commande/{bon_commande}/valider', [BonCommandeController::class, 'valider'])->name('bons-commande.valider');
    Route::post('bons-commande/{bon_commande}/annuler', [BonCommandeController::class, 'annuler'])->name('bons-commande.annuler');
    Route::post('bons-commande/{bon_commande}/cloturer', [BonCommandeController::class, 'cloturer'])->name('bons-commande.cloturer');
    Route::resource('bons-commande', BonCommandeController::class)
        ->parameters(['bons-commande' => 'bon_commande'])
        ->names('bons-commande');

    // ── Bordereaux de livraison ────────────────────────────────────────────
    Route::get('bordereaux/data', [BordereauLivraisonController::class, 'getData'])->name('bordereaux.data');
    Route::get('bordereaux/{bordereau}/imprimer', [BordereauLivraisonController::class, 'imprimer'])->name('bordereaux.imprimer');
    Route::get('bordereaux/{bordereau}/wizard', [BordereauLivraisonController::class, 'wizard'])->name('bordereaux.wizard');
    Route::post('bordereaux/{bordereau}/wizard/{article}/sauvegarder', [BordereauLivraisonController::class, 'sauvegarderEtape'])->name('bordereaux.wizard.sauvegarder');
    Route::post('bordereaux/{bordereau}/wizard/valider', [BordereauLivraisonController::class, 'validerBordereau'])->name('bordereaux.wizard.valider');
    Route::post('bordereaux/{bordereau}/revenir-brouillon', [BordereauLivraisonController::class, 'revenirBrouillon'])->name('bordereaux.revenir-brouillon');
    Route::resource('bordereaux', BordereauLivraisonController::class)
        ->parameters(['bordereaux' => 'bordereau'])
        ->except(['edit'])
        ->names('bordereaux');

    // ── Stocks ─────────────────────────────────────────────────────────────
    Route::get('stocks/data', [StockController::class, 'getData'])->name('stocks.data');
    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');

    // ── États et statistiques ──────────────────────────────────────────────
    Route::get('statistiques/data', [StatistiquesController::class, 'getData'])->name('statistiques.data');
    Route::get('statistiques/pdf', [StatistiquesController::class, 'generatePdf'])->name('statistiques.pdf');
    Route::get('statistiques', [StatistiquesController::class, 'index'])->name('statistiques.index');

    // ── Documents joints ───────────────────────────────────────────────────
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/telecharger', [DocumentController::class, 'download'])->name('documents.telecharger');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});
