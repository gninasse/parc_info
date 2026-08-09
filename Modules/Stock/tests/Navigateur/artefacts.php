<?php

// Produit les artefacts nécessaires à la vérification côté navigateur :
// le HTML réellement rendu et les charges JSON réellement servies.

// Un compte réellement habilité : la vérification doit refléter ce que voit
// un utilisateur autorisé, pas une page 403.
$u = \Modules\Core\Models\User::all()->first(fn ($user) => $user->can('stock.rapports.view'))
    ?? throw new RuntimeException(
        'Aucun utilisateur ne possède « stock.rapports.view » : attribuez un rôle Stock avant de lancer cette vérification.'
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

/*
 * BR-04 — le formulaire d'un brouillon d'entrée PORTANT UNE LIGNE : la saisie
 * des écarts se construit à partir des lignes reçues, un formulaire vide ne
 * prouverait rien. Le brouillon est créé puis SUPPRIMÉ : la base de
 * développement ne garde aucune trace de la vérification.
 */
$magasinier = \Modules\Core\Models\User::all()->first(fn ($user) => $user->can('stock.entrees.store'));

if ($magasinier !== null) {
    $magasin = \Modules\Stock\Models\Magasin::query()->where('est_actif', true)->first();
    $article = \Modules\Catalogue\Models\Article::query()->where('est_actif', true)->first();

    if ($magasin !== null && $article !== null) {
        $brouillon = \Modules\Stock\Models\Entree::create([
            'date_document' => now()->toDateString(),
            'magasin_id' => $magasin->id,
            'nature' => 'livraison',
            'created_by' => $magasinier->id,
        ]);

        $brouillon->lignes()->create([
            'article_id' => $article->id,
            'quantite' => 8,
            'cout_unitaire' => 42000,
        ]);

        auth()->login($magasinier);
        $requete = \Illuminate\Http\Request::create(route('stock.entrees.edit', $brouillon->id), 'GET');
        $requete->setUserResolver(fn () => $magasinier);

        file_put_contents(
            "{$dossier}/entree-brouillon.html",
            app()->handle($requete)->getContent()
        );

        $brouillon->lignes()->delete();
        $brouillon->forceDelete();
    }
}

echo "artefacts écrits dans {$dossier}\n";
