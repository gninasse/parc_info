<?php

use Illuminate\Support\Facades\Route;
use Modules\Organisation\Http\Controllers\BatimentController;
use Modules\Organisation\Http\Controllers\DirectionController;
use Modules\Organisation\Http\Controllers\EtageController;
use Modules\Organisation\Http\Controllers\LocalController;
use Modules\Organisation\Http\Controllers\ServiceController;
use Modules\Organisation\Http\Controllers\SiteController;
use Modules\Organisation\Http\Controllers\UniteController;

Route::middleware(['auth'])->prefix('organisation')->name('organisation.')->group(function () {
    // Sites
    Route::prefix('sites')->name('sites.')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('index');
        Route::get('/data', [SiteController::class, 'getData'])->name('data');
        Route::get('/arborescence', [SiteController::class, 'getArborescence'])->name('arborescence');
        Route::post('/', [SiteController::class, 'store'])->name('store');
        Route::get('/{id}', [SiteController::class, 'show'])->name('show');
        Route::put('/{id}', [SiteController::class, 'update'])->name('update');
        Route::delete('/{id}', [SiteController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [SiteController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Directions
    Route::prefix('directions')->name('directions.')->group(function () {
        Route::get('/', [DirectionController::class, 'index'])->name('index');
        Route::get('/data', [DirectionController::class, 'getData'])->name('data');
        Route::post('/', [DirectionController::class, 'store'])->name('store');
        Route::get('/{id}', [DirectionController::class, 'show'])->name('show');
        Route::put('/{id}', [DirectionController::class, 'update'])->name('update');
        Route::delete('/{id}', [DirectionController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [DirectionController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Services
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/data', [ServiceController::class, 'getData'])->name('data');
        Route::get('/directions-by-site/{siteId}', [ServiceController::class, 'getDirectionsBySite'])->name('directions-by-site');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::get('/{id}', [ServiceController::class, 'show'])->name('show');
        Route::put('/{id}', [ServiceController::class, 'update'])->name('update');
        Route::delete('/{id}', [ServiceController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Unités
    Route::prefix('unites')->name('unites.')->group(function () {
        Route::get('/', [UniteController::class, 'index'])->name('index');
        Route::get('/data', [UniteController::class, 'getData'])->name('data');
        Route::get('/majors', [UniteController::class, 'getMajors'])->name('majors');
        Route::get('/services-by-direction/{directionId}', [UniteController::class, 'getServicesByDirection'])->name('services-by-direction');
        Route::post('/', [UniteController::class, 'store'])->name('store');
        Route::get('/{id}', [UniteController::class, 'show'])->name('show');
        Route::put('/{id}', [UniteController::class, 'update'])->name('update');
        Route::delete('/{id}', [UniteController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [UniteController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Bâtiments
    Route::prefix('batiments')->name('batiments.')->group(function () {
        Route::get('/', [BatimentController::class, 'index'])->name('index');
        Route::get('/data', [BatimentController::class, 'getData'])->name('data');
        Route::get('/by-site/{siteId}', [BatimentController::class, 'getBySite'])->name('by-site');
        Route::post('/', [BatimentController::class, 'store'])->name('store');
        Route::get('/{id}', [BatimentController::class, 'show'])->name('show');
        Route::put('/{id}', [BatimentController::class, 'update'])->name('update');
        Route::delete('/{id}', [BatimentController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [BatimentController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Étages
    Route::prefix('etages')->name('etages.')->group(function () {
        Route::get('/', [EtageController::class, 'index'])->name('index');
        Route::get('/data', [EtageController::class, 'getData'])->name('data');
        Route::get('/by-batiment/{batimentId}', [EtageController::class, 'getByBatiment'])->name('by-batiment');
        Route::post('/', [EtageController::class, 'store'])->name('store');
        Route::get('/{id}', [EtageController::class, 'show'])->name('show');
        Route::put('/{id}', [EtageController::class, 'update'])->name('update');
        Route::delete('/{id}', [EtageController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [EtageController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Locaux
    Route::prefix('locaux')->name('locaux.')->group(function () {
        Route::get('/', [LocalController::class, 'index'])->name('index');
        Route::get('/data', [LocalController::class, 'getData'])->name('data');
        Route::get('/by-etage/{etageId}', [LocalController::class, 'getByEtage'])->name('by-etage');
        Route::post('/', [LocalController::class, 'store'])->name('store');
        Route::get('/{id}', [LocalController::class, 'show'])->name('show');
        Route::put('/{id}', [LocalController::class, 'update'])->name('update');
        Route::delete('/{id}', [LocalController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [LocalController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Postes de travail
    Route::prefix('postes')->name('postes.')->group(function () {
        Route::get('/', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'index'])->name('index');
        Route::get('/data', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'getData'])->name('data');
        Route::get('/search-employes', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/services-by-direction/{directionId}', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'getServicesByDirection'])->name('services-by-direction');
        Route::get('/unites-by-service/{serviceId}', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'getUnitesByService'])->name('unites-by-service');
        Route::post('/', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'store'])->name('store');
        Route::get('/{id}', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'show'])->name('show');
        Route::put('/{id}', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'update'])->name('update');
        Route::delete('/{id}', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [\Modules\Organisation\Http\Controllers\PosteTravailController::class, 'toggleStatus'])->name('toggle-status');
    });
});
