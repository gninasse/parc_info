<?php

use Illuminate\Support\Facades\Route;
use Modules\ParcInfo\Http\Controllers\ConsommableController;
use Modules\ParcInfo\Http\Controllers\ContratMaintenanceController;
use Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController;
use Modules\ParcInfo\Http\Controllers\FournisseurController;
use Modules\ParcInfo\Http\Controllers\LicenceController;
use Modules\ParcInfo\Http\Controllers\LogicielController;
use Modules\ParcInfo\Http\Controllers\ParcInfoController;

Route::middleware(['auth'])->prefix('parc-info')->name('parc-info.')->group(function () {
    Route::get('/dashboard', [ParcInfoController::class, 'dashboard'])->name('dashboard');
    Route::get('/search/equipements', [ParcInfoController::class, 'searchEquipements'])->name('search-equipements');
    Route::get('/informatique/equipements/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('equipements.show-json');
    Route::post('/informatique/dictionnaires/valeurs', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->name('dictionnaires.valeurs.store');

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
        Route::post('/affectations/{affectationId}/desaffecter', [LicenceController::class, 'desaffecter'])->name('licences.desaffecter');
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
        Route::get('/create', [FournisseurController::class, 'create'])->name('fournisseurs.create');
        Route::post('/', [FournisseurController::class, 'store'])->name('fournisseurs.store');
        Route::get('/{id}', [FournisseurController::class, 'show'])->name('fournisseurs.show');
        Route::put('/{id}', [FournisseurController::class, 'update'])->name('fournisseurs.update');
        Route::patch('/{id}/toggle', [FournisseurController::class, 'toggleStatus'])->name('fournisseurs.toggle');
        Route::delete('/{id}', [FournisseurController::class, 'destroy'])->name('fournisseurs.destroy');
        Route::post('/{id}/contacts', [FournisseurController::class, 'storeContact'])->name('fournisseurs.store-contact');
        Route::put('/{id}/contacts/{contactId}', [FournisseurController::class, 'updateContact'])->name('fournisseurs.update-contact');
        Route::delete('/{id}/contacts/{contactId}', [FournisseurController::class, 'deleteContact'])->name('fournisseurs.delete-contact');
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
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'ordinateur')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'ordinateur')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'ordinateur')->name('store');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::get('/search/employes', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/search/postes', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/search/locaux', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::post('/types-ram', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_ram')->name('store-type-ram');
        Route::post('/types-os', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_os')->name('store-type-os');
        Route::post('/types-disque', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_disque')->name('store-type-disque');
        Route::post('/types-cpu', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_cpu')->name('store-type-cpu');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
    });

    // Écrans
    Route::prefix('informatique/ecrans')->name('ecrans.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'ecran')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'ecran')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'ecran')->name('store');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::get('/search/employes', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/search/postes', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/search/locaux', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
    });

    // Unités Centrales
    Route::prefix('informatique/unites-centrales')->name('unite-centrales.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'unite-centrale')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'unite-centrale')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'unite-centrale')->name('store');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::get('/search/employes', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/search/postes', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/search/locaux', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::post('/types-ram', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_ram')->name('store-type-ram');
        Route::post('/types-os', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_os')->name('store-type-os');
        Route::post('/types-disque', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_disque')->name('store-type-disque');
        Route::post('/types-cpu', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_cpu')->name('store-type-cpu');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
    });

    // Serveurs
    Route::prefix('informatique/serveurs')->name('serveurs.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'serveur')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'serveur')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'serveur')->name('store');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::get('/search/hotes', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-hotes');
        Route::get('/search/locaux', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
    });

    // Serveurs Virtuels
    Route::prefix('informatique/serveurs-virtuels')->name('serveurs-virtuels.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'serveur-virtuel')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'serveur-virtuel')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'serveur-virtuel')->name('store');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
    });

    // Mobiles & Tablettes
    Route::prefix('informatique/mobiles')->name('mobiles.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'mobile')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'mobile')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'mobile')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::post('/types-mobile', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_mobile')->name('store-type-mobile');
        Route::get('/search/employes', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/search/postes', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/search/locaux', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Switches
    Route::prefix('informatique/switches')->name('switches.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'switch')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'switch')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'switch')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/employes/search', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/postes/search', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Routeurs
    Route::prefix('informatique/routeurs')->name('routeurs.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'routeur')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'routeur')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'routeur')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/employes/search', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/postes/search', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // WiFi
    Route::prefix('informatique/wifi')->name('wifi.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'wifi')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'wifi')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'wifi')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Pare-feux
    Route::prefix('informatique/parefeux')->name('parefeux.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'parefeu')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'parefeu')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'parefeu')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Onduleurs
    Route::prefix('informatique/onduleurs')->name('onduleurs.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'onduleur')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'onduleur')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'onduleur')->name('store');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/employes/search', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/postes/search', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Baies & Racks
    Route::prefix('informatique/infrastructure/racks')->name('racks.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'rack')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'rack')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'rack')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Brassage & Panneaux de brassage
    Route::prefix('informatique/infrastructure/brassage')->name('brassage.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'brassage')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'brassage')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'brassage')->name('store');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
    });

    // Imprimantes
    Route::prefix('informatique/imprimantes')->name('imprimantes.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'imprimante')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'imprimante')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'imprimante')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::post('/types-imprimante', [EquipementDynamiqueController::class, 'storeDictionnaireValeur'])->defaults('dictionnaire_code', 'type_imprimante')->name('store-type-imprimante');
        Route::get('/employes/search', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/postes/search', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Scanners
    Route::prefix('informatique/scanners')->name('scanners.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'scanner')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'scanner')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'scanner')->name('store');
        Route::get('/{id}/json', [EquipementDynamiqueController::class, 'showJson'])->name('show-json');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/employes/search', [EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
        Route::get('/postes/search', [EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Telephones
    Route::prefix('informatique/telephonie')->name('telephonie.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'telephone')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'telephone')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'telephone')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Terminaux IP
    Route::prefix('informatique/terminaux-ip')->name('terminaux-ip.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'telephone')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'telephone')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'telephone')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Cameras
    Route::prefix('informatique/cameras')->name('cameras.')->group(function () {
        Route::get('/', [EquipementDynamiqueController::class, 'index'])->defaults('category', 'camera')->name('index');
        Route::get('/data', [EquipementDynamiqueController::class, 'getData'])->defaults('category', 'camera')->name('data');
        Route::post('/', [EquipementDynamiqueController::class, 'store'])->defaults('category', 'camera')->name('store');
        Route::get('/{id}', [EquipementDynamiqueController::class, 'show'])->name('show');
        Route::put('/{id}', [EquipementDynamiqueController::class, 'update'])->name('update');
        Route::delete('/{id}', [EquipementDynamiqueController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/statut', [EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
        Route::patch('/{id}/etat', [EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
        Route::post('/{id}/desaffecter', [EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
        Route::post('/affectation', [EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
        Route::post('/marques', [EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
        Route::get('/locaux/search', [EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
    });

    // Référentiels
    Route::prefix('referentiels')->name('referentiels.')->group(function () {
        // Types de CPU
        Route::prefix('types-cpus')->name('types-cpus.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeCpuController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeCpuController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeCpuController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeCpuController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeCpuController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeCpuController::class, 'destroy'])->name('destroy');
        });

        // Types de Disques
        Route::prefix('types-disques')->name('types-disques.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeDisqueController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeDisqueController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeDisqueController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeDisqueController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeDisqueController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeDisqueController::class, 'destroy'])->name('destroy');
        });

        // Types d'OS
        Route::prefix('types-os')->name('types-os.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeOsController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeOsController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeOsController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeOsController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeOsController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeOsController::class, 'destroy'])->name('destroy');
        });

        // Types de RAM
        Route::prefix('types-rams')->name('types-rams.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeRamController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeRamController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeRamController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeRamController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeRamController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeRamController::class, 'destroy'])->name('destroy');
        });

        // Marques
        Route::prefix('marques')->name('marques.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\MarqueController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\MarqueController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\MarqueController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\MarqueController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\MarqueController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\MarqueController::class, 'destroy'])->name('destroy');
        });

        // Types d'Imprimantes
        Route::prefix('types-imprimantes')->name('types-imprimantes.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeImprimanteController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeImprimanteController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeImprimanteController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeImprimanteController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeImprimanteController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeImprimanteController::class, 'destroy'])->name('destroy');
        });

        // Types de Mobiles
        Route::prefix('types-mobiles')->name('types-mobiles.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeMobileController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeMobileController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeMobileController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeMobileController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeMobileController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeMobileController::class, 'destroy'])->name('destroy');
        });

        // Types de Licences
        Route::prefix('types-licences')->name('types-licences.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeLicenceController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeLicenceController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeLicenceController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeLicenceController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeLicenceController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeLicenceController::class, 'destroy'])->name('destroy');
        });

        // Types Consommables
        Route::prefix('types-consommables')->name('types-consommables.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeConsommableController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeConsommableController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeConsommableController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeConsommableController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeConsommableController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\TypeConsommableController::class, 'destroy'])->name('destroy');
        });

        // Éditeurs
        Route::prefix('editeurs')->name('editeurs.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\EditeurController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\EditeurController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\EditeurController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\EditeurController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\EditeurController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\EditeurController::class, 'destroy'])->name('destroy');
        });

        // Catégories d'équipements
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'store'])->name('store');
            Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'show'])->name('show');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'destroy'])->name('destroy');

            // Nested dynamic fields management
            Route::get('/{id}/fields/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'getDataFields'])->name('fields.data');
            Route::post('/{id}/fields', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'storeField'])->name('fields.store');
            Route::get('/{id}/fields/{fieldId}', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'showField'])->name('fields.show');
            Route::put('/{id}/fields/{fieldId}', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'updateField'])->name('fields.update');
            Route::delete('/{id}/fields/{fieldId}', [\Modules\ParcInfo\Http\Controllers\Referentiels\CategorieController::class, 'destroyField'])->name('fields.destroy');
        });

        // Dictionnaires Dynamiques
        Route::prefix('dictionnaires')->name('dictionnaires.')->group(function () {
            Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'index'])->name('index');
            Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'getData'])->name('data');
            Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'store'])->name('store');
            Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'destroy'])->name('destroy');

            Route::prefix('{code}/valeurs')->name('valeurs.')->group(function () {
                Route::get('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'indexValues'])->name('index');
                Route::get('/data', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'getDataValues'])->name('data');
                Route::post('/', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'storeValue'])->name('store');
                Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'showValue'])->name('show');
                Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'updateValue'])->name('update');
                Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\Referentiels\DictionnaireController::class, 'destroyValue'])->name('destroy');
            });
        });
    });

    // Analyse
    Route::prefix('analyse')->name('analyse.')->group(function () {
        Route::get('/etats', [\Modules\ParcInfo\Http\Controllers\Analyse\EtatController::class, 'index'])->name('etats.index');
        Route::get('/etats/data', [\Modules\ParcInfo\Http\Controllers\Analyse\EtatController::class, 'getData'])->name('etats.data');
        Route::get('/etats/export', [\Modules\ParcInfo\Http\Controllers\Analyse\EtatController::class, 'export'])->name('etats.export');
        Route::get('/statistiques', [\Modules\ParcInfo\Http\Controllers\Analyse\StatistiquesController::class, 'index'])->name('statistiques.index');
        Route::get('/statistiques/data', [\Modules\ParcInfo\Http\Controllers\Analyse\StatistiquesController::class, 'getData'])->name('statistiques.data');
    });

    // Dynamic Categories Routes (registered dynamically at runtime for all non-hardcoded database categories)
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('parc_info_categories_equipements')) {
            $hardcodedCodes = [
                'ordinateur', 'serveur', 'serveur-virtuel', 'mobile', 'switch', 'routeur',
                'wifi', 'parefeu', 'onduleur', 'rack', 'brassage', 'camera',
                'imprimante', 'scanner', 'telephone', 'terminal-ip', 'ecran', 'unite-centrale',
            ];

            $dynamicCategories = \Modules\ParcInfo\Models\CategorieEquipement::whereNotIn('code', $hardcodedCodes)->get();
            foreach ($dynamicCategories as $cat) {
                $plural = \Illuminate\Support\Str::plural($cat->code);

                Route::prefix('informatique/'.$plural)->name($plural.'.')->group(function () use ($cat) {
                    Route::get('/', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'index'])->defaults('category', $cat->code)->name('index');
                    Route::get('/data', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'getData'])->defaults('category', $cat->code)->name('data');
                    Route::post('/', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'store'])->defaults('category', $cat->code)->name('store');
                    Route::get('/{id}/json', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'showJson'])->name('show-json');
                    Route::get('/{id}', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'show'])->name('show');
                    Route::put('/{id}', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'update'])->name('update');
                    Route::delete('/{id}', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'destroy'])->name('destroy');
                    Route::get('/search/employes', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'searchEmployes'])->name('search-employes');
                    Route::get('/search/postes', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'searchPostes'])->name('search-postes');
                    Route::get('/search/locaux', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'searchLocaux'])->name('search-locaux');
                    Route::post('/marques', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'storeMarque'])->name('store-marque');
                    Route::post('/affectation', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'storeAffectation'])->name('store-affectation');
                    Route::patch('/{id}/statut', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'updateStatut'])->name('update-statut');
                    Route::patch('/{id}/etat', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'updateEtat'])->name('update-etat');
                    Route::post('/{id}/desaffecter', [\Modules\ParcInfo\Http\Controllers\EquipementDynamiqueController::class, 'desaffecter'])->name('desaffecter');
                });
            }
        }
    } catch (\Exception $e) {
        // Prevent console command crashes
    }

});
