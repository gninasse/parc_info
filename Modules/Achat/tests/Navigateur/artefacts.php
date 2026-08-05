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

echo "artefacts écrits dans {$dossier}\n";
