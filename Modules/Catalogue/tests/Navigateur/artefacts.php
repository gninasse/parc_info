<?php

/*
 * Rend la fiche fournisseur réelle pour les contrôles navigateur.
 *
 * Comme pour les artefacts du module Achat : les tests serveur s'arrêtent à
 * la réponse HTTP, on a donc besoin du HTML réellement produit pour vérifier
 * la structure des onglets et de la table des contacts.
 */

$dossier = getenv('CATALOGUE_ARTEFACTS') ?: sys_get_temp_dir().'/verif_catalogue';

if (! is_dir($dossier)) {
    mkdir($dossier, 0777, true);
}

/*
 * On prend un utilisateur RÉELLEMENT HABILITÉ, et non le premier venu : le
 * premier compte de la base n'a aucune permission, et la page rendue serait
 * un « Accès refusé » que les contrôles interpréteraient à tort comme des
 * onglets manquants.
 */
$utilisateur = \Modules\Core\Models\User::query()
    ->get()
    ->first(fn ($candidat) => $candidat->can('catalogue.fournisseurs.index'));

if ($utilisateur === null) {
    echo "Aucun utilisateur habilité « catalogue.fournisseurs.index » : impossible de rendre la fiche.\n";

    return;
}

echo "Rendu au nom de : {$utilisateur->user_name}\n";

auth()->login($utilisateur);

$appel = function (string $url) {
    $requete = \Illuminate\Http\Request::create($url, 'GET');
    $requete->setUserResolver(fn () => auth()->user());

    return app()->handle($requete)->getContent();
};

$fournisseur = \Modules\Catalogue\Models\Fournisseur::query()->first();

if ($fournisseur === null) {
    echo "Aucun fournisseur en base.\n";

    return;
}

file_put_contents(
    "{$dossier}/fiche-fournisseur.html",
    $appel(route('catalogue.fournisseurs.show', $fournisseur->id))
);

echo "Artefacts écrits dans {$dossier}\n";
