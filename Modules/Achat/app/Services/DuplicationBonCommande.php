<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;

/**
 * D-23 — dupliquer un bon de commande.
 *
 * Le besoin est banal et quotidien : le trimestre de consommables ressemble
 * au précédent. Le ressaisir ligne à ligne fait perdre du temps et introduit
 * des fautes de frappe sur des références à huit caractères.
 *
 * Mais une duplication naïve — recopier les lignes à l'identique — serait
 * pire que la saisie manuelle, parce qu'elle produirait un bon qui a l'air
 * juste. D'où la règle centrale de ce service :
 *
 *     LES VALEURS SONT RE-FIGÉES AU JOUR DE LA DUPLICATION.
 *
 * C'est l'extension de IA-2. La photographie contractuelle du bon d'origine
 * n'est pas transportable : le prix du catalogue a pu changer, le taux de
 * TVA aussi, l'article a pu être désactivé, le fournisseur fermer. Recopier
 * un prix de l'an dernier dans un bon qu'on s'apprête à engager, c'est
 * commander à un prix qui n'existe plus.
 *
 * Le prix NÉGOCIÉ d'origine n'est pas perdu pour autant : il est rendu à
 * l'appelant comme point de comparaison (PO-01), pour que l'acheteur voie
 * ce qu'il avait obtenu la dernière fois.
 */
class DuplicationBonCommande
{
    /** L'événement au journal — retrouver la filiation d'un bon. */
    public const EVENEMENT = 'duplication';

    public function __construct(private readonly LignesBonCommandeService $lignes) {}

    /**
     * Duplique un bon en BROUILLON, valeurs re-figées.
     *
     * @return array{bon: BonCommande, avertissements: list<string>, comparaisons: list<array>}
     */
    public function dupliquer(BonCommande $origine, User $auteur): array
    {
        $avertissements = [];
        $comparaisons = [];

        // Le fournisseur est revérifié : un bon adressé à un fournisseur
        // désactivé ne partirait nulle part. On le conserve quand même (le
        // supprimer obligerait à tout ressaisir) mais on le signale.
        $fournisseur = Fournisseur::query()->find($origine->fournisseur_id);

        if ($fournisseur !== null && ! $fournisseur->est_actif) {
            $avertissements[] = sprintf(
                'Le fournisseur « %s » est désactivé au catalogue : vérifiez-le avant de soumettre.',
                $fournisseur->raison_sociale
            );
        }

        $copie = DB::transaction(function () use ($origine, $auteur, &$avertissements, &$comparaisons) {
            $copie = BonCommande::create([
                'statut' => BonCommande::STATUT_BROUILLON,
                // La date du JOUR, jamais celle de l'original : un bon
                // dupliqué est un nouvel engagement, pas une copie d'archive.
                'date_document' => now()->toDateString(),
                'fournisseur_id' => $origine->fournisseur_id,
                'service_demandeur_id' => $origine->service_demandeur_id,
                'created_by' => $auteur->id,
            ]);

            $charge = [];

            /*
             * L'article est TOUJOURS présent : `article_id` est NOT NULL et sa
             * clé étrangère est en `restrict`, ce qui interdit de supprimer un
             * article encore référencé par une ligne de commande. Inutile donc
             * de prévoir le cas « article disparu » — ce serait du code mort,
             * et un code mort finit par être lu comme une garantie réelle.
             */
            foreach ($origine->lignes as $ligne) {
                $article = Article::query()->findOrFail($ligne->article_id);

                if (! $article->est_actif) {
                    $avertissements[] = sprintf(
                        'L\'article « %s » est désactivé au catalogue : la ligne est reprise, à vérifier.',
                        $article->nom
                    );
                }

                // LE re-figeage : le prix proposé est celui d'AUJOURD'HUI.
                $prixPropose = $article->prix_indicatif !== null
                    ? (float) $article->prix_indicatif
                    : (float) $ligne->prix_unitaire_ht;

                $charge[] = [
                    'article_id' => $article->id,
                    'quantite' => (float) $ligne->quantite,
                    'prix_unitaire_ht' => $prixPropose,
                    // Le taux vient du catalogue à son tour : il a pu changer.
                    'taux_tva' => $article->taux_tva,
                ];

                // Ce que l'acheteur avait négocié la dernière fois : c'est
                // sa meilleure référence de négociation, il doit la voir.
                $comparaisons[] = [
                    'designation' => $article->nom,
                    'prix_precedent' => round((float) $ligne->prix_unitaire_ht, 2),
                    'prix_propose' => round($prixPropose, 2),
                    'ecart_pct' => $this->ecartPourcentage(
                        (float) $ligne->prix_unitaire_ht,
                        $prixPropose
                    ),
                ];
            }

            if ($charge !== []) {
                $this->lignes->synchroniser($copie, $charge);
            }

            activity()
                ->performedOn($copie)
                ->causedBy($auteur)
                ->withProperties([
                    'origine_id' => $origine->id,
                    'origine_numero' => $origine->numero_affiche,
                    'nb_lignes' => count($charge),
                    'avertissements' => $avertissements,
                ])
                ->tap(fn ($activite) => $activite->module = 'achat')
                ->log(self::EVENEMENT);

            return $copie->refresh();
        });

        return [
            'bon' => $copie,
            'avertissements' => $avertissements,
            'comparaisons' => $comparaisons,
        ];
    }

    /**
     * L'écart entre l'ancien prix négocié et le prix proposé aujourd'hui.
     *
     * Nul quand l'ancien prix est nul ou absent : un « +100 % » calculé sur
     * une base inexistante ne veut rien dire.
     */
    private function ecartPourcentage(float $precedent, float $propose): ?float
    {
        if ($precedent <= 0.0) {
            return null;
        }

        return round(($propose - $precedent) / $precedent * 100, 1);
    }
}
