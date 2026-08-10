<?php

// Artefacts de la vérification navigateur de l'écran A-02 : le HTML réellement
// rendu par Blade et la charge JSON réellement servie par `.data`.
//
// Les tests PHPUnit s'arrêtent à la réponse HTTP ; ici on capture ce que le
// navigateur recevra vraiment, pour ensuite exécuter le JS de la vue dessus.

$utilisateur = \Modules\Core\Models\User::all()
    ->first(fn ($u) => $u->can('achat.bons_commande.index'))
    ?? throw new RuntimeException(
        'Aucun utilisateur ne possède « achat.bons_commande.index » : attribuez un rôle Achat avant de lancer cette vérification.'
    );

$appel = function (string $url, bool $json = false) use ($utilisateur) {
    $requete = \Illuminate\Http\Request::create($url, 'GET');

    if ($json) {
        $requete->headers->set('Accept', 'application/json');
    }

    auth()->login($utilisateur);
    $requete->setUserResolver(fn () => $utilisateur);

    return app()->handle($requete)->getContent();
};

$dossier = getenv('ACHAT_ARTEFACTS') ?: sys_get_temp_dir().'/verif_achat';

if (! is_dir($dossier)) {
    mkdir($dossier, 0777, true);
}

file_put_contents("{$dossier}/liste.html", $appel(route('achat.bons-commande.index')));
file_put_contents("{$dossier}/liste.json", $appel(route('achat.bons-commande.data', ['limit' => 100]), true));

/*
 * Le tableau de bord (A-01). Il n'était PAS capturé, ce qui explique que le
 * gel du graphique lui ait échappé : un défaut de mise en page ne se voit ni
 * dans une réponse HTTP correcte, ni dans une charge JSON. La page est donc
 * rendue ici pour que `gel-graphique.cjs` puisse contrôler sa structure.
 */
file_put_contents("{$dossier}/dashboard.html", $appel(route('achat.dashboard')));

// Une charge filtrée, pour vérifier que le pied de tableau suit le filtre.
file_put_contents(
    "{$dossier}/liste-valide.json",
    $appel(route('achat.bons-commande.data', ['statut' => ['VALIDE'], 'limit' => 100]), true)
);

// Une charge vide, pour l'état EV-02.
file_put_contents(
    "{$dossier}/liste-vide.json",
    $appel(route('achat.bons-commande.data', ['search' => 'zzz-inexistant-zzz']), true)
);

/*
 * A-03 — écrans de saisie. L'édition a besoin d'un brouillon PORTANT DES
 * LIGNES : c'est le seul cas qui prouve le verrouillage du fournisseur, le
 * rendu des pilules de TVA et la cohérence des totaux.
 */
$brouillon = \Modules\Achat\Models\BonCommande::query()
    ->where('statut', \Modules\Achat\Models\BonCommande::STATUT_BROUILLON)
    ->whereHas('lignes')
    ->latest('id')
    ->first()
    ?? throw new RuntimeException(
        "Aucun brouillon avec lignes : exécutez d'abord donnees_demo.php."
    );

file_put_contents("{$dossier}/form-create.html", $appel(route('achat.bons-commande.create')));
file_put_contents("{$dossier}/form-edit.html", $appel(route('achat.bons-commande.edit', $brouillon->id)));

// Les montants CALCULÉS PAR LE SERVEUR, pour les confronter à la
// prévisualisation du navigateur (IA-1).
$montants = app(\Modules\Achat\Services\CalculMontantsService::class);
file_put_contents("{$dossier}/brouillon.json", json_encode([
    'data' => [
        'id' => $brouillon->id,
        'montant_ht' => (float) $brouillon->montant_ht,
        'montant_tva' => (float) $brouillon->montant_tva,
        'montant_ttc' => (float) $brouillon->montant_ttc,
        'lignes' => $brouillon->lignes->map(fn ($ligne) => [
            'id' => $ligne->id,
            'quantite' => (float) $ligne->quantite,
            'prix_unitaire_ht' => (float) $ligne->prix_unitaire_ht,
            'taux_tva' => (float) $ligne->taux_tva,
            'montant_ht' => $montants->montantHtLigne($ligne),
        ])->values(),
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

/*
 * Étape ② — récapitulatif. Deux états à capturer : un bon PRÊT à partir
 * (bouton ouvert) et un bon BLOQUÉ (bouton grisé avec son diagnostic). Sans
 * les deux, on ne vérifie que la moitié de la doctrine des actions.
 */
file_put_contents(
    "{$dossier}/recapitulatif-pret.html",
    $appel(route('achat.bons-commande.recapitulatif', $brouillon->id))
);

$bloque = \Modules\Achat\Models\BonCommande::query()
    ->where('statut', \Modules\Achat\Models\BonCommande::STATUT_BROUILLON)
    ->whereDoesntHave('lignes')
    ->latest('id')
    ->first();

if ($bloque !== null) {
    file_put_contents(
        "{$dossier}/recapitulatif-bloque.html",
        $appel(route('achat.bons-commande.recapitulatif', $bloque->id))
    );
}

// Un brouillon RENVOYÉ, pour l'encart jaune de réouverture (UX2-07).
$renvoye = \Modules\Achat\Models\BonCommande::query()
    ->where('statut', \Modules\Achat\Models\BonCommande::STATUT_BROUILLON)
    ->whereNotNull('renvoi_le')
    ->latest('id')
    ->first();

if ($renvoye !== null) {
    file_put_contents(
        "{$dossier}/form-renvoye.html",
        $appel(route('achat.bons-commande.edit', $renvoye->id))
    );
}

// La liste vue par un porteur du visa : les actions valider/renvoyer doivent
// y apparaître sur les bons soumis.
file_put_contents(
    "{$dossier}/liste-visa.json",
    $appel(route('achat.bons-commande.data', ['statut' => ['SOUMIS'], 'limit' => 50]), true)
);

/*
 * D-06 — le visa. Les signaux de SW-02 tels que servis au validateur : le
 * Swal enrichi se construit dessus, il faut donc les capturer sur un bon
 * réellement soumis.
 */
$soumis = \Modules\Achat\Models\BonCommande::query()
    ->where('statut', \Modules\Achat\Models\BonCommande::STATUT_SOUMIS)
    ->latest('id')
    ->first();

if ($soumis !== null) {
    file_put_contents(
        "{$dossier}/signaux.json",
        $appel(route('achat.bons-commande.signaux', $soumis->id), true)
    );
}

/*
 * D-08 — la fiche A-04. Trois états couvrant les rendus qui divergent :
 * un PARTIEL (colonnes de livraison + barres + clôture offerte), un
 * BROUILLON (colonnes masquées) et un SOUMIS (visa offert au validateur —
 * l'utilisateur des artefacts porte les deux casquettes).
 */
$fiches = [
    // Le PARTIEL retenu est celui qui a une VRAIE histoire (trace
    // d'intégration au journal) : un partiel de factory n'a rien à raconter,
    // et c'est justement la chronologie qu'on vérifie (IA-14).
    'fiche-partiel.html' => \Modules\Achat\Models\BonCommande::query()
        ->where('statut', \Modules\Achat\Models\BonCommande::STATUT_PARTIEL)
        ->whereIn('id', \Modules\Achat\Models\IntegrationReception::query()->select('bon_commande_id'))
        ->latest('id')->first(),
    'fiche-brouillon.html' => $brouillon,
    'fiche-soumis.html' => $soumis,
];

foreach ($fiches as $fichier => $bon) {
    if ($bon !== null) {
        file_put_contents(
            "{$dossier}/{$fichier}",
            $appel(route('achat.bons-commande.show', $bon->id))
        );
    }
}

echo "artefacts écrits dans {$dossier}\n";
