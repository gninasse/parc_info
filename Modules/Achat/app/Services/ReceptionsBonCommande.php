<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;

/**
 * L'onglet Réceptions de la fiche A-04 (SPEC_UX A-04, maquette P-03).
 *
 * Deux sections aux statuts épistémiques différents :
 *
 *   - « INTÉGRÉES » : les compteurs d'ACHAT font foi — ce sont les traces
 *     d'intégration écrites par la transaction de validation Stock (D-12).
 *     Aucune lecture croisée : le module est source de vérité de son reste ;
 *   - « EN COURS CÔTÉ MAGASIN » : informatif — les bons d'entrée LIÉS non
 *     validés, lus chez Stock. Badge « non intégré — sans effet sur les
 *     reliquats ». Si cette lecture échoue (module coupé, table absente),
 *     la fiche se DÉGRADE PARTIELLEMENT : la section affiche son
 *     indisponibilité, les sections Achat restent entières.
 *
 * Lecture directe de `stock_entrees` sans passer par les modèles Stock :
 * même doctrine que RechercheBonCommande — une fiche ne doit pas dépendre du
 * chargement d'un autre module pour afficher SES données.
 */
class ReceptionsBonCommande
{
    /**
     * BR-04 — libellés des motifs d'écart (source : `Stock\Models\Entree`).
     * Recopiés ici pour la même raison que le reste du service : la fiche
     * d'un bon ne dépend pas du chargement du module voisin pour s'afficher.
     */
    private const MOTIFS_ECART = [
        'manquant' => 'Manquant',
        'endommage_refuse' => 'Endommagé — refusé',
        'excedent_refuse' => 'Excédent refusé',
    ];

    public function __construct(private readonly DocumentsReception $documents) {}

    /**
     * @return array{integrees: Collection, en_cours: Collection, magasin_disponible: bool}
     */
    public function pour(BonCommande $bon): array
    {
        return [
            'integrees' => $this->integrees($bon),
            ...$this->enCoursCoteMagasin($bon),
        ];
    }

    /** Les réceptions (et contre-passations) intégrées — la vérité d'Achat. */
    private function integrees(BonCommande $bon): Collection
    {
        $entrees = $this->entreesLiees($bon);

        // BR-03 : le dossier documentaire de la livraison, lu une seule fois
        // pour toutes les cartes (une requête, pas une par réception).
        $documents = $this->documents->parEntree($bon);

        return IntegrationReception::query()
            ->where('bon_commande_id', $bon->id)
            ->with('createur:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (IntegrationReception $integration) use ($entrees, $documents, $bon) {
                $entree = $integration->entree_id !== null
                    ? $entrees->get($integration->entree_id)
                    : null;

                return [
                    'sens' => $integration->sens,
                    'est_contre_passation' => $integration->sens === IntegrationReception::SENS_CONTRE_PASSATION,
                    'reference' => $integration->reference
                        ?? ($integration->entree_id !== null ? "Entrée #{$integration->entree_id}" : 'Mouvement'),
                    'quand' => $integration->created_at,
                    'par' => $integration->createur?->name,
                    'lignes' => collect($integration->detail ?? []),
                    'unites' => collect($integration->detail ?? [])->sum(fn ($l) => (float) ($l['quantite'] ?? 0)),
                    // Contexte du bon d'entrée (magasin, date, observation) —
                    // enrichissement facultatif, la trace se suffit sans lui.
                    'magasin' => $entree?->magasin,
                    'date_livraison' => $entree?->date_document,
                    'observation' => $entree?->observation,
                    'url_entree' => $entree !== null && \Illuminate\Support\Facades\Route::has('stock.entrees.show')
                        ? route('stock.entrees.show', $entree->id)
                        : null,
                    // BR-03 — les pièces de CETTE livraison et son bordereau,
                    // servis par les routes proxy d'Achat (jamais par Stock).
                    'documents' => $integration->entree_id !== null
                        ? $documents->get($integration->entree_id, collect())
                        : collect(),
                    'url_bordereau' => $this->urlBordereau($bon, $integration, $entree),
                    // BR-04 — la pilule rouge « Écart BL » et son détail. Elle
                    // DOCUMENTE : aucun compteur ne s'en trouve modifié, les
                    // reliquats ne connaîtront jamais que le compté (RGC-02).
                    'ecarts_bl' => $this->ecarts($entree),
                ];
            });
    }

    /**
     * Les écarts BL d'une entrée, prêts à l'affichage.
     *
     * Lecture défensive du JSON : la colonne peut arriver décodée (PostgreSQL
     * json) ou en chaîne (SQLite), et l'entrée peut manquer.
     *
     * @return Collection<int, array>
     */
    private function ecarts(?object $entree): Collection
    {
        $brut = $entree->ecarts_bl ?? null;

        if (is_string($brut)) {
            $brut = json_decode($brut, true);
        }

        if (! is_array($brut)) {
            return collect();
        }

        return collect($brut)
            ->filter(fn ($ligne) => is_array($ligne))
            ->map(fn (array $ligne) => [
                'designation' => $ligne['designation'] ?? 'Article',
                'annoncee' => (float) ($ligne['quantite_annoncee_bl'] ?? 0),
                'comptee' => (float) ($ligne['quantite_comptee'] ?? 0),
                'ecart' => (float) ($ligne['quantite_comptee'] ?? 0) - (float) ($ligne['quantite_annoncee_bl'] ?? 0),
                'motif' => self::MOTIFS_ECART[$ligne['motif'] ?? ''] ?? ($ligne['motif'] ?? ''),
            ])
            ->values();
    }

    /**
     * Le lien vers le bordereau de réception (BR-02), servi par le proxy.
     *
     * Il n'existe que pour une trace portant une ENTRÉE — ce qui exclut de
     * fait les contre-passations, que le schéma rattache à un mouvement et
     * jamais à une entrée (CHECK `chk_integrations_cle_selon_sens`) : elles
     * défont une livraison, elles n'en attestent pas.
     */
    private function urlBordereau(BonCommande $bon, IntegrationReception $integration, ?object $entree): ?string
    {
        if ($integration->entree_id === null || $entree === null) {
            return null;
        }

        if (! (auth()->user()?->can('achat.documents.view') ?? false)) {
            return null;
        }

        return route('achat.bons-commande.receptions.bordereau', [$bon->id, $integration->entree_id]);
    }

    /**
     * Les bons d'entrée liés NON VALIDÉS : ce que le magasin prépare.
     *
     * @return array{en_cours: Collection, magasin_disponible: bool}
     */
    private function enCoursCoteMagasin(BonCommande $bon): array
    {
        try {
            if (! Schema::hasTable('stock_entrees')) {
                return ['en_cours' => collect(), 'magasin_disponible' => false];
            }

            $enCours = DB::table('stock_entrees')
                ->leftJoin('stock_magasins', 'stock_magasins.id', '=', 'stock_entrees.magasin_id')
                ->where('stock_entrees.bon_commande_id', $bon->id)
                ->whereNotIn('stock_entrees.statut', ['VALIDE', 'ANNULE'])
                ->orderBy('stock_entrees.id')
                ->get([
                    'stock_entrees.id',
                    'stock_entrees.statut',
                    'stock_entrees.date_document',
                    'stock_entrees.created_by',
                    'stock_magasins.libelle AS magasin',
                ])
                ->map(function ($entree) {
                    $unites = (float) DB::table('stock_lignes_entrees')
                        ->where('entree_id', $entree->id)
                        ->whereNotNull('article_id')
                        ->sum('quantite');

                    return [
                        'id' => $entree->id,
                        'libelle' => "Brouillon #{$entree->id}",
                        'statut' => $entree->statut,
                        'statut_label' => $entree->statut === 'REFERENCEMENT'
                            ? 'Saisie des n° de série'
                            : 'Brouillon',
                        'magasin' => $entree->magasin,
                        'unites' => $unites,
                        'par' => DB::table('users')->where('id', $entree->created_by)->value('name'),
                        'url_entree' => \Illuminate\Support\Facades\Route::has('stock.entrees.edit')
                            ? route('stock.entrees.edit', $entree->id)
                            : null,
                        // BR-03 : indicateur INFORMATIF — le magasin a-t-il déjà
                        // numérisé le bordereau du livreur ? Rien n'en dépend.
                        'bl_joint' => $this->documents->aUnBlFournisseur((int) $entree->id),
                    ];
                });

            return ['en_cours' => $enCours, 'magasin_disponible' => true];
        } catch (\Throwable $e) {
            // Dégradation PARTIELLE : la fiche vit, la section explique.
            Log::warning('Lecture des entrées Stock impossible pour l\'onglet Réceptions', [
                'bon_commande_id' => $bon->id,
                'exception' => $e->getMessage(),
            ]);

            return ['en_cours' => collect(), 'magasin_disponible' => false];
        }
    }

    /** Les entrées VALIDÉES liées, indexées par id — contexte des cartes. */
    private function entreesLiees(BonCommande $bon): Collection
    {
        try {
            if (! Schema::hasTable('stock_entrees')) {
                return collect();
            }

            return DB::table('stock_entrees')
                ->leftJoin('stock_magasins', 'stock_magasins.id', '=', 'stock_entrees.magasin_id')
                ->where('stock_entrees.bon_commande_id', $bon->id)
                ->get([
                    'stock_entrees.id',
                    'stock_entrees.numero',
                    'stock_entrees.date_document',
                    'stock_entrees.observation',
                    // BR-04 : le rapprochement BL ↔ saisie déclaré au quai.
                    'stock_entrees.ecarts_bl',
                    'stock_magasins.libelle AS magasin',
                ])
                ->keyBy('id');
        } catch (\Throwable) {
            return collect();
        }
    }
}
