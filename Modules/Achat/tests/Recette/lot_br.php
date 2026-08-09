<?php

/**
 * Recette du lot BR — les bordereaux de réception (CDC §12.3, REC-23 → REC-28).
 *
 * Ce que les tests PHPUnit ne prouvent pas : les scénarios se déroulent sur la
 * base de DÉVELOPPEMENT réelle, avec les comptes et les permissions qui y
 * existent, en passant par les vrais contrôleurs HTTP. C'est le parcours du
 * recetteur, joué en une commande.
 *
 * Tout se déroule dans une transaction ANNULÉE à la fin : la base ressort
 * intacte. Deux artefacts sont laissés dans le dossier temporaire pour
 * inspection à l'œil — le bordereau PDF et la fiche du bon de commande.
 *
 * Usage :
 *     php Modules/Achat/tests/Recette/lot_br.php
 *
 * Prérequis : un compte disposant des permissions Stock (saisie et validation
 * des entrées) et un compte Achat pouvant lire les pièces justificatives.
 */

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;

$racine = dirname(__DIR__, 4);

require $racine.'/vendor/autoload.php';

$app = require $racine.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/*
 * Le jeton CSRF protège les formulaires du navigateur ; ce script appelle les
 * contrôleurs en direct. On neutralise la vérification pour la recette, ce qui
 * ne change rien aux contrôles de PERMISSION, eux bien exercés.
 */
Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::except(['*']);

$resultats = [];
$verifier = function (string $reference, string $attendu, bool $ok) use (&$resultats) {
    $resultats[] = [$ok, $reference, $attendu];
    echo ($ok ? '  ok     ' : '  ÉCHEC  ').str_pad($reference, 10).$attendu.PHP_EOL;
};

/** Un appel HTTP réel, avec la session de l'utilisateur donné. */
$appeler = function (string $methode, string $url, array $charge, User $utilisateur) {
    $requete = Illuminate\Http\Request::create($url, $methode, $charge);
    $requete->headers->set('Accept', 'application/json');
    $requete->setUserResolver(fn () => $utilisateur);
    Auth::login($utilisateur);
    app()->instance('request', $requete);

    return app()->handle($requete);
};

DB::beginTransaction();

try {
    $magasinier = User::all()->first(fn ($u) => $u->can('stock.entrees.store') && $u->can('stock.entrees.valider'))
        ?? throw new RuntimeException('Aucun magasinier en base.');

    $acheteur = User::all()->first(fn ($u) => $u->can('achat.bons_commande.index') && $u->can('achat.documents.view'))
        ?? throw new RuntimeException('Aucun acheteur en base.');

    $magasin = Magasin::query()->where('est_actif', true)->firstOrFail();
    $article = Article::query()->where('est_actif', true)->where('nature', '!=', 'equipement')->firstOrFail();

    // Un bon de commande validé de 20 unités. Le numéro est forcé hors des
    // plages de la fabrique : la base de développement en contient déjà.
    $bon = BonCommande::factory()->valide()->create([
        'numero' => 'BC-RECETTE-'.substr((string) microtime(true), -6),
    ]);
    $ligneBc = LigneCommande::factory()->create([
        'bon_commande_id' => $bon->id,
        'article_id' => $article->id,
        'designation' => $article->nom,
        'nature' => $article->nature,
        'quantite' => 20,
        'quantite_livree' => 0,
        'prix_unitaire_ht' => 42000,
    ]);

    echo PHP_EOL.'── REC-24 : le bordereau signé par le livreur, avec écarts ──'.PHP_EOL;

    // Le BL annonce 10, on compte 8.
    $reponse = $appeler('POST', route('stock.entrees.store'), [
        'magasin_id' => $magasin->id,
        'date_document' => now()->toDateString(),
        'nature' => 'livraison',
        'bon_commande_id' => $bon->id,
        'fournisseur_id' => $bon->fournisseur_id,
        'reference_externe' => 'BL-RECETTE-001',
        'observation_type' => 'ecart_bl',
        'lignes' => [['article_id' => $article->id, 'quantite' => 8, 'cout_unitaire' => 42000]],
        'ecarts_bl' => [[
            'article_id' => $article->id,
            'quantite_annoncee_bl' => 10,
            'quantite_comptee' => 8,
            'motif' => 'manquant',
        ]],
    ], $magasinier);

    $entreeId = json_decode($reponse->getContent(), true)['data']['id'] ?? null;
    $verifier('REC-24', 'le brouillon avec écart est enregistré', $reponse->getStatusCode() === 200 && $entreeId !== null);

    if ($entreeId === null) {
        throw new RuntimeException('Brouillon non créé : '.substr($reponse->getContent(), 0, 300));
    }

    $validation = $appeler('POST', route('stock.entrees.valider', $entreeId), ['jeton' => 'recette-br'], $magasinier);
    $verifier('REC-24', 'la validation passe (écart non bloquant)', $validation->getStatusCode() === 200);

    $ligneBc->refresh();
    $verifier('REC-24', 'la commande est à 8 livrées (et non 10)', abs((float) $ligneBc->quantite_livree - 8) < 0.01);
    $verifier('REC-24', 'le reste à livrer est de 12', abs((float) $ligneBc->reste - 12) < 0.01);

    $pdf = $appeler('GET', route('stock.entrees.bordereau-reception', $entreeId), [], $magasinier)->getContent();
    file_put_contents(sys_get_temp_dir().'/recette_bordereau.pdf', $pdf);
    $verifier('REC-24', 'le bordereau PDF est produit', str_starts_with($pdf, '%PDF'));

    echo PHP_EOL.'── REC-23 : la garde « BL obligatoire » ──'.PHP_EOL;

    config(['stock.bl_obligatoire_si_commande' => true]);

    $reponse = $appeler('POST', route('stock.entrees.store'), [
        'magasin_id' => $magasin->id,
        'date_document' => now()->toDateString(),
        'nature' => 'livraison',
        'bon_commande_id' => $bon->id,
        'fournisseur_id' => $bon->fournisseur_id,
        'lignes' => [['article_id' => $article->id, 'quantite' => 2, 'cout_unitaire' => 42000]],
    ], $magasinier);
    $sansBl = json_decode($reponse->getContent(), true)['data']['id'] ?? null;

    $refus = $appeler('POST', route('stock.entrees.valider', $sansBl), ['jeton' => 'recette-sans-bl'], $magasinier);
    $message = json_decode($refus->getContent(), true)['message'] ?? '';
    $verifier('REC-23', 'la validation sans BL est refusée (422)', $refus->getStatusCode() === 422);
    $verifier('REC-23', 'le message nomme la pièce attendue', str_contains($message, 'bordereau du fournisseur'));

    // On joint le BL, puis on revalide.
    $depot = $appeler('POST', route('stock.documents.store', ['entrees', $sansBl]), [
        'type' => Document::TYPE_BL_FOURNISSEUR,
        'fichiers' => [UploadedFile::fake()->create('bl-comptoir.jpg', 40, 'image/jpeg')],
    ], $magasinier);
    $verifier('REC-23', 'la photo du BL est acceptée et typée', $depot->getStatusCode() === 200);

    $apres = $appeler('POST', route('stock.entrees.valider', $sansBl), ['jeton' => 'recette-avec-bl'], $magasinier);
    $verifier('REC-23', 'la validation passe une fois le BL joint', $apres->getStatusCode() === 200);

    config(['stock.bl_obligatoire_si_commande' => false]);

    echo PHP_EOL.'── REC-25 : la consultation croisée depuis Achat ──'.PHP_EOL;

    $piece = Entree::query()->findOrFail($sansBl)->documents()->firstOrFail();

    $bordereau = $appeler('GET', route('achat.bons-commande.receptions.bordereau', [$bon->id, $sansBl]), [], $acheteur);
    $verifier('REC-25', 'l\'acheteur imprime le bordereau (200)', $bordereau->getStatusCode() === 200);

    $telechargement = $appeler('GET', route('achat.bons-commande.receptions.documents', [$bon->id, $sansBl, $piece->id]), [], $acheteur);
    $verifier('REC-25', 'l\'acheteur télécharge le BL (200)', $telechargement->getStatusCode() === 200);

    // Une pièce d'un AUTRE bon : 404 attendu.
    $autreBon = BonCommande::factory()->valide()->create([
        'numero' => 'BC-RECETTE2-'.substr((string) microtime(true), -6),
    ]);
    $intrusion = $appeler('GET', route('achat.bons-commande.receptions.documents', [$autreBon->id, $sansBl, $piece->id]), [], $acheteur);
    $verifier('REC-25', 'une pièce hors dossier répond 404', $intrusion->getStatusCode() === 404);

    $fiche = $appeler('GET', route('achat.bons-commande.show', $bon->id), [], $acheteur);
    $html = $fiche->getContent();
    file_put_contents(sys_get_temp_dir().'/recette_fiche.html', $html);

    $verifier('REC-25', 'la fiche montre le bordereau et l\'écart',
        str_contains($html, 'Bordereau de réception') && str_contains($html, 'Écart BL'));
    $verifier('REC-25', 'aucune URL du magasin n\'apparaît dans la page',
        ! str_contains($html, '/stock/documents') && ! str_contains($html, $piece->chemin));

    echo PHP_EOL."── REC-26 : la pièce retirée d'un bon validé ──".PHP_EOL;

    $sansMotif = $appeler('DELETE', route('stock.documents.destroy', ['entrees', $sansBl, $piece->id]), [], $magasinier);
    $verifier('REC-26', 'la suppression sans motif est refusée', $sansMotif->getStatusCode() === 422);

    $avecMotif = $appeler('DELETE', route('stock.documents.destroy', ['entrees', $sansBl, $piece->id]), [
        'motif' => 'Numérisation illisible, remplacée par un scan propre',
    ], $magasinier);
    $verifier('REC-26', 'la suppression motivée est acceptée', $avecMotif->getStatusCode() === 200);

    $tombale = $piece->fresh();
    $verifier('REC-26', 'la ligne reste, motivée et signée',
        $tombale !== null && $tombale->est_supprime && $tombale->motif_suppression !== null);

    $apresSuppression = $appeler('GET', route('achat.bons-commande.receptions.documents', [$bon->id, $sansBl, $piece->id]), [], $acheteur);
    $verifier('REC-26', 'le téléchargement répond 410 depuis Achat', $apresSuppression->getStatusCode() === 410);

    echo PHP_EOL.'── REC-27 : le 9e signal ──'.PHP_EOL;

    Auth::login($acheteur);
    $signal = app(\Modules\Achat\Services\SignauxService::class)->tous()['ecarts_bl'];
    $lignes = collect($signal['lignes']);

    $verifier('REC-27', 'le signal existe et porte un titre',
        ($signal['titre'] ?? '') !== '' && ($signal['aide'] ?? '') !== '');
    $verifier('REC-27', 'le fournisseur en écart y figure avec son taux',
        $lignes->contains(fn ($l) => $l['livraisons_avec_ecart'] >= 1 && $l['taux_pct'] > 0));

    echo PHP_EOL.'── REC-28 : Achat survit à un Stock indisponible ──'.PHP_EOL;

    DB::statement('ALTER TABLE stock_documents RENAME TO stock_documents_recette');

    $degrade = $appeler('GET', route('achat.bons-commande.show', $bon->id), [], $acheteur);
    $verifier('REC-28', 'la fiche du bon s\'affiche quand même (200)', $degrade->getStatusCode() === 200);

    $ligneBc->refresh();
    $verifier('REC-28', 'les compteurs d\'Achat restent exacts', abs((float) $ligneBc->quantite_livree - 10) < 0.01);

    DB::statement('ALTER TABLE stock_documents_recette RENAME TO stock_documents');

    echo PHP_EOL;
    $echecs = collect($resultats)->reject(fn ($r) => $r[0])->count();
    echo $echecs === 0
        ? 'RECETTE DU LOT BR : '.count($resultats).' contrôles, tous conformes'.PHP_EOL
        : "RECETTE : {$echecs} contrôle(s) en échec sur ".count($resultats).PHP_EOL;
} finally {
    DB::rollBack();
    echo 'base de développement inchangée (transaction annulée)'.PHP_EOL;
}
