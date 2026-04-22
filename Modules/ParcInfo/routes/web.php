<?php

use Illuminate\Support\Facades\Route;
use Modules\ParcInfo\Http\Controllers\OrdinateurController;
use Modules\ParcInfo\Http\Controllers\ParcInfoController;

Route::middleware(['auth'])->prefix('parc-info')->name('parc-info.')->group(function () {
    Route::get('/dashboard', [ParcInfoController::class, 'dashboard'])->name('dashboard');

    // Ordinateurs fixes
    Route::prefix('informatique/ordinateurs-fixes')->name('ordinateurs-fixes.')->group(function () {
        Route::get('/',                          [OrdinateurController::class, 'index'])->name('index');
        Route::get('/data',                      [OrdinateurController::class, 'getData'])->name('data');
        Route::post('/',                         [OrdinateurController::class, 'store'])->name('store');
        Route::get('/{id}/json',                 [OrdinateurController::class, 'showJson'])->name('show-json');
        Route::get('/{id}',                      [OrdinateurController::class, 'show'])->name('show');
        Route::put('/{id}',                      [OrdinateurController::class, 'update'])->name('update');
        Route::delete('/{id}',                   [OrdinateurController::class, 'destroy'])->name('destroy');
        Route::get('/search/employes',           [OrdinateurController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/search/postes',             [OrdinateurController::class, 'searchPostes'])->name('search-postes');
        Route::get('/search/locaux',             [OrdinateurController::class, 'searchLocaux'])->name('search-locaux');
        Route::post('/marques',                  [OrdinateurController::class, 'storeMarque'])->name('store-marque');
        Route::post('/types-ram',                [OrdinateurController::class, 'storeTypeRam'])->name('store-type-ram');
        Route::post('/types-os',                 [OrdinateurController::class, 'storeTypeOs'])->name('store-type-os');
        Route::post('/types-disque',             [OrdinateurController::class, 'storeTypeDisque'])->name('store-type-disque');
        Route::post('/affectation',              [OrdinateurController::class, 'storeAffectation'])->name('store-affectation');
    });
});
