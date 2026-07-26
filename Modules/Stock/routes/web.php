<?php

use Illuminate\Support\Facades\Route;
use Modules\Stock\Http\Controllers\DashboardController;
use Modules\Stock\Http\Controllers\EntreeController;
use Modules\Stock\Http\Controllers\InventaireController;
use Modules\Stock\Http\Controllers\MagasinController;
use Modules\Stock\Http\Controllers\SortieController;
use Modules\Stock\Http\Controllers\TransfertController;
use Modules\Stock\Http\Controllers\ValorisationController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // Magasins CRUD
    Route::get('magasins/data', [MagasinController::class, 'getData'])->name('magasins.data');
    Route::resource('magasins', MagasinController::class)->except(['create', 'edit'])->names('magasins');

    // Responsables
    Route::post('magasins/{magasin}/responsables', [MagasinController::class, 'storeResponsable'])->name('magasins.responsables.store');
    Route::delete('magasins/{magasin}/responsables/{responsable}', [MagasinController::class, 'destroyResponsable'])->name('magasins.responsables.destroy');

    // Droits
    Route::post('magasins/{magasin}/droits', [MagasinController::class, 'storeDroit'])->name('magasins.droits.store');
    Route::delete('magasins/{magasin}/droits/{droit}', [MagasinController::class, 'destroyDroit'])->name('magasins.droits.destroy');

    // Transferts CRUD & Actions
    Route::get('transferts/data', [TransfertController::class, 'getData'])->name('transferts.data');
    Route::post('transferts/{transfert}/valider', [TransfertController::class, 'valider'])->name('transferts.valider');
    Route::post('transferts/{transfert}/rejeter', [TransfertController::class, 'rejeter'])->name('transferts.rejeter');
    Route::post('transferts/{transfert}/annuler', [TransfertController::class, 'annuler'])->name('transferts.annuler');
    Route::resource('transferts', TransfertController::class)->only(['index', 'store', 'show'])->names('transferts');

    // Sorties CRUD & Actions
    Route::get('sorties/data', [SortieController::class, 'getData'])->name('sorties.data');
    Route::resource('sorties', SortieController::class)->only(['index', 'store'])->names('sorties');

    // Inventaires CRUD & Actions
    Route::get('inventaires/data', [InventaireController::class, 'getData'])->name('inventaires.data');
    Route::get('inventaires/{inventaire}/saisie', [InventaireController::class, 'saisieForm'])->name('inventaires.saisie');
    Route::post('inventaires/{inventaire}/saisie', [InventaireController::class, 'enregistrerSaisie'])->name('inventaires.saisie.store');
    Route::post('inventaires/{inventaire}/valider', [InventaireController::class, 'valider'])->name('inventaires.valider');
    Route::post('inventaires/{inventaire}/annuler', [InventaireController::class, 'annuler'])->name('inventaires.annuler');
    Route::resource('inventaires', InventaireController::class)->only(['index', 'store', 'show'])->names('inventaires');

    // Valorisation CRUD & Actions
    Route::get('valorisation/data', [ValorisationController::class, 'getData'])->name('valorisation.data');
    Route::resource('valorisation', ValorisationController::class)->only(['index', 'store', 'show'])->names('valorisation');

    // Entrees CRUD & Actions
    Route::get('entrees/data', [EntreeController::class, 'getData'])->name('entrees.data');
    Route::resource('entrees', EntreeController::class)->only(['index', 'store', 'destroy'])->names('entrees');
});
