<?php

use Illuminate\Support\Facades\Route;
use Modules\Achat\Http\Controllers\AchatController;
use Modules\Achat\Http\Controllers\ArticleController;
use Modules\Achat\Http\Controllers\BonCommandeController;
use Modules\Achat\Http\Controllers\BordereauLivraisonController;
use Modules\Achat\Http\Controllers\StockController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/', [AchatController::class, 'index'])->name('dashboard.index');

    // Articles Catalog
    Route::post('articles/{article}/toggle-actif', [ArticleController::class, 'toggleActif'])->name('articles.toggle-actif');
    Route::post('articles/{article}/dupliquer', [ArticleController::class, 'dupliquer'])->name('articles.dupliquer');
    Route::resource('articles', ArticleController::class)->except(['create', 'edit'])->names('articles');

    // Bons de Commande
    Route::post('bons-commande/{bon_commande}/valider', [BonCommandeController::class, 'valider'])->name('bons-commande.valider');
    Route::post('bons-commande/{bon_commande}/annuler', [BonCommandeController::class, 'annuler'])->name('bons-commande.annuler');
    Route::get('bons-commande/{bon_commande}/imprimer', [BonCommandeController::class, 'imprimer'])->name('bons-commande.imprimer');
    Route::resource('bons-commande', BonCommandeController::class)->parameters([
        'bons-commande' => 'bon_commande',
    ])->names('bons-commande');

    // Bordereaux de Livraison
    Route::get('bons-commande/{bon_commande}/lignes-a-livrer', [BordereauLivraisonController::class, 'getLignesALivrer'])->name('bons-commande.lignes-a-livrer');
    Route::get('bordereaux/{bordereau}/wizard', [BordereauLivraisonController::class, 'wizard'])->name('bordereaux.wizard');
    Route::post('bordereaux/{bordereau}/wizard/{article}/sauvegarder', [BordereauLivraisonController::class, 'sauvegarderWizardEtape'])->name('bordereaux.wizard.sauvegarder');
    Route::post('bordereaux/{bordereau}/wizard/valider', [BordereauLivraisonController::class, 'validerBordereau'])->name('bordereaux.wizard.valider');
    Route::resource('bordereaux', BordereauLivraisonController::class)->names('bordereaux');

    // Stocks
    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
});
