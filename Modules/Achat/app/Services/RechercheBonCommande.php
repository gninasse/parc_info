<?php

namespace Modules\Achat\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Recherche « tous formats » de la liste A-02 (SPEC_UX A-02, UX3-01).
 *
 * L'utilisateur tape ce qu'il a sous les yeux — un numéro de bon, un numéro de
 * bon d'entrée, un numéro de série, ou simplement un nom de fournisseur — et la
 * liste doit répondre sans qu'il ait à choisir un « type de recherche ». Le
 * format est donc reconnu ici, une fois, plutôt que d'exiger de l'utilisateur
 * qu'il connaisse nos conventions internes.
 *
 * Portabilité : uniquement `LIKE` sur des colonnes passées en minuscules
 * (jamais ILIKE ni `~`), pour que la même requête s'exécute sur SQLite et sur
 * PostgreSQL.
 */
class RechercheBonCommande
{
    /** Aucun format particulier reconnu : recherche en texte libre. */
    public const FORMAT_LIBRE = 'LIBRE';

    /** « BC-2026-0041 » — numéro de bon de commande. */
    public const FORMAT_BON_COMMANDE = 'BON_COMMANDE';

    /** « Brouillon #12 » ou « #12 » — désignation d'un brouillon sans numéro. */
    public const FORMAT_BROUILLON = 'BROUILLON';

    /** « ENT-2026-0034 » — bon d'entrée Stock, résolu via le lien de réception. */
    public const FORMAT_ENTREE = 'ENTREE';

    /**
     * Applique la recherche à la requête et rend le format reconnu, pour que
     * l'appelant puisse en informer l'utilisateur (« recherche par n° de bon
     * d'entrée ») plutôt que de lui laisser un tableau vide inexpliqué.
     *
     * @return array{format: string, terme: string, message: ?string}
     */
    public function appliquer(Builder $query, ?string $terme): array
    {
        $terme = trim((string) $terme);

        if ($terme === '') {
            return ['format' => self::FORMAT_LIBRE, 'terme' => '', 'message' => null];
        }

        return match ($this->reconnaitre($terme)) {
            self::FORMAT_BON_COMMANDE => $this->parNumeroDeBon($query, $terme),
            self::FORMAT_BROUILLON => $this->parIdentifiantDeBrouillon($query, $terme),
            self::FORMAT_ENTREE => $this->parNumeroDeBonEntree($query, $terme),
            default => $this->enTexteLibre($query, $terme),
        };
    }

    /**
     * Reconnaissance du format à partir de sa forme. Les préfixes sont ceux
     * effectivement produits par les deux modules (« BC- » ici, « ENT- » au
     * Stock) ; tout le reste est du texte libre.
     */
    public function reconnaitre(string $terme): string
    {
        $terme = mb_strtoupper(trim($terme));

        if (preg_match('/^BC-\d{4}-\d+$/', $terme) === 1) {
            return self::FORMAT_BON_COMMANDE;
        }

        if (preg_match('/^ENT-\d{4}-\d+$/', $terme) === 1) {
            return self::FORMAT_ENTREE;
        }

        // « Brouillon #12 », « brouillon 12 » ou « #12 » : c'est ce que la
        // colonne Numéro affiche pour un bon sans numéro, l'utilisateur le
        // recopie tel quel.
        if (preg_match('/^(BROUILLON\s*#?\s*|#)(\d+)$/', $terme) === 1) {
            return self::FORMAT_BROUILLON;
        }

        return self::FORMAT_LIBRE;
    }

    /** @return array{format: string, terme: string, message: ?string} */
    private function parNumeroDeBon(Builder $query, string $terme): array
    {
        $query->whereRaw('LOWER(achat_bons_commande.numero) = ?', [mb_strtolower($terme)]);

        return [
            'format' => self::FORMAT_BON_COMMANDE,
            'terme' => $terme,
            'message' => null,
        ];
    }

    /** @return array{format: string, terme: string, message: ?string} */
    private function parIdentifiantDeBrouillon(Builder $query, string $terme): array
    {
        preg_match('/(\d+)$/', $terme, $correspondance);

        $query->where('achat_bons_commande.id', (int) $correspondance[1]);

        return [
            'format' => self::FORMAT_BROUILLON,
            'terme' => $terme,
            'message' => null,
        ];
    }

    /**
     * Résolution d'un numéro de bon d'entrée vers le bon de commande livré.
     *
     * Le raccordement PRQ-05 a posé `stock_entrees.bon_commande_id` : le lien
     * existe réellement, la recherche le suit plutôt que de renvoyer un
     * message d'attente. Requête directe sur la table du Stock, sans passer
     * par son modèle : une liste ne doit pas dépendre du chargement d'un autre
     * module pour afficher ses lignes.
     *
     * @return array{format: string, terme: string, message: ?string}
     */
    private function parNumeroDeBonEntree(Builder $query, string $terme): array
    {
        $bonsLies = DB::table('stock_entrees')
            ->whereRaw('LOWER(numero) = ?', [mb_strtolower($terme)])
            ->whereNotNull('bon_commande_id')
            ->pluck('bon_commande_id');

        $query->whereIn('achat_bons_commande.id', $bonsLies->all());

        return [
            'format' => self::FORMAT_ENTREE,
            'terme' => $terme,
            'message' => $bonsLies->isEmpty()
                ? "Aucun bon de commande n'est lié au bon d'entrée {$terme}."
                : "Bon(s) de commande livré(s) par le bon d'entrée {$terme}.",
        ];
    }

    /**
     * Texte libre : numéro, fournisseur (référence ou libellé photographié),
     * référence de demande, et désignation des articles commandés.
     *
     * @return array{format: string, terme: string, message: ?string}
     */
    private function enTexteLibre(Builder $query, string $terme): array
    {
        $motif = '%'.mb_strtolower($terme).'%';

        $query->where(function (Builder $recherche) use ($motif) {
            $recherche
                ->whereRaw('LOWER(achat_bons_commande.numero) LIKE ?', [$motif])
                ->orWhereRaw('LOWER(achat_bons_commande.fournisseur_libelle) LIKE ?', [$motif])
                ->orWhereRaw('LOWER(achat_bons_commande.reference_demande) LIKE ?', [$motif])
                ->orWhereHas('fournisseur', fn (Builder $fournisseur) => $fournisseur->whereRaw('LOWER(raison_sociale) LIKE ?', [$motif]))
                ->orWhereHas('lignes', fn (Builder $ligne) => $ligne->whereRaw('LOWER(designation) LIKE ?', [$motif]));
        });

        return [
            'format' => self::FORMAT_LIBRE,
            'terme' => $terme,
            'message' => null,
        ];
    }
}
