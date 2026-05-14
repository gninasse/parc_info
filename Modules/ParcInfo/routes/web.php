<?php

use Illuminate\Support\Facades\Route;
use Modules\ParcInfo\Http\Controllers\OrdinateurController;
use Modules\ParcInfo\Http\Controllers\ServeurController;
use Modules\ParcInfo\Http\Controllers\MobileController;
use Modules\ParcInfo\Http\Controllers\DashboardController;

Route::middleware(['auth'])->prefix('parc-info')->name('parc-info.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ordinateurs
    Route::prefix('informatique/ordinateurs')->name('ordinateurs.')->group(function () {
        Route::get('/', [OrdinateurController::class, 'index'])->name('index');
        Route::get('/data', [OrdinateurController::class, 'getData'])->name('data');
        Route::post('/', [OrdinateurController::class, 'store'])->name('store');
        Route::get('/{id}/json', [OrdinateurController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [OrdinateurController::class, 'show'])->name('show');
        Route::put('/{id}', [OrdinateurController::class, 'update'])->name('update');
        Route::delete('/{id}', [OrdinateurController::class, 'destroy'])->name('destroy');
        Route::get('/search/employes', [OrdinateurController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/search/postes', [OrdinateurController::class, 'searchPostes'])->name('search-postes');
        Route::get('/search/locaux', [OrdinateurController::class, 'searchLocaux'])->name('search-locaux');
        Route::post('/marques', [OrdinateurController::class, 'storeMarque'])->name('store-marque');
        Route::post('/types-ram', [OrdinateurController::class, 'storeTypeRam'])->name('store-type-ram');
        Route::post('/types-os', [OrdinateurController::class, 'storeTypeOs'])->name('store-type-os');
        Route::post('/types-disque', [OrdinateurController::class, 'storeTypeDisque'])->name('store-type-disque');
        Route::post('/types-cpu', [OrdinateurController::class, 'storeTypeCpu'])->name('store-type-cpu');
        Route::post('/affectation', [OrdinateurController::class, 'storeAffectation'])->name('store-affectation');
        Route::patch('/{id}/statut', [OrdinateurController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [OrdinateurController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [OrdinateurController::class, 'desaffecter'])->name('desaffecter');
    });

    // Équipements Réseau
    Route::prefix('informatique/reseaux')->name('reseaux.')->group(function () {
        Route::get('/', [ReseauController::class, 'index'])->name('index');
        Route::get('/data', [ReseauController::class, 'getData'])->name('data');
        Route::post('/', [ReseauController::class, 'store'])->name('store');
        Route::get('/{id}', [ReseauController::class, 'show'])->name('show');
        Route::put('/{id}', [ReseauController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReseauController::class, 'destroy'])->name('destroy');
        Route::post('/types', [ReseauController::class, 'storeTypeReseau'])->name('store-type');
        Route::patch('/{id}/statut', [ReseauController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [ReseauController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [ReseauController::class, 'desaffecter'])->name('desaffecter');
    });
    // Serveurs
    Route::prefix('informatique/serveurs')->name('serveurs.')->group(function () {
        Route::get('/', [ServeurController::class, 'index'])->name('index');
        Route::get('/data', [ServeurController::class, 'getData'])->name('data');
        Route::post('/', [ServeurController::class, 'store'])->name('store');
        Route::get('/{id}', [ServeurController::class, 'show'])->name('show');
        Route::put('/{id}', [ServeurController::class, 'update'])->name('update');
        Route::delete('/{id}', [ServeurController::class, 'destroy'])->name('destroy');
        Route::get('/search/hotes', [ServeurController::class, 'searchHotes'])->name('search-hotes');
        Route::patch('/{id}/statut', [ServeurController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [ServeurController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [ServeurController::class, 'desaffecter'])->name('desaffecter');
    });

    // Mobiles & Tablettes
    Route::prefix('informatique/mobiles')->name('mobiles.')->group(function () {
        Route::get('/', [MobileController::class, 'index'])->name('index');
        Route::get('/data', [MobileController::class, 'getData'])->name('data');
        Route::post('/', [MobileController::class, 'store'])->name('store');
        Route::get('/{id}', [MobileController::class, 'show'])->name('show');
        Route::put('/{id}', [MobileController::class, 'update'])->name('update');
        Route::delete('/{id}', [MobileController::class, 'destroy'])->name('destroy');
        Route::post('/types', [MobileController::class, 'storeTypeMobile'])->name('store-type');
        Route::patch('/{id}/statut', [MobileController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [MobileController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [MobileController::class, 'desaffecter'])->name('desaffecter');
    });
});
