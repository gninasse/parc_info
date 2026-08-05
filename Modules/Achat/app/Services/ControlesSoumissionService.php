<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Collection;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Catalogue\Models\Article;

/**
 * Contrôles de complétude avant soumission (SFD §7.1) et signaux du
 * récapitulatif de l'étape ② (SPEC_UX A-03).
 *
 * Deux natures de constats, à ne pas confondre :
 *
 *   - les BLOCAGES : le bon ne peut pas partir au visa en l'état (aucune
 *     ligne, prix nul, licence sans logiciel rattaché) ;
 *   - les AVERTISSEMENTS : le bon peut partir, mais quelque chose mérite un
 *     regard (écart de prix au-delà du seuil). Ils n'empêchent jamais rien —
 *     un écart de prix peut être parfaitement justifié, et c'est au
 *     validateur d'en juger, pas au logiciel.
 *
 * Ce service est appelé DEUX fois : par l'écran, pour afficher les encarts et
 * griser le bouton avec son diagnostic ; et par le serveur au moment de la
 * soumission, qui ne fait jamais confiance à ce que l'écran a conclu.
 */
class ControlesSoumissionService
{
    public function __construct(
        private readonly ReferencePrixService $referencePrix,
        private readonly AchatParametres $parametres,
    ) {}

    /**
     * Diagnostic complet d'un bon.
     *
     * @return array{
     *   soumettable: bool,
     *   blocages: list<array{code: string, message: string, lignes: list<int>}>,
     *   avertissements: list<array{code: string, message: string, details: list<array<string, mixed>>}>,
     *   diagnostic_bouton: ?string
     * }
     */
    public function diagnostiquer(BonCommande $bon): array
    {
        $lignes = $bon->lignes()->get();

        $blocages = $this->blocages($bon, $lignes);
        $avertissements = $this->avertissements($bon, $lignes);

        return [
            'soumettable' => $blocages === [],
            'blocages' => $blocages,
            'avertissements' => $avertissements,
            // Texte porté par le bouton grisé (SPEC_UX §0.3) : dire ce qui
            // manque, pas seulement que c'est impossible.
            'diagnostic_bouton' => $blocages === [] ? null : $blocages[0]['message'],
        ];
    }

    /**
     * @param  Collection<int, LigneCommande>  $lignes
     * @return list<array{code: string, message: string, lignes: list<int>}>
     */
    private function blocages(BonCommande $bon, Collection $lignes): array
    {
        $blocages = [];

        if ($lignes->isEmpty()) {
            $blocages[] = [
                'code' => 'aucune_ligne',
                'message' => 'Ajoutez au moins une ligne avant de soumettre.',
                'lignes' => [],
            ];

            // Sans ligne, les contrôles suivants n'ont rien à examiner.
            return $blocages;
        }

        $sansPrix = $lignes->filter(fn (LigneCommande $ligne) => (float) $ligne->prix_unitaire_ht <= 0);

        if ($sansPrix->isNotEmpty()) {
            $blocages[] = [
                'code' => 'prix_nul',
                'message' => $sansPrix->count() === 1
                    ? 'Une ligne est sans prix : indiquez le prix négocié.'
                    : "{$sansPrix->count()} lignes sont sans prix : indiquez le prix négocié.",
                'lignes' => $sansPrix->pluck('id')->all(),
            ];
        }

        /*
         * Licence sans logiciel rattaché (garde C11) : contrôlé ICI, à la
         * soumission, et non seulement à l'ouverture du wizard de réception.
         * Laisser passer un tel bon, c'est le découvrir des semaines plus tard
         * quand les licences arrivent et qu'on ne peut plus rien créer.
         */
        $licencesOrphelines = $this->licencesSansLogiciel($lignes);

        if ($licencesOrphelines->isNotEmpty()) {
            $codes = $licencesOrphelines->pluck('designation')->implode(', ');

            $blocages[] = [
                'code' => 'licence_sans_logiciel',
                'message' => $licencesOrphelines->count() === 1
                    ? "La ligne licence « {$codes} » n'a pas de logiciel rattaché au Catalogue : la réception serait impossible."
                    : "{$licencesOrphelines->count()} lignes licence n'ont pas de logiciel rattaché au Catalogue : la réception serait impossible.",
                'lignes' => $licencesOrphelines->pluck('id')->all(),
            ];
        }

        return $blocages;
    }

    /**
     * @param  Collection<int, LigneCommande>  $lignes
     * @return list<array{code: string, message: string, details: list<array<string, mixed>>}>
     */
    private function avertissements(BonCommande $bon, Collection $lignes): array
    {
        $avertissements = [];
        $ecarts = [];

        foreach ($lignes as $index => $ligne) {
            if ($ligne->article_id === null) {
                continue;
            }

            $ecart = $this->referencePrix->ecart($ligne->article_id, (float) $ligne->prix_unitaire_ht);

            if (! $ecart['depasse_seuil']) {
                continue;
            }

            // Dépliable ligne à ligne (SPEC_UX A-03) : un encart qui annonce
            // « 2 lignes s'écartent » sans dire lesquelles n'aide personne.
            $ecarts[] = [
                'ligne_id' => $ligne->id,
                'numero' => $index + 1,
                'designation' => $ligne->designation,
                'prix' => (float) $ligne->prix_unitaire_ht,
                'reference' => $ecart['reference'],
                'ecart_pct' => $ecart['ecart_pct'],
            ];
        }

        if ($ecarts !== []) {
            $seuil = $this->parametres->seuilEcartPrixPct();

            $avertissements[] = [
                'code' => 'ecart_prix',
                'message' => count($ecarts) === 1
                    ? "1 ligne s'écarte de plus de {$seuil} % du dernier prix payé."
                    : count($ecarts)." lignes s'écartent de plus de {$seuil} % du dernier prix payé.",
                'details' => $ecarts,
            ];
        }

        if ($bon->est_regularisation) {
            $avertissements[] = [
                'code' => 'regularisation',
                'message' => 'Ce bon documentera une acquisition passée ; il sera exclu des statistiques de dépense.',
                'details' => [],
            ];
        }

        return $avertissements;
    }

    /**
     * Lignes de nature licence dont l'article n'a pas de logiciel rattaché.
     *
     * La nature est lue sur la LIGNE (valeur figée), mais le logiciel est lu
     * sur l'article vivant : c'est bien l'état actuel du Catalogue qui
     * déterminera si la réception sera possible.
     *
     * @param  Collection<int, LigneCommande>  $lignes
     * @return Collection<int, LigneCommande>
     */
    private function licencesSansLogiciel(Collection $lignes): Collection
    {
        $lignesLicence = $lignes->where('nature', Article::NATURE_LICENCE);

        if ($lignesLicence->isEmpty()) {
            return collect();
        }

        $articlesAvecLogiciel = Article::query()
            ->whereIn('id', $lignesLicence->pluck('article_id')->filter()->all())
            ->whereNotNull('logiciel_id')
            ->pluck('id')
            ->all();

        return $lignesLicence->reject(
            fn (LigneCommande $ligne) => in_array($ligne->article_id, $articlesAvecLogiciel, true)
        )->values();
    }
}
