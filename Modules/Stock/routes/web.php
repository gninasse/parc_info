<?php

use Illuminate\Support\Facades\Route;
use Modules\Stock\Http\Controllers\DashboardController;
use Modules\Stock\Http\Controllers\DocumentController;
use Modules\Stock\Http\Controllers\EntreeController;
use Modules\Stock\Http\Controllers\MagasinController;
use Modules\Stock\Http\Controllers\NiveauController;
use Modules\Stock\Http\Controllers\RapportController;
use Modules\Stock\Http\Controllers\SortieController;
use Modules\Stock\Http\Controllers\StatistiqueController;
use Modules\Stock\Http\Controllers\TransfertController;

Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Préférences du module (magasin par défaut)
    Route::patch('/preferences/magasin-defaut', [DashboardController::class, 'definirMagasinParDefaut'])->name('preferences.magasin-defaut');

    // Pièces jointes des bons : {type} ∈ entrees|sorties|transferts
    Route::prefix('documents/{type}/{id}')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentController::class, 'download'])->name('download');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
    });

    // Cascade partagée : unités « en stock » non rattachées (sélecteur d'unités)
    Route::get('/equipements/disponibles', [EntreeController::class, 'getEquipementsDisponibles'])->name('equipements.disponibles');

    // Entrées (SFD §8) : routes littérales avant /{id}
    Route::prefix('entrees')->name('entrees.')->group(function () {
        Route::get('/', [EntreeController::class, 'index'])->name('index');
        Route::get('/data', [EntreeController::class, 'getData'])->name('data');
        Route::get('/create', [EntreeController::class, 'create'])->name('create');
        Route::post('/', [EntreeController::class, 'store'])->name('store');
        Route::get('/{id}', [EntreeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EntreeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EntreeController::class, 'update'])->name('update');
        Route::delete('/{id}', [EntreeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/referencement', [EntreeController::class, 'referencement'])->name('referencement');
        Route::get('/{id}/wizard', [EntreeController::class, 'wizard'])->name('wizard');
        Route::put('/{id}/wizard', [EntreeController::class, 'wizardUpdate'])->name('wizard.update');
        Route::post('/{id}/wizard/import', [EntreeController::class, 'wizardImport'])->name('wizard.import');
        Route::post('/{id}/retour-brouillon', [EntreeController::class, 'retourBrouillon'])->name('retour-brouillon');
        Route::post('/{id}/valider', [EntreeController::class, 'valider'])->name('valider');
        Route::get('/{id}/pdf', [EntreeController::class, 'pdf'])->name('pdf');
    });

    // Magasins (référentiel — UX §9) : routes littérales avant /{id}
    Route::prefix('magasins')->name('magasins.')->group(function () {
        Route::get('/', [MagasinController::class, 'index'])->name('index');
        Route::get('/data', [MagasinController::class, 'getData'])->name('data');
        Route::get('/sites-disponibles', [MagasinController::class, 'getSitesDisponibles'])->name('sites-disponibles');
        Route::get('/locaux', [MagasinController::class, 'getLocaux'])->name('locaux');
        Route::get('/responsables', [MagasinController::class, 'getResponsables'])->name('responsables');
        Route::post('/', [MagasinController::class, 'store'])->name('store');
        Route::get('/{id}', [MagasinController::class, 'show'])->name('show');
        Route::get('/{id}/equipements-data', [MagasinController::class, 'getEquipementsData'])->name('equipements-data');
        Route::get('/{id}/mouvements-data', [MagasinController::class, 'getMouvementsData'])->name('mouvements-data');
        Route::put('/{id}', [MagasinController::class, 'update'])->name('update');
        Route::delete('/{id}', [MagasinController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [MagasinController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Cascade des unités pointables : équipements « en stock » DU magasin (D14)
    Route::get('/equipements/du-magasin', [SortieController::class, 'getEquipementsDuMagasin'])->name('equipements.du-magasin');

    // Sélecteurs de bénéficiaires (sorties + retours d'entrées)
    Route::get('/beneficiaires', [SortieController::class, 'getBeneficiaires'])->name('beneficiaires.data');
    Route::get('/beneficiaires/cascade', [SortieController::class, 'getCascade'])->name('beneficiaires.cascade');

    // Sorties (SFD §8) : routes littérales avant /{id}
    Route::prefix('sorties')->name('sorties.')->group(function () {
        Route::get('/', [SortieController::class, 'index'])->name('index');
        Route::get('/data', [SortieController::class, 'getData'])->name('data');
        Route::get('/create', [SortieController::class, 'create'])->name('create');
        Route::get('/disponibilite', [SortieController::class, 'getDisponibilite'])->name('disponibilite');
        Route::post('/', [SortieController::class, 'store'])->name('store');
        Route::get('/{id}', [SortieController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SortieController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SortieController::class, 'update'])->name('update');
        Route::delete('/{id}', [SortieController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/pointage', [SortieController::class, 'pointage'])->name('pointage');
        Route::get('/{id}/pointage', [SortieController::class, 'pointageShow'])->name('pointage.show');
        Route::put('/{id}/pointage', [SortieController::class, 'pointageUpdate'])->name('pointage.update');
        Route::post('/{id}/scan-express', [SortieController::class, 'scanExpress'])->name('scan-express');
        Route::post('/{id}/retour-brouillon', [SortieController::class, 'retourBrouillon'])->name('retour-brouillon');
        Route::post('/{id}/valider', [SortieController::class, 'valider'])->name('valider');
        Route::get('/{id}/pdf', [SortieController::class, 'pdf'])->name('pdf');
    });

    // Transferts (SFD §8, D17) : routes-gabarit des sorties
    Route::prefix('transferts')->name('transferts.')->group(function () {
        Route::get('/', [TransfertController::class, 'index'])->name('index');
        Route::get('/data', [TransfertController::class, 'getData'])->name('data');
        Route::get('/create', [TransfertController::class, 'create'])->name('create');
        Route::post('/', [TransfertController::class, 'store'])->name('store');
        Route::get('/{id}', [TransfertController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TransfertController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TransfertController::class, 'update'])->name('update');
        Route::delete('/{id}', [TransfertController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/pointage', [TransfertController::class, 'pointage'])->name('pointage');
        Route::get('/{id}/pointage', [TransfertController::class, 'pointageShow'])->name('pointage.show');
        Route::put('/{id}/pointage', [TransfertController::class, 'pointageUpdate'])->name('pointage.update');
        Route::post('/{id}/scan-express', [TransfertController::class, 'scanExpress'])->name('scan-express');
        Route::post('/{id}/retour-brouillon', [TransfertController::class, 'retourBrouillon'])->name('retour-brouillon');
        Route::post('/{id}/valider', [TransfertController::class, 'valider'])->name('valider');
        Route::get('/{id}/pdf', [TransfertController::class, 'pdf'])->name('pdf');
    });

    // État des stocks (UX §2)
    Route::prefix('niveaux')->name('niveaux.')->group(function () {
        Route::get('/', [NiveauController::class, 'index'])->name('index');
        Route::get('/data', [NiveauController::class, 'getData'])->name('data');
        Route::get('/export', [NiveauController::class, 'export'])->name('export');
        Route::post('/seuil-article', [NiveauController::class, 'seuilArticle'])->name('seuil-article');
        Route::patch('/{id}/seuil', [NiveauController::class, 'seuil'])->name('seuil');
    });

    // Statistiques (UX §7) — tableau de bord analytique
    Route::prefix('statistiques')->name('statistiques.')->group(function () {
        Route::get('/', [StatistiqueController::class, 'index'])->name('index');
        Route::get('/data', [StatistiqueController::class, 'getData'])->name('data');
    });

    // États / rapports (UX §7) : routes littérales avant /{code}
    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::get('/', [RapportController::class, 'index'])->name('index');
        Route::get('/{code}', [RapportController::class, 'show'])->name('show');
        Route::get('/{code}/data', [RapportController::class, 'getData'])->name('data');
        Route::get('/{code}/export', [RapportController::class, 'export'])->name('export');
    });
});
