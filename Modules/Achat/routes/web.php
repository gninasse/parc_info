<?php

use Illuminate\Support\Facades\Route;
use Modules\Achat\Http\Controllers\AdministrationController;
use Modules\Achat\Http\Controllers\ApiController;
use Modules\Achat\Http\Controllers\BonCommandeController;
use Modules\Achat\Http\Controllers\BonCommandePdfController;
use Modules\Achat\Http\Controllers\DashboardController;
use Modules\Achat\Http\Controllers\DocumentController;
use Modules\Achat\Http\Controllers\DocumentsReceptionController;
use Modules\Achat\Http\Controllers\RapportController;
use Modules\Achat\Http\Controllers\ReceptionLicencesController;
use Modules\Achat\Http\Controllers\RegularisationController;
use Modules\Achat\Http\Controllers\ReliquatController;

/*
|--------------------------------------------------------------------------
| Routes du module Achat (SFD §8)
|--------------------------------------------------------------------------
| Préfixe /achat, noms achat.*, `auth` + permission SERVEUR sur TOUTES les
| routes (.data, cascades, PDF, exports, API compris) ; routes littérales
| avant /{id}.
*/

Route::middleware(['auth'])->prefix('achat')->name('achat.')->group(function () {
    // A-01 — Tableau de bord
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    | A-02 (liste) et A-03 (création / édition d'un brouillon). Les routes
    | LITTÉRALES sont déclarées avant tout segment /{id} — sinon /create
    | serait capté comme un identifiant. Chaque route porte sa permission
    | serveur, y compris .data et la référence de prix (SFD §8).
    */
    Route::prefix('bons-commande')->name('bons-commande.')->group(function () {
        Route::get('/', [BonCommandeController::class, 'index'])->name('index');
        Route::get('/data', [BonCommandeController::class, 'getData'])->name('data');
        Route::get('/create', [BonCommandeController::class, 'create'])->name('create');
        Route::get('/reference-prix/{article}', [BonCommandeController::class, 'referencePrix'])->name('reference-prix');

        Route::post('/', [BonCommandeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [BonCommandeController::class, 'edit'])->name('edit');
        Route::get('/{id}/recapitulatif', [BonCommandeController::class, 'recapitulatif'])->name('recapitulatif');
        Route::put('/{id}', [BonCommandeController::class, 'update'])->name('update');
        Route::delete('/{id}', [BonCommandeController::class, 'destroy'])->name('destroy');

        // Circuit BROUILLON ⇄ SOUMIS (SFD §7.1) — chaque transition est un
        // POST distinct, avec sa propre permission serveur.
        Route::post('/{id}/soumettre', [BonCommandeController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/renvoyer', [BonCommandeController::class, 'renvoyer'])->name('renvoyer');
        Route::post('/{id}/reprendre', [BonCommandeController::class, 'reprendre'])->name('reprendre');

        // PDF — l'objet juridique imprimable (SPEC_UX §17). Lecture :
        // permission index, comme les fiches (SFD §5).
        Route::get('/{id}/pdf', [BonCommandePdfController::class, 'pdf'])->name('pdf');

        // Visa (SFD §7.2) : les signaux de SW-02 puis la validation elle-même.
        Route::get('/{id}/signaux', [BonCommandeController::class, 'signaux'])->name('signaux');
        Route::post('/{id}/valider', [BonCommandeController::class, 'valider'])->name('valider');

        // Fin de vie (SFD §7.5) : M-07 annuler, M-03 clôturer le reliquat.
        Route::post('/{id}/annuler', [BonCommandeController::class, 'annuler'])->name('annuler');
        Route::post('/{id}/cloturer', [BonCommandeController::class, 'cloturer'])->name('cloturer');

        // D-09 — pièces justificatives (M-05, M-08). Le téléchargement passe
        // par cette route contrôlée : l'URL directe du fichier n'existe pas.
        Route::prefix('{id}/documents')->name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::get('/{document}/telecharger', [DocumentController::class, 'telecharger'])->name('telecharger');
            Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        });

        // BR-03 — le dossier documentaire de la LIVRAISON, servi par Achat.
        // Routes PROXY : elles vérifient les permissions d'Achat puis relaient
        // les fichiers de Stock. Aucune URL Stock n'est exposée, et aucun droit
        // Stock n'est exigé — l'acheteur consulte les pièces de SES commandes.
        Route::prefix('{id}/receptions/{entree}')->name('receptions.')->group(function () {
            Route::get('/bordereau', [DocumentsReceptionController::class, 'bordereau'])->name('bordereau');
            Route::get('/documents/{document}', [DocumentsReceptionController::class, 'telecharger'])->name('documents');
        });

        // A-04 — la fiche, en DERNIER : /{id} capterait tout segment littéral
        // déclaré après lui.
        Route::get('/{id}', [BonCommandeController::class, 'show'])->name('show');
    });

    /*
    | A-05 — réception des natures NON STOCKABLES (D-13). Permission
    | achat.licences.receptionner portée par le contrôleur ; le préfixe est
    | distinct de /bons-commande/{id} pour ne pas entrer en collision avec
    | la fiche, déclarée en dernier.
    */
    Route::prefix('licences/{bon}/lignes/{ligne}')->name('licences.')->group(function () {
        Route::get('/preparer', [ReceptionLicencesController::class, 'preparer'])->name('preparer');
        Route::post('/ouvrir', [ReceptionLicencesController::class, 'ouvrir'])->name('ouvrir');
        Route::post('/service-fait', [ReceptionLicencesController::class, 'serviceFait'])->name('service-fait');

        Route::prefix('receptions/{reception}')->group(function () {
            Route::get('/', [ReceptionLicencesController::class, 'wizard'])->name('wizard');
            Route::post('/cles', [ReceptionLicencesController::class, 'saisir'])->name('saisir');
            Route::post('/importer', [ReceptionLicencesController::class, 'importer'])->name('importer');
            Route::delete('/cles/{tampon}', [ReceptionLicencesController::class, 'supprimerCle'])->name('supprimer-cle');
            Route::post('/finaliser', [ReceptionLicencesController::class, 'finaliser'])->name('finaliser');
            Route::post('/abandonner', [ReceptionLicencesController::class, 'abandonner'])->name('abandonner');
        });
    });

    /*
    | A-06 — reliquats (D-14) : la to-do fournisseurs. Lecture seule ; la
    | clôture passe par la route du bon (M-03), avec sa propre permission.
    */
    Route::prefix('reliquats')->name('reliquats.')->group(function () {
        Route::get('/', [ReliquatController::class, 'index'])->name('index');
        Route::get('/data', [ReliquatController::class, 'getData'])->name('data');
        Route::get('/export', [ReliquatController::class, 'export'])->name('export');
    });

    /*
    | D-15 — régularisation de l'intérim (A15, M-09). `regulariser` documente
    | la dette ; `administration.manage` rouvre la porte après extinction.
    */
    Route::prefix('regularisation')->name('regularisation.')->group(function () {
        Route::get('/etat', [RegularisationController::class, 'etat'])->name('etat');
        Route::get('/equipements-candidats', [RegularisationController::class, 'candidats'])->name('candidats');
        Route::post('/reactiver', [RegularisationController::class, 'reactiver'])->name('reactiver');
        Route::post('/{id}/rattacher', [RegularisationController::class, 'rattacher'])->name('rattacher');
        Route::delete('/{id}/equipements/{equipement}', [RegularisationController::class, 'detacher'])->name('detacher');
    });

    /*
    | A-07 — rapports (D-16). Trois permissions distinctes : consulter,
    | exporter, et lire les SIGNAUX (contrôle, UX4-09).
    */
    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::get('/', [RapportController::class, 'index'])->name('index');
        // Littérales avant /{carte}, sinon « signaux » serait pris pour une carte.
        Route::get('/signaux', [RapportController::class, 'signaux'])->name('signaux');
        Route::get('/signaux/export', [RapportController::class, 'exportSignaux'])->name('signaux-export');
        Route::get('/{carte}/data', [RapportController::class, 'donnees'])->name('donnees');
        Route::get('/{carte}/export', [RapportController::class, 'export'])->name('export');
    });

    /*
    | A-08 — administration (D-17). La leçon de la v1 : jamais de table de
    | paramètres sans écran. Effet immédiat, journal de l'ancienne valeur.
    */
    Route::get('/administration', [AdministrationController::class, 'index'])->name('administration');
    Route::patch('/parametres/{cle}', [AdministrationController::class, 'modifier'])->name('parametres.modifier');

    /*
    | API inter-modules (API_Inter_Modules.md §4) — permission achat.api.view
    | portée par le contrôleur. Consommée par Stock (raccordement PRQ-05) et
    | par les écrans Achat. Routes littérales avant /{id}.
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::prefix('bons-commande')->name('bons-commande.')->group(function () {
            Route::get('/a-livrer', [ApiController::class, 'bonsCommandeALivrer'])->name('a-livrer');
            Route::get('/resoudre', [ApiController::class, 'resoudre'])->name('resoudre');
            // §4.7 (D-21) — le SEUL point d'écriture de cette API : il crée un
            // brouillon depuis les articles en alerte du Stock. La permission
            // de création est vérifiée dans le contrôleur, en plus de
            // `achat.api.view` qui ouvre le groupe.
            Route::post('/brouillon-depuis-articles', [ApiController::class, 'brouillonDepuisArticles'])
                ->name('brouillon-depuis-articles');
            Route::get('/{id}/lignes-a-livrer', [ApiController::class, 'lignesALivrer'])->name('lignes-a-livrer');
            Route::get('/{id}/receptions', [ApiController::class, 'receptions'])->name('receptions');
        });

        Route::get('/articles/{id}/historique-prix', [ApiController::class, 'historiquePrix'])->name('articles.historique-prix');
        Route::get('/fournisseurs/{id}/cumul-mois', [ApiController::class, 'cumulMois'])->name('fournisseurs.cumul-mois');
    });
});
