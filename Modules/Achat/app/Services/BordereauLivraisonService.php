<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\Parametre;

class BordereauLivraisonService
{
    /**
     * RG-BL-01 / RG-BL-02 — Réception rattachée à un bon livrable.
     *
     * @throws RegleMetierException
     */
    public function creer(array $donnees, array $lignes): BordereauLivraison
    {
        $bonCommande = BonCommande::find($donnees['bon_de_commande_id']);

        if (! $bonCommande) {
            throw new RegleMetierException("Le bon de commande sélectionné n'existe pas.");
        }

        if (! $bonCommande->accepteLivraison()) {
            throw new RegleMetierException(
                "Impossible de créer une livraison pour un bon de commande en statut : {$bonCommande->statut_label}."
            );
        }

        return DB::transaction(function () use ($donnees, $lignes, $bonCommande) {
            $bordereau = new BordereauLivraison;

            $donnees['numero_livraison'] = $bordereau->genererNumero(
                'bordereau_livraison',
                Parametre::getVal('prefix_bordereau_livraison', config('achat.prefix_bordereau_livraison', 'BL'))
            );
            $donnees['statut'] = 'brouillon';
            $donnees['ref_bordereau_physique'] = mb_strtoupper(trim($donnees['ref_bordereau_physique']));

            $bordereau = BordereauLivraison::create($donnees);

            $this->remplacerLignes($bordereau, $bonCommande, $lignes);

            activity()->performedOn($bordereau)
                ->log("Bordereau de livraison {$bordereau->numero_livraison} créé");

            return $bordereau->refresh();
        });
    }

    /**
     * RG-BL-06 — Seul un brouillon est modifiable.
     * EF-BL-14 — Le bon de commande de rattachement n'est jamais modifiable.
     *
     * @throws RegleMetierException
     */
    public function modifier(BordereauLivraison $bordereau, array $donnees, array $lignes): BordereauLivraison
    {
        if (! $bordereau->estModifiable()) {
            throw new RegleMetierException(
                "Ce bordereau de livraison ne peut plus être modifié car il n'est plus en brouillon."
            );
        }

        // Le rattachement est définitif : toute valeur transmise est ignorée.
        unset($donnees['bon_de_commande_id']);

        $bonCommande = $bordereau->bonCommande;

        return DB::transaction(function () use ($bordereau, $bonCommande, $donnees, $lignes) {
            if (isset($donnees['ref_bordereau_physique'])) {
                $donnees['ref_bordereau_physique'] = mb_strtoupper(trim($donnees['ref_bordereau_physique']));
            }

            $bordereau->update($donnees);

            $this->remplacerLignes($bordereau, $bonCommande, $lignes);

            activity()->performedOn($bordereau)
                ->log("Bordereau de livraison {$bordereau->numero_livraison} modifié");

            return $bordereau->refresh();
        });
    }

    /**
     * RG-BL-06 — Suppression réservée aux brouillons.
     *
     * @throws RegleMetierException
     */
    public function supprimer(BordereauLivraison $bordereau): void
    {
        if (! $bordereau->estSupprimable()) {
            throw new RegleMetierException(
                "Ce bordereau de livraison ne peut pas être supprimé car il n'est plus en statut brouillon."
            );
        }

        DB::transaction(function () use ($bordereau) {
            $numero = $bordereau->numero_livraison;
            $bordereau->wizardData()->delete();
            $bordereau->delete();

            activity()->log("Bordereau de livraison {$numero} supprimé");
        });
    }

    /**
     * EF-BL-12 — Retour au brouillon depuis l'assistant, tant qu'aucune
     * intégration n'a été finalisée. Corrige l'irréversibilité d'une ouverture
     * accidentelle de l'assistant.
     *
     * @throws RegleMetierException
     */
    public function revenirEnBrouillon(BordereauLivraison $bordereau): BordereauLivraison
    {
        if (! $bordereau->peutRevenirEnBrouillon()) {
            throw new RegleMetierException(
                $bordereau->estValide()
                    ? 'Ce bordereau est validé : son intégration au parc est définitive.'
                    : 'Ce bordereau est déjà en brouillon.'
            );
        }

        return DB::transaction(function () use ($bordereau) {
            // Les saisies d'inventaire non finalisées sont abandonnées.
            $bordereau->wizardData()->delete();
            $bordereau->update(['statut' => 'brouillon']);

            activity()->performedOn($bordereau)
                ->log("Bordereau {$bordereau->numero_livraison} ramené en brouillon");

            return $bordereau->refresh();
        });
    }

    /**
     * Lignes livrables d'un bon de commande, avec leur reste à livrer.
     *
     * @param  BordereauLivraison|null  $bordereauCourant  Bordereau en cours d'édition,
     *                                                     dont les quantités déjà saisies sont réintégrées au plafond.
     */
    public function lignesALivrer(BonCommande $bonCommande, ?BordereauLivraison $bordereauCourant = null): array
    {
        $dejaSaisies = [];

        if ($bordereauCourant && $bordereauCourant->exists) {
            $dejaSaisies = $bordereauCourant->lignesLivraison()
                ->pluck('quantite_livree', 'article_id')
                ->toArray();
        }

        return $bonCommande->lignesCommande()
            ->with('article')
            ->get()
            ->map(function (LigneCommande $ligne) use ($dejaSaisies) {
                $dejaSaisie = (int) ($dejaSaisies[$ligne->article_id] ?? 0);

                return [
                    'article_id' => $ligne->article_id,
                    'code_article' => $ligne->article->code_article,
                    'designation' => $ligne->article->designation,
                    'type_article' => $ligne->article->type_article,
                    'type_label' => $ligne->article->type_label,
                    'quantite_commandee' => $ligne->quantite,
                    'quantite_livree' => $ligne->quantite_livree,
                    'reste_a_livrer' => $ligne->reste_a_livrer,
                    // Plafond de saisie : le reste augmenté de ce que ce bordereau
                    // avait déjà consommé (RG-BL-03).
                    'max_qty' => $ligne->reste_a_livrer + $dejaSaisie,
                    'quantite_saisie' => $dejaSaisie,
                    'prix_unitaire' => (float) $ligne->prix_unitaire,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * RG-BL-03 / RG-BL-05 / RG-BL-07 — Contrôle et enregistrement des lignes.
     *
     * @throws RegleMetierException
     */
    protected function remplacerLignes(BordereauLivraison $bordereau, BonCommande $bonCommande, array $lignes): void
    {
        // Quantités déjà portées par ce bordereau, à réintégrer au plafond.
        $dejaSaisies = $bordereau->lignesLivraison()
            ->pluck('quantite_livree', 'article_id')
            ->toArray();

        $bordereau->lignesLivraison()->delete();

        $articlesVus = [];
        $lignesRetenues = 0;

        foreach ($lignes as $ligne) {
            $articleId = (int) $ligne['article_id'];
            $quantite = (int) $ligne['quantite_livree'];

            if ($quantite <= 0) {
                continue; // Ligne non concernée par cette réception
            }

            if (in_array($articleId, $articlesVus, true)) {
                throw new RegleMetierException(
                    'Un même article ne peut figurer qu\'une seule fois dans un bordereau de livraison.'
                );
            }

            $articlesVus[] = $articleId;

            $ligneCommande = LigneCommande::where('bon_de_commande_id', $bonCommande->id)
                ->where('article_id', $articleId)
                ->first();

            // RG-BL-05
            if (! $ligneCommande) {
                throw new RegleMetierException(
                    "L'article livré ne figure pas dans le bon de commande {$bonCommande->numero_commande}."
                );
            }

            // RG-BL-03 / RGC-02
            $plafond = $ligneCommande->reste_a_livrer + (int) ($dejaSaisies[$articleId] ?? 0);

            if ($quantite > $plafond) {
                throw new RegleMetierException(
                    "La quantité livrée ({$quantite}) dépasse le reste à livrer ({$plafond}) ".
                    "pour l'article « {$ligneCommande->article->designation} »."
                );
            }

            $bordereau->lignesLivraison()->create([
                'article_id' => $articleId,
                'quantite_livree' => $quantite,
                'quantite_refusee' => (int) ($ligne['quantite_refusee'] ?? 0),
                'motif_refus' => $ligne['motif_refus'] ?? null,
            ]);

            $lignesRetenues++;
        }

        // RG-BL-07
        if ($lignesRetenues === 0) {
            throw new RegleMetierException(
                'Le bordereau de livraison doit comporter au moins une ligne avec une quantité reçue supérieure à 0.'
            );
        }
    }
}
