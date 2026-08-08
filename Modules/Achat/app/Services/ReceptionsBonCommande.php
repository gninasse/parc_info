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

        return IntegrationReception::query()
            ->where('bon_commande_id', $bon->id)
            ->with('createur:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (IntegrationReception $integration) use ($entrees) {
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
                ];
            });
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
                    'stock_magasins.libelle AS magasin',
                ])
                ->keyBy('id');
        } catch (\Throwable) {
            return collect();
        }
    }
}
