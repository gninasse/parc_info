<?php

// Jeu de démonstration pour la vérification navigateur : un bon par statut,
// une régularisation, un partiel avec reliquat, et un bon d'entrée lié.
//
// Sans données couvrant TOUS les statuts, la vérification ne prouverait rien
// de la grille actions × statut : elle ne verrait qu'un cas sur sept. Ce jeu
// est réservé à la vérification et n'est pas monté par AchatDatabaseSeeder.

use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;

$fournisseur = Fournisseur::query()->first()
    ?? throw new RuntimeException('Aucun fournisseur au Catalogue : semez le Catalogue avant.');

$auteur = User::all()->first(fn ($u) => $u->can('achat.bons_commande.index'))
    ?? throw new RuntimeException('Aucun utilisateur habilité pour le module Achat.');

$creer = function (array $etats, array $attributs = [], int $lignes = 2, ?float $livree = null) use ($fournisseur, $auteur): BonCommande {
    $factory = BonCommande::factory();

    foreach ($etats as $etat) {
        $factory = $factory->{$etat}();
    }

    $bon = $factory->create(array_merge([
        'fournisseur_id' => $fournisseur->id,
        'created_by' => $auteur->id,
    ], $attributs));

    LigneCommande::factory()->count($lignes)->create([
        'bon_commande_id' => $bon->id,
        'quantite' => 10,
        'quantite_livree' => $livree ?? 0,
        'prix_unitaire_ht' => 250000,
        'taux_tva' => 18,
    ]);

    app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

    return $bon->refresh();
};

$creer([]);                                   // brouillon
$creer([], [], 0);                            // brouillon sans ligne (soumission grisée)
$creer(['soumis']);
$creer(['valide']);
$creer(['partiel'], [], 2, 6);                // barre de livraison à 60 %
$creer(['livre'], [], 2, 10);
$creer(['cloture']);
$creer(['annule']);
$creer(['valide', 'regularisation']);         // pictogramme orange hachuré

/*
 * Un brouillon RENVOYÉ par le visa : c'est le seul état qui fait apparaître
 * l'encart jaune de réouverture (UX2-07). On passe par le circuit réel plutôt
 * que d'écrire les colonnes à la main, pour que la démonstration reflète ce
 * que produit vraiment l'application.
 */
$aRenvoyer = $creer([]);
$circuit = app(\Modules\Achat\Services\CircuitSoumissionService::class);
$circuit->soumettre($aRenvoyer, $auteur);
$circuit->renvoyer(
    $aRenvoyer->refresh(),
    $auteur,
    'Le prix du toner dépasse le marché en cours : renégociez avant de resoumettre.'
);

/*
 * Un bon PARTIEL passé par le CIRCUIT RÉEL (soumission → visa → réception) :
 * c'est le seul dont la chronologie A-04 raconte quelque chose — les bons
 * fabriqués par factory n'ont pas d'histoire au journal (IA-14 : la fiche ne
 * reconstruit rien).
 */
$vecu = $creer([]);
$circuit->soumettre($vecu, $auteur);
app(\Modules\Achat\Services\VisaService::class)->valider($vecu->refresh(), $auteur);
app(\Modules\Achat\Services\AchatReceptionService::class)->integrer(
    $vecu->id,
    990001,
    $vecu->lignes()->get()->take(1)->map(fn ($l) => [
        'article_id' => $l->article_id,
        'quantite' => 6,
    ])->values()->all(),
    'ENT-2026-0034',
    $auteur->id
);

// Un bon SOUMIS par le circuit réel, lui aussi avec son histoire au journal.
$circuit->soumettre($creer([]), $auteur);

echo 'Jeu de démonstration : '.BonCommande::count()." bons de commande.\n";
