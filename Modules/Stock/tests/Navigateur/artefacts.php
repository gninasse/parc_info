<?php

// Produit les artefacts nécessaires à la vérification côté navigateur :
// le HTML réellement rendu et les charges JSON réellement servies.

// Un compte réellement habilité : la vérification doit refléter ce que voit
// un utilisateur autorisé, pas une page 403.
$u = \Modules\Core\Models\User::all()->first(fn ($user) => $user->can('stock.rapports.view'))
    ?? throw new RuntimeException(
        "Aucun utilisateur ne possède « stock.rapports.view » : attribuez un rôle Stock avant de lancer cette vérification."
    );

$appel = function (string $url, bool $json = false) use ($u) {
    $req = \Illuminate\Http\Request::create($url, 'GET');
    if ($json) {
        $req->headers->set('Accept', 'application/json');
    }
    auth()->login($u);
    $req->setUserResolver(fn () => $u);

    return app()->handle($req)->getContent();
};

$dossier = getenv('STOCK_ARTEFACTS') ?: sys_get_temp_dir().'/verif_stock';
if (! is_dir($dossier)) {
    mkdir($dossier, 0777, true);
}

foreach (['mouvements', 'valorisation', 'alertes', 'rotation'] as $code) {
    file_put_contents("{$dossier}/show-{$code}.html", $appel(route('stock.rapports.show', $code)));
    file_put_contents("{$dossier}/data-{$code}.json", $appel(route('stock.rapports.data', $code), true));
}

file_put_contents("{$dossier}/statistiques.html", $appel(route('stock.statistiques.index')));
file_put_contents("{$dossier}/statistiques.json", $appel(route('stock.statistiques.data'), true));

echo "artefacts écrits dans {$dossier}\n";
