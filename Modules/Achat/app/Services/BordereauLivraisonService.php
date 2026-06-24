<?php

namespace Modules\Achat\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\Parametre;

class BordereauLivraisonService
{
    /**
     * Crée un nouveau bordereau de livraison.
     *
     * @throws Exception
     */
    public function creer(array $data, array $lignes): BordereauLivraison
    {
        $bc = BonCommande::findOrFail($data['bon_de_commande_id']);

        // R-BL-02 : Le BC doit être valide ou partiel
        if (! in_array($bc->statut, ['valide', 'partiel'])) {
            throw new Exception("Impossible de créer une livraison pour un bon de commande en statut : {$bc->statut}.");
        }

        // R-BL-04 : Unicité du bordereau physique
        $existsPhysique = BordereauLivraison::where('ref_bordereau_physique', $data['ref_bordereau_physique'])->exists();
        if ($existsPhysique) {
            throw new Exception("Un bordereau de livraison physique avec la référence '{$data['ref_bordereau_physique']}' existe déjà.");
        }

        return DB::transaction(function () use ($data, $lignes, $bc) {
            $data['numero_livraison'] = $this->genererNumeroLivraison();
            $data['statut'] = 'brouillon';

            $bl = BordereauLivraison::create($data);

            foreach ($lignes as $ligne) {
                // R-BL-03 : La quantité livrée d'une ligne ne peut excéder la quantité restante à livrer du BC
                $ligneCommande = LigneCommande::where('bon_de_commande_id', $bc->id)
                    ->where('article_id', $ligne['article_id'])
                    ->first();

                if (! $ligneCommande) {
                    throw new Exception("L'article ID {$ligne['article_id']} n'existe pas dans le bon de commande.");
                }

                $resteALivrer = $ligneCommande->reste_a_livrer;
                if ((int) $ligne['quantite_livree'] > $resteALivrer) {
                    throw new Exception("La quantité livrée ({$ligne['quantite_livree']}) dépasse le reste à livrer ({$resteALivrer}) pour l'article '{$ligneCommande->article->designation}'.");
                }

                $bl->lignesLivraison()->create([
                    'article_id' => $ligne['article_id'],
                    'quantite_livree' => $ligne['quantite_livree'],
                ]);
            }

            return $bl;
        });
    }

    /**
     * Modifie un bordereau de livraison en brouillon.
     *
     * @throws Exception
     */
    public function modifier(BordereauLivraison $bl, array $data, array $lignes): BordereauLivraison
    {
        if (! $bl->estModifiable()) {
            throw new Exception("Ce bordereau de livraison ne peut plus être modifié car il n'est plus en brouillon.");
        }

        // R-BL-04 : Unicité de la référence physique
        if (isset($data['ref_bordereau_physique']) && $data['ref_bordereau_physique'] !== $bl->ref_bordereau_physique) {
            $existsPhysique = BordereauLivraison::where('ref_bordereau_physique', $data['ref_bordereau_physique'])->exists();
            if ($existsPhysique) {
                throw new Exception("Un bordereau de livraison physique avec la référence '{$data['ref_bordereau_physique']}' existe déjà.");
            }
        }

        return DB::transaction(function () use ($bl, $data, $lignes) {
            $bl->update($data);
            $bl->lignesLivraison()->delete();

            $bc = BonCommande::find($bl->bon_de_commande_id);

            foreach ($lignes as $ligne) {
                $ligneCommande = LigneCommande::where('bon_de_commande_id', $bc->id)
                    ->where('article_id', $ligne['article_id'])
                    ->first();

                if (! $ligneCommande) {
                    throw new Exception("L'article ID {$ligne['article_id']} n'existe pas dans le bon de commande.");
                }

                $resteALivrer = $ligneCommande->reste_a_livrer;
                if ((int) $ligne['quantite_livree'] > $resteALivrer) {
                    throw new Exception("La quantité livrée ({$ligne['quantite_livree']}) dépasse le reste à livrer ({$resteALivrer}) pour l'article '{$ligneCommande->article->designation}'.");
                }

                $bl->lignesLivraison()->create([
                    'article_id' => $ligne['article_id'],
                    'quantite_livree' => $ligne['quantite_livree'],
                ]);
            }

            return $bl;
        });
    }

    /**
     * Génère le numéro séquentiel unique du Bordereau de Livraison (BL-YYYY-XXXX).
     */
    protected function genererNumeroLivraison(): string
    {
        $prefix = Parametre::getVal('prefix_bordereau_livraison', 'BL');
        $annee = date('Y');

        $count = BordereauLivraison::whereYear('date_livraison', $annee)->count();
        $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        $numero = "{$prefix}-{$annee}-{$sequence}";

        // Doublons exceptionnels
        while (BordereauLivraison::where('numero_livraison', $numero)->exists()) {
            $count++;
            $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $numero = "{$prefix}-{$annee}-{$sequence}";
        }

        return $numero;
    }
}
