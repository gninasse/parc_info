<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\Route;
use Modules\Achat\Models\BonCommande;
use Modules\Core\Models\User;

/**
 * Grille « actions × statut » de la liste A-02 (SFD §1.4, SPEC_UX A-02).
 *
 * Doctrine des actions (SPEC_UX §0.3), appliquée ICI et nulle part ailleurs :
 *
 *   - action interdite par les DROITS   → **absente** de la charge utile ;
 *   - action impossible par l'ÉTAT      → présente, `actif = false`, avec un
 *     `titre` qui dit pourquoi (jamais un bouton grisé muet).
 *
 * Le calcul est fait côté serveur pour que le navigateur n'ait aucune décision
 * de droit à prendre : il ne peut afficher que ce que le serveur a émis, et le
 * serveur revérifie de toute façon la permission à l'appel de chaque route.
 *
 * Chaque action porte sa MÉTHODE HTTP. C'est indispensable : une transition de
 * statut est un POST, et la rendre en simple lien produirait un GET — donc un
 * 405, ou pire une transition déclenchée par un survol de navigateur. Les
 * actions `GET` deviennent des liens, les `POST` des boutons de commande.
 */
class ActionsBonCommande
{
    /**
     * Actions d'une ligne de la liste, dans l'ordre d'affichage.
     *
     * @return list<array{cle: string, libelle: string, icone: string, classe: string, url: ?string, methode: string, actif: bool, titre: string}>
     */
    public function pour(BonCommande $bon, User $utilisateur): array
    {
        $actions = [];

        foreach ($this->grille($bon, $utilisateur) as $action) {
            // Droits : une action non permise est absente, jamais grisée.
            if (! $utilisateur->can($action['permission'])) {
                continue;
            }

            $actions[] = [
                'cle' => $action['cle'],
                'libelle' => $action['libelle'],
                'icone' => $action['icone'],
                'classe' => $action['classe'],
                'url' => $this->url($action['route'] ?? null, $bon),
                'methode' => $action['methode'] ?? 'GET',
                'actif' => $action['actif'],
                'titre' => $action['titre'],
            ];
        }

        return $actions;
    }

    /**
     * La grille brute du SFD §1.4, avant filtrage par les droits.
     *
     * @return list<array<string, mixed>>
     */
    private function grille(BonCommande $bon, User $utilisateur): array
    {
        $voir = [
            'cle' => 'voir',
            'libelle' => 'Voir',
            'icone' => 'bi-eye',
            'classe' => 'btn-outline-secondary',
            'permission' => 'achat.bons_commande.index',
            'route' => 'achat.bons-commande.show',
            'actif' => true,
            'titre' => 'Voir le bon',
        ];

        $pdf = [
            'cle' => 'pdf',
            'libelle' => 'PDF',
            'icone' => 'bi-printer',
            'classe' => 'btn-outline-primary',
            'permission' => 'achat.bons_commande.index',
            'route' => 'achat.bons-commande.pdf',
            'actif' => true,
            'titre' => 'Imprimer le bon',
        ];

        return match ($bon->statut) {
            BonCommande::STATUT_BROUILLON => [
                $voir,
                [
                    'cle' => 'modifier',
                    'libelle' => 'Modifier',
                    'icone' => 'bi-pencil',
                    'classe' => 'btn-outline-info',
                    'permission' => 'achat.bons_commande.update',
                    'route' => 'achat.bons-commande.edit',
                    'actif' => true,
                    'titre' => 'Modifier le brouillon',
                ],
                [
                    'cle' => 'supprimer',
                    'libelle' => 'Supprimer',
                    'icone' => 'bi-trash',
                    'classe' => 'btn-outline-danger',
                    'permission' => 'achat.bons_commande.destroy',
                    'route' => 'achat.bons-commande.destroy',
                    'methode' => 'DELETE',
                    'actif' => true,
                    'titre' => 'Supprimer le brouillon',
                ],
                [
                    'cle' => 'soumettre',
                    'libelle' => 'Soumettre',
                    'icone' => 'bi-send',
                    'classe' => 'btn-outline-warning',
                    'permission' => 'achat.bons_commande.soumettre',
                    // Depuis la liste, « Soumettre » ouvre le RÉCAPITULATIF :
                    // SW-01 exige une confirmation chiffrée, qu'on ne peut pas
                    // produire depuis une ligne de tableau (SPEC_UX A-03 ②).
                    'route' => 'achat.bons-commande.recapitulatif',
                    // Un brouillon sans ligne n'a rien à soumettre : bouton
                    // grisé et diagnostic explicite (SPEC_UX §0.3).
                    'actif' => $bon->nb_lignes > 0,
                    'titre' => $bon->nb_lignes > 0
                        ? 'Soumettre au visa'
                        : 'Soumettre (aucune ligne)',
                ],
            ],

            BonCommande::STATUT_SOUMIS => array_values(array_filter([
                $voir,
                [
                    'cle' => 'valider',
                    'libelle' => 'Valider',
                    'icone' => 'bi-check-lg',
                    'classe' => 'btn-outline-success',
                    'permission' => 'achat.bons_commande.valider',
                    'route' => 'achat.bons-commande.valider',
                    'methode' => 'POST',
                    'actif' => true,
                    'titre' => 'Valider le bon',
                ],
                [
                    'cle' => 'renvoyer',
                    'libelle' => 'Renvoyer',
                    'icone' => 'bi-arrow-return-left',
                    'classe' => 'btn-outline-warning',
                    'permission' => 'achat.bons_commande.valider',
                    'route' => 'achat.bons-commande.renvoyer',
                    'methode' => 'POST',
                    'actif' => true,
                    'titre' => 'Renvoyer en brouillon (motif obligatoire)',
                ],
                /*
                 * « Reprendre » est le retour à soi-même de l'auteur (SFD §7.1) :
                 * il défait sa propre soumission. Le SFD §5 ne lui donne pas de
                 * permission dédiée ; la lecture retenue est `soumettre`, dont
                 * elle est l'exacte réciproque, restreinte à l'auteur du bon.
                 * Écart signalé dans Modules/Achat/README.md.
                 */
                $bon->created_by === $utilisateur->id ? [
                    'cle' => 'reprendre',
                    'libelle' => 'Reprendre',
                    'icone' => 'bi-arrow-counterclockwise',
                    'classe' => 'btn-outline-secondary',
                    'permission' => 'achat.bons_commande.soumettre',
                    'route' => 'achat.bons-commande.reprendre',
                    'methode' => 'POST',
                    'actif' => true,
                    'titre' => 'Reprendre ma soumission',
                ] : null,
            ])),

            BonCommande::STATUT_VALIDE, BonCommande::STATUT_PARTIEL => array_values(array_filter([
                $voir,
                $pdf,
                // Présentes mais grisées : l'utilisateur a le droit, c'est
                // l'état qui interdit — et l'infobulle indique la sortie.
                [
                    'cle' => 'modifier',
                    'libelle' => 'Modifier',
                    'icone' => 'bi-pencil',
                    'classe' => 'btn-outline-info',
                    'permission' => 'achat.bons_commande.update',
                    'route' => null,
                    'actif' => false,
                    'titre' => $bon->diagnosticModification(),
                ],
                [
                    'cle' => 'supprimer',
                    'libelle' => 'Supprimer',
                    'icone' => 'bi-trash',
                    'classe' => 'btn-outline-danger',
                    'permission' => 'achat.bons_commande.destroy',
                    'route' => null,
                    'actif' => false,
                    'titre' => $bon->diagnosticModification(),
                ],
                /*
                 * M-07 — « présent seulement si aucune réception » (SPEC_UX
                 * A-04) : l'annulation d'un bon déjà livré n'existe pas, même
                 * grisée — la sortie est la clôture du reliquat. Un bon VALIDE
                 * n'a par construction aucune réception (la première le passe
                 * PARTIEL), le garde-fou reste pour un état incohérent.
                 */
                $bon->statut === BonCommande::STATUT_VALIDE && ! $bon->aDesReceptions() ? [
                    'cle' => 'annuler',
                    'libelle' => 'Annuler',
                    'icone' => 'bi-x-octagon',
                    'classe' => 'btn-outline-danger',
                    'permission' => 'achat.bons_commande.annuler',
                    'route' => 'achat.bons-commande.annuler',
                    'methode' => 'POST',
                    'actif' => true,
                    'titre' => 'Annuler le bon (motif obligatoire)',
                ] : null,
                // M-03 — clôturer le reliquat d'un bon partiellement livré.
                $bon->statut === BonCommande::STATUT_PARTIEL ? [
                    'cle' => 'cloturer',
                    'libelle' => 'Clôturer le reliquat',
                    'icone' => 'bi-lock',
                    'classe' => 'btn-outline-dark',
                    'permission' => 'achat.bons_commande.cloturer',
                    'route' => 'achat.bons-commande.cloturer',
                    'methode' => 'POST',
                    'actif' => true,
                    'titre' => 'Renoncer au reste à livrer (motif obligatoire)',
                ] : null,
            ])),

            BonCommande::STATUT_LIVRE, BonCommande::STATUT_CLOTURE => [$voir, $pdf],

            // Un bon annulé est sans effet : il n'a pas de PDF à imprimer.
            BonCommande::STATUT_ANNULE => [$voir],

            default => [$voir],
        };
    }

    /**
     * URL d'une action, ou `null` tant que l'écran cible n'existe pas : la
     * liste n'émet jamais de lien mort. Le bouton reste visible et grisé —
     * il documente la grille du SFD — et s'active de lui-même dès que la
     * route est déclarée.
     */
    private function url(?string $route, BonCommande $bon): ?string
    {
        if ($route === null || ! Route::has($route)) {
            return null;
        }

        return route($route, $bon->id);
    }
}
