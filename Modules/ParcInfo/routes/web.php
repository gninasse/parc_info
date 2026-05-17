<?php

use Illuminate\Support\Facades\Route;
use Modules\ParcInfo\Http\Controllers\CameraController;
use Modules\ParcInfo\Http\Controllers\ConsommableController;
use Modules\ParcInfo\Http\Controllers\ContratMaintenanceController;
use Modules\ParcInfo\Http\Controllers\FournisseurController;
use Modules\ParcInfo\Http\Controllers\ImprimanteController;
use Modules\ParcInfo\Http\Controllers\LicenceController;
use Modules\ParcInfo\Http\Controllers\LogicielController;
use Modules\ParcInfo\Http\Controllers\MobileController;
use Modules\ParcInfo\Http\Controllers\OrdinateurController;
use Modules\ParcInfo\Http\Controllers\ParcInfoController;
use Modules\ParcInfo\Http\Controllers\ScannerController;
use Modules\ParcInfo\Http\Controllers\ServeurController;
use Modules\ParcInfo\Http\Controllers\TelephoneController;
use Modules\ParcInfo\Http\Controllers\TerminalIPController;

Route::middleware(['auth'])->prefix('parc-info')->name('parc-info.')->group(function () {
    Route::get('/dashboard', [ParcInfoController::class, 'dashboard'])->name('dashboard');
    Route::get('/search/equipements', [ParcInfoController::class, 'searchEquipements'])->name('search-equipements');

    // Licences
    Route::prefix('informatique/licences')->group(function () {
        Route::get('/', [LicenceController::class, 'index'])->name('licences.index');
        Route::get('/data', [LicenceController::class, 'getData'])->name('licences.data');
        Route::get('/create', [LicenceController::class, 'create'])->name('licences.create');
        Route::post('/', [LicenceController::class, 'store'])->name('licences.store');
        Route::get('/{id}', [LicenceController::class, 'show'])->name('licences.show');
        Route::put('/{id}', [LicenceController::class, 'update'])->name('licences.update');
        Route::patch('/{id}/toggle', [LicenceController::class, 'toggleStatus'])->name('licences.toggle');
        Route::delete('/{id}', [LicenceController::class, 'destroy'])->name('licences.destroy');
        Route::post('/{id}/affecter', [LicenceController::class, 'affecter'])->name('licences.affecter');
        Route::post('/{id}/renouveler', [LicenceController::class, 'renouveler'])->name('licences.renouveler');
        Route::post('/fournisseurs/quick-add', [LicenceController::class, 'storeFournisseur'])->name('licences.store-fournisseur');
        Route::post('/contrats/quick-add', [LicenceController::class, 'storeContrat'])->name('licences.store-contrat');
    });

    // Logiciels
    Route::prefix('informatique/logiciels')->group(function () {
        Route::get('/', [LogicielController::class, 'index'])->name('logiciels.index');
        Route::get('/data', [LogicielController::class, 'getData'])->name('logiciels.data');
        Route::post('/', [LogicielController::class, 'store'])->name('logiciels.store');
        Route::get('/{id}', [LogicielController::class, 'show'])->name('logiciels.show');
        Route::put('/{id}', [LogicielController::class, 'update'])->name('logiciels.update');
        Route::patch('/{id}/toggle', [LogicielController::class, 'toggleStatus'])->name('logiciels.toggle');
        Route::delete('/{id}', [LogicielController::class, 'destroy'])->name('logiciels.destroy');
        Route::post('/editeurs/quick-add', [LogicielController::class, 'storeEditeur'])->name('logiciels.store-editeur');
    });

    // Fournisseurs
    Route::prefix('informatique/fournisseurs')->group(function () {
        Route::get('/', [FournisseurController::class, 'index'])->name('fournisseurs.index');
        Route::get('/data', [FournisseurController::class, 'getData'])->name('fournisseurs.data');
        Route::post('/', [FournisseurController::class, 'store'])->name('fournisseurs.store');
        Route::get('/{id}', [FournisseurController::class, 'show'])->name('fournisseurs.show');
        Route::put('/{id}', [FournisseurController::class, 'update'])->name('fournisseurs.update');
        Route::patch('/{id}/toggle', [FournisseurController::class, 'toggleStatus'])->name('fournisseurs.toggle');
        Route::delete('/{id}', [FournisseurController::class, 'destroy'])->name('fournisseurs.destroy');
        Route::post('/{id}/contacts', [FournisseurController::class, 'storeContact'])->name('fournisseurs.store-contact');
    });

    // Contrats de Maintenance
    Route::prefix('informatique/contrats')->group(function () {
        Route::post('/', [ContratMaintenanceController::class, 'store'])->name('contrats.store');
        Route::get('/{id}', [ContratMaintenanceController::class, 'show'])->name('contrats.show');
        Route::put('/{id}', [ContratMaintenanceController::class, 'update'])->name('contrats.update');
        Route::delete('/{id}', [ContratMaintenanceController::class, 'destroy'])->name('contrats.destroy');
    });

    // Consommables
    Route::prefix('informatique/consommables')->group(function () {
        Route::get('/', [ConsommableController::class, 'index'])->name('consommables.index');
        Route::get('/data', [ConsommableController::class, 'getData'])->name('consommables.data');
        Route::post('/', [ConsommableController::class, 'store'])->name('consommables.store');
        Route::get('/{id}', [ConsommableController::class, 'show'])->name('consommables.show');
        Route::put('/{id}', [ConsommableController::class, 'update'])->name('consommables.update');
        Route::patch('/{id}/toggle', [ConsommableController::class, 'toggleStatus'])->name('consommables.toggle');
        Route::delete('/{id}', [ConsommableController::class, 'destroy'])->name('consommables.destroy');
        Route::post('/{id}/consommer', [ConsommableController::class, 'consommer'])->name('consommables.consommer');
        Route::post('/{id}/approvisionner', [ConsommableController::class, 'approvisionner'])->name('consommables.approvisionner');
        Route::post('/types/quick-add', [ConsommableController::class, 'storeType'])->name('consommables.store-type');
    });

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
        Route::post('/affectation', [MobileController::class, 'storeAffectation'])->name('store-affectation');
    });

    // Switches
    Route::prefix('informatique/switches')->name('switches.')->group(function () {
        Route::get('/', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'index'])->name('index');
        Route::get('/data', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'getData'])->name('data');
        Route::post('/', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'store'])->name('store');
        Route::post('/types/quick-add', [Modules\ParcInfo\Http\Controllers\SwitchController::class, 'storeTypeReseau'])->name('store-type');
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
        Route::post('/types/quick-add', [Modules\ParcInfo\Http\Controllers\RouteurController::class, 'storeTypeReseau'])->name('store-type');
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
        Route::post('/types/quick-add', [Modules\ParcInfo\Http\Controllers\WifiController::class, 'storeTypeReseau'])->name('store-type');
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
        Route::post('/types/quick-add', [Modules\ParcInfo\Http\Controllers\PareFeuController::class, 'storeTypeReseau'])->name('store-type');
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
        Route::post('/types/quick-add', [Modules\ParcInfo\Http\Controllers\OnduleurController::class, 'storeTypeInfrastructure'])->name('store-type');
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
        Route::post('/types/quick-add', [Modules\ParcInfo\Http\Controllers\RackController::class, 'storeTypeInfrastructure'])->name('store-type');
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
        Route::post('/types/quick-add', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'storeTypeInfrastructure'])->name('store-type');
        Route::get('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [Modules\ParcInfo\Http\Controllers\BrassageController::class, 'desaffecter'])->name('desaffecter');
    });

    // Imprimantes
    Route::prefix('informatique/imprimantes')->name('imprimantes.')->group(function () {
        Route::get('/', [ImprimanteController::class, 'index'])->name('index');
        Route::get('/data', [ImprimanteController::class, 'getData'])->name('data');
        Route::post('/', [ImprimanteController::class, 'store'])->name('store');
        Route::post('/types/quick-add', [ImprimanteController::class, 'storeTypeImprimante'])->name('store-type');
        Route::get('/{id}', [ImprimanteController::class, 'show'])->name('show');
        Route::put('/{id}', [ImprimanteController::class, 'update'])->name('update');
        Route::delete('/{id}', [ImprimanteController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [ImprimanteController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [ImprimanteController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [ImprimanteController::class, 'desaffecter'])->name('desaffecter');
    });

    // Scanners
    Route::prefix('informatique/scanners')->name('scanners.')->group(function () {
        Route::get('/', [ScannerController::class, 'index'])->name('index');
        Route::get('/data', [ScannerController::class, 'getData'])->name('data');
        Route::post('/', [ScannerController::class, 'store'])->name('store');
        Route::get('/{id}', [ScannerController::class, 'show'])->name('show');
        Route::put('/{id}', [ScannerController::class, 'update'])->name('update');
        Route::delete('/{id}', [ScannerController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [ScannerController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [ScannerController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [ScannerController::class, 'desaffecter'])->name('desaffecter');
    });

    // Telephones
    Route::prefix('informatique/telephonie')->name('telephonie.')->group(function () {
        Route::get('/', [TelephoneController::class, 'index'])->name('index');
        Route::get('/data', [TelephoneController::class, 'getData'])->name('data');
        Route::post('/', [TelephoneController::class, 'store'])->name('store');
        Route::get('/{id}', [TelephoneController::class, 'show'])->name('show');
        Route::put('/{id}', [TelephoneController::class, 'update'])->name('update');
        Route::delete('/{id}', [TelephoneController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [TelephoneController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [TelephoneController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [TelephoneController::class, 'desaffecter'])->name('desaffecter');
    });

    // Terminaux IP
    Route::prefix('informatique/terminaux-ip')->name('terminaux-ip.')->group(function () {
        Route::get('/', [TerminalIPController::class, 'index'])->name('index');
        Route::get('/data', [TerminalIPController::class, 'getData'])->name('data');
        Route::post('/', [TerminalIPController::class, 'store'])->name('store');
        Route::get('/{id}', [TerminalIPController::class, 'show'])->name('show');
        Route::put('/{id}', [TerminalIPController::class, 'update'])->name('update');
        Route::delete('/{id}', [TerminalIPController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [TerminalIPController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [TerminalIPController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [TerminalIPController::class, 'desaffecter'])->name('desaffecter');
    });

    // Cameras
    Route::prefix('informatique/cameras')->name('cameras.')->group(function () {
        Route::get('/', [CameraController::class, 'index'])->name('index');
        Route::get('/data', [CameraController::class, 'getData'])->name('data');
        Route::post('/', [CameraController::class, 'store'])->name('store');
        Route::get('/{id}', [CameraController::class, 'show'])->name('show');
        Route::put('/{id}', [CameraController::class, 'update'])->name('update');
        Route::delete('/{id}', [CameraController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [CameraController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [CameraController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [CameraController::class, 'desaffecter'])->name('desaffecter');
    });

});
