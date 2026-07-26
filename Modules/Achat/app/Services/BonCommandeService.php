<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Events\BonCommandeAnnule;
use Modules\Achat\Events\BonCommandeCloture;
use Modules\Achat\Events\BonCommandeValide;
use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Parametre;

class BonCommandeService
{
    /**
     * Crée un bon de commande en brouillon avec ses lignes.
     *
     * RG-BC-01 — Statut initial brouillon, numéro attribué par séquence verrouillée.
     */
    public function creer(array $donnees, array $lignes): BonCommande
    {
        return DB::transaction(function () use ($donnees, $lignes) {
            $bonCommande = new BonCommande;

            $donnees['numero_commande'] = $bonCommande->genererNumero(
                'bon_commande',
                Parametre::getVal('prefix_bon_commande', config('achat.prefix_bon_commande', 'BC'))
            );
            $donnees['statut'] = 'brouillon';

            $bonCommande = BonCommande::create($donnees);

            $this->remplacerLignes($bonCommande, $lignes);
            $this->recalculerMontants($bonCommande);

            activity()->performedOn($bonCommande)
                ->log("Bon de commande {$bonCommande->numero_commande} créé");

            return $bonCommande->refresh();
        });
    }

    /**
     * RG-BC-03 / RGC-01 — Seul un brouillon est modifiable.
     *
     * @throws RegleMetierException
     */
    public function modifier(BonCommande $bonCommande, array $donnees, array $lignes): BonCommande
    {
        if (! $bonCommande->estModifiable()) {
            throw new RegleMetierException(
                "Ce bon de commande ne peut plus être modifié car il n'est plus en statut brouillon."
            );
        }

        return DB::transaction(function () use ($bonCommande, $donnees, $lignes) {
            $bonCommande->update($donnees);

            $this->remplacerLignes($bonCommande, $lignes);
            $this->recalculerMontants($bonCommande);

            activity()->performedOn($bonCommande)
                ->log("Bon de commande {$bonCommande->numero_commande} modifié");

            return $bonCommande->refresh();
        });
    }

    /**
     * RG-BC-04 / RG-BC-06 — Validation par un utilisateur habilité.
     *
     * @throws RegleMetierException
     */
    public function valider(BonCommande $bonCommande, int $userId): BonCommande
    {
        if ($bonCommande->statut !== 'brouillon') {
            throw new RegleMetierException(
                "Seul un bon de commande en brouillon peut être validé (statut actuel : {$bonCommande->statut_label})."
            );
        }

        if (! $bonCommande->lignesCommande()->exists()) {
            throw new RegleMetierException(
                'Ce bon de commande ne comporte aucune ligne : il ne peut pas être validé.'
            );
        }

        return DB::transaction(function () use ($bonCommande, $userId) {
            // Le montant est figé au moment de la validation.
            $this->recalculerMontants($bonCommande);

            $bonCommande->update([
                'statut' => 'valide',
                'valide_par' => $userId,
                'date_validation' => now(),
            ]);

            activity()->performedOn($bonCommande)
                ->log("Bon de commande {$bonCommande->numero_commande} validé");

            event(new BonCommandeValide($bonCommande, $userId));

            return $bonCommande->refresh();
        });
    }

    /**
     * RG-BC-05 / RGC-10 — Annulation impossible dès qu'une livraison est intégrée.
     *
     * @throws RegleMetierException
     */
    public function annuler(BonCommande $bonCommande, int $userId, string $motif): BonCommande
    {
        if (! $bonCommande->estAnnulable()) {
            throw new RegleMetierException(
                $bonCommande->bordereauxLivraison()->where('statut', 'valide')->exists()
                    ? 'Ce bon de commande a déjà fait l\'objet d\'une livraison intégrée : il ne peut plus être annulé. Utilisez la clôture de reliquat.'
                    : "Ce bon de commande ne peut pas être annulé (statut actuel : {$bonCommande->statut_label})."
            );
        }

        return DB::transaction(function () use ($bonCommande, $userId, $motif) {
            $bonCommande->update([
                'statut' => 'annule',
                'annule_par' => $userId,
                'date_annulation' => now(),
                'motif_annulation' => $motif,
            ]);

            activity()->performedOn($bonCommande)
                ->withProperties(['motif' => $motif])
                ->log("Bon de commande {$bonCommande->numero_commande} annulé");

            event(new BonCommandeAnnule($bonCommande, $userId));

            return $bonCommande->refresh();
        });
    }

    /**
     * EF-BC-18 — Clôture du reliquat d'une commande partiellement livrée et
     * abandonnée. Les livraisons déjà intégrées sont préservées.
     *
     * @throws RegleMetierException
     */
    public function cloturerReliquat(BonCommande $bonCommande, int $userId, string $motif): BonCommande
    {
        if (! $bonCommande->estCloturable()) {
            throw new RegleMetierException(
                'Seul un bon de commande partiellement livré peut voir son reliquat clôturé '.
                "(statut actuel : {$bonCommande->statut_label})."
            );
        }

        return DB::transaction(function () use ($bonCommande, $userId, $motif) {
            $bonCommande->update([
                'statut' => 'cloture',
                'cloture_par' => $userId,
                'date_cloture' => now(),
                'motif_cloture' => $motif,
            ]);

            activity()->performedOn($bonCommande)
                ->withProperties(['motif' => $motif, 'reste_a_livrer' => $bonCommande->reste_a_livrer])
                ->log("Reliquat du bon de commande {$bonCommande->numero_commande} clôturé");

            event(new BonCommandeCloture($bonCommande, $userId));

            return $bonCommande->refresh();
        });
    }

    /**
     * RG-BC-03 — Suppression réservée aux brouillons sans livraison.
     *
     * @throws RegleMetierException
     */
    public function supprimer(BonCommande $bonCommande): void
    {
        if (! $bonCommande->estSupprimable()) {
            throw new RegleMetierException(
                "Ce bon de commande ne peut pas être supprimé car il n'est plus en statut brouillon."
            );
        }

        DB::transaction(function () use ($bonCommande) {
            $numero = $bonCommande->numero_commande;
            $bonCommande->delete();

            activity()->log("Bon de commande {$numero} supprimé");
        });
    }

    /**
     * RG-BC-11 — Recalcule le statut après intégration d'une livraison.
     */
    public function actualiserStatutApresLivraison(BonCommande $bonCommande): BonCommande
    {
        $bonCommande->load('lignesCommande');

        $nouveauStatut = $bonCommande->est_entierement_livre ? 'livre' : 'partiel';

        if ($bonCommande->statut !== $nouveauStatut) {
            $bonCommande->update(['statut' => $nouveauStatut]);
        }

        return $bonCommande;
    }

    /**
     * ENF-FIA-04 — Unique point de calcul des montants d'un bon de commande.
     * RG-BC-05 : le taux de TVA appliqué est celui figé sur chaque ligne.
     */
    public function recalculerMontants(BonCommande $bonCommande): void
    {
        $bonCommande->load('lignesCommande');

        $montantHt = 0.0;
        $montantTva = 0.0;

        foreach ($bonCommande->lignesCommande as $ligne) {
            $montantHt += $ligne->montant_ht;
            $montantTva += $ligne->montant_tva;
        }

        $bonCommande->update([
            'montant_ht' => round($montantHt, 2),
            'montant_tva' => round($montantTva, 2),
            'montant_ttc' => round($montantHt + $montantTva, 2),
        ]);
    }

    /**
     * Remplace l'intégralité des lignes du bon.
     *
     * Le taux de TVA de l'article est copié sur la ligne à cet instant
     * (RG-BC-05) : une évolution ultérieure du catalogue ne modifiera pas
     * rétroactivement le montant du bon.
     *
     * @throws RegleMetierException
     */
    protected function remplacerLignes(BonCommande $bonCommande, array $lignes): void
    {
        $bonCommande->lignesCommande()->delete();

        $articlesVus = [];

        foreach ($lignes as $ligne) {
            $articleId = (int) $ligne['article_id'];

            // Contrainte unique_article_par_bc : un article n'apparaît qu'une fois
            if (in_array($articleId, $articlesVus, true)) {
                throw new RegleMetierException(
                    'Un même article ne peut figurer qu\'une seule fois dans un bon de commande. '.
                    'Regroupez les quantités sur une seule ligne.'
                );
            }

            $articlesVus[] = $articleId;

            $article = Article::find($articleId);

            if (! $article) {
                throw new RegleMetierException("L'article sélectionné (identifiant {$articleId}) n'existe pas.");
            }

            $bonCommande->lignesCommande()->create([
                'article_id' => $article->id,
                'quantite' => (int) $ligne['quantite'],
                'prix_unitaire' => (float) $ligne['prix_unitaire'],
                'taux_tva' => $ligne['taux_tva'] ?? $article->taux_tva,
                'quantite_livree' => 0,
            ]);
        }
    }
}
