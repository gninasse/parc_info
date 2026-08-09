<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Models\BonCommande;

/**
 * BR-03 — le dossier documentaire de la livraison, vu depuis Achat.
 *
 * L'acheteur qui conteste une facture a besoin du BL signé et du bordereau de
 * réception. Jusqu'ici il devait ouvrir le module Stock, donc en avoir les
 * droits : dans les faits, il téléphonait au magasin. Ce service lui rend les
 * pièces de SES commandes sans lui donner le Stock.
 *
 * Trois règles tiennent tout :
 *
 *   1. LECTURE DIRECTE des tables Stock (`stock_documents`, `stock_entrees`),
 *      sans passer par les modèles de l'autre module — même doctrine que
 *      ReceptionsBonCommande : la fiche d'un bon ne doit pas dépendre du
 *      chargement d'un module voisin pour afficher SES données ;
 *   2. RATTACHEMENT VÉRIFIÉ (IA-16) : une pièce n'est servie que si elle
 *      appartient à une entrée elle-même LIÉE à ce bon de commande. L'identifiant
 *      d'un document ne suffit jamais — sinon la route proxy deviendrait un
 *      moyen de lire les documents de n'importe quelle entrée ;
 *   3. DÉGRADATION PARTIELLE : Stock absent ou table manquante, on rend une
 *      collection vide et la fiche vit. Jamais d'erreur 500 sur la fiche d'un
 *      bon parce que le magasin est en maintenance.
 *
 * Aucune URL Stock n'est exposée : les liens rendus pointent tous vers les
 * routes proxy d'Achat, qui contrôlent `achat.documents.view`.
 */
class DocumentsReception
{
    /** Libellés des natures de pièce, côté Achat (le module Stock en est la source). */
    public const TYPES = [
        'bl_fournisseur' => 'Bordereau du fournisseur',
        'photo_livraison' => 'Photo de la livraison',
        'autre' => 'Autre pièce',
    ];

    /**
     * Les pièces jointes des entrées liées à ce bon, indexées par entrée.
     *
     * Les pierres tombales sont RENDUES (une pièce retirée d'un dossier
     * engagé doit rester visible), mais sans lien de téléchargement.
     *
     * @return Collection<int, Collection<int, array>>
     */
    public function parEntree(BonCommande $bon): Collection
    {
        try {
            if (! Schema::hasTable('stock_documents') || ! Schema::hasTable('stock_entrees')) {
                return collect();
            }

            $entrees = DB::table('stock_entrees')
                ->where('bon_commande_id', $bon->id)
                ->pluck('id');

            if ($entrees->isEmpty()) {
                return collect();
            }

            return DB::table('stock_documents')
                ->whereIn('documentable_id', $entrees->all())
                ->where('documentable_type', 'Modules\Stock\Models\Entree')
                ->orderBy('id')
                ->get(['id', 'documentable_id', 'type', 'nom_original', 'taille', 'est_supprime', 'created_at'])
                ->groupBy('documentable_id')
                ->map(fn (Collection $pieces, $entreeId) => $pieces->map(
                    fn ($piece) => $this->presenter($piece, $bon, (int) $entreeId)
                )->values());
        } catch (\Throwable $e) {
            Log::warning('Lecture des documents Stock impossible pour l\'onglet Réceptions', [
                'bon_commande_id' => $bon->id,
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * La pièce demandée, SI elle appartient bien à une entrée de ce bon.
     *
     * C'est le contrôle d'IA-16 : il est fait ici, une seule fois, pour que la
     * route proxy ne puisse pas l'oublier.
     *
     * @return object|null l'enregistrement brut, ou null si le rattachement
     *                     n'est pas établi (traité en 404 par l'appelant)
     */
    public function pieceDuBon(BonCommande $bon, int $entreeId, int $documentId): ?object
    {
        if (! Schema::hasTable('stock_documents') || ! Schema::hasTable('stock_entrees')) {
            return null;
        }

        $appartient = DB::table('stock_entrees')
            ->where('id', $entreeId)
            ->where('bon_commande_id', $bon->id)
            ->exists();

        if (! $appartient) {
            return null;
        }

        return DB::table('stock_documents')
            ->where('id', $documentId)
            ->where('documentable_id', $entreeId)
            ->where('documentable_type', 'Modules\Stock\Models\Entree')
            ->first();
    }

    /** L'entrée VALIDÉE de ce bon, pour le bordereau (null sinon). */
    public function entreeValideeDuBon(BonCommande $bon, int $entreeId): ?object
    {
        if (! Schema::hasTable('stock_entrees')) {
            return null;
        }

        return DB::table('stock_entrees')
            ->where('id', $entreeId)
            ->where('bon_commande_id', $bon->id)
            ->where('statut', 'VALIDE')
            ->first(['id', 'numero']);
    }

    /** Y a-t-il un BL fournisseur vivant sur cette entrée ? (indicateur informatif) */
    public function aUnBlFournisseur(int $entreeId): bool
    {
        try {
            if (! Schema::hasTable('stock_documents')) {
                return false;
            }

            return DB::table('stock_documents')
                ->where('documentable_id', $entreeId)
                ->where('documentable_type', 'Modules\Stock\Models\Entree')
                ->where('type', 'bl_fournisseur')
                ->where('est_supprime', false)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Une pièce telle que la fiche l'affiche — liens proxy compris. */
    private function presenter(object $piece, BonCommande $bon, int $entreeId): array
    {
        $supprimee = (bool) $piece->est_supprime;
        $peutVoir = auth()->user()?->can('achat.documents.view') ?? false;

        return [
            'id' => (int) $piece->id,
            'type' => $piece->type,
            'type_label' => self::TYPES[$piece->type] ?? 'Pièce',
            'nom_original' => $piece->nom_original,
            'taille_lisible' => $this->tailleLisible($piece->taille),
            'est_supprime' => $supprimee,
            'est_bl' => $piece->type === 'bl_fournisseur',
            // Drapeau SERVEUR : l'écran n'infère aucun droit.
            'peut_telecharger' => ! $supprimee && $peutVoir,
            // Route ACHAT, jamais l'URL Stock du fichier.
            'url_telechargement' => $supprimee || ! $peutVoir
                ? null
                : route('achat.bons-commande.receptions.documents', [$bon->id, $entreeId, $piece->id]),
        ];
    }

    private function tailleLisible(?int $octets): string
    {
        if ($octets === null) {
            return '—';
        }

        return $octets >= 1048576
            ? number_format($octets / 1048576, 1, ',', ' ').' Mo'
            : max(1, (int) round($octets / 1024)).' Ko';
    }
}
