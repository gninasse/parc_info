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

    // Switches
    Route::prefix('informatique/switches')->name('switches.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'desaffecter'])->name('desaffecter');
    });

    // Routeurs
    Route::prefix('informatique/routeurs')->name('routeurs.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'desaffecter'])->name('desaffecter');
    });

    // WiFi
    Route::prefix('informatique/wifi')->name('wifi.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'desaffecter'])->name('desaffecter');
    });

    // Pare-feux
    Route::prefix('informatique/parefeux')->name('parefeux.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'desaffecter'])->name('desaffecter');
    });

    // Onduleurs
    Route::prefix('informatique/onduleurs')->name('onduleurs.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'desaffecter'])->name('desaffecter');
    });

    // Baies & Racks
    Route::prefix('informatique/racks')->name('racks.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\RackController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\RackController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\RackController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\RackController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\RackController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\RackController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\RackController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\RackController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\RackController::class, 'desaffecter'])->name('desaffecter');
    });

    // Brassage
    Route::prefix('informatique/brassage')->name('brassage.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'desaffecter'])->name('desaffecter');
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

    // Onduleurs
    Route::prefix('informatique/onduleurs')->name('onduleurs.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'desaffecter'])->name('desaffecter');
    });

    // Baies & Racks
    Route::prefix('informatique/racks')->name('racks.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\RackController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\RackController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\RackController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\RackController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\RackController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\RackController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\RackController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\RackController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\RackController::class, 'desaffecter'])->name('desaffecter');
    });

    // Brassage
    Route::prefix('informatique/brassage')->name('brassage.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'desaffecter'])->name('desaffecter');
    });

});
