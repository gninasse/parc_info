<?php

namespace Modules\Achat\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Events\BonCommandeAnnule;
use Modules\Achat\Events\BonCommandeValide;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Parametre;

class BonCommandeService
{
    /**
     * Crée un nouveau Bon de Commande avec ses lignes.
     */
    public function creer(array $data, array $lignes): BonCommande
    {
        return DB::transaction(function () use ($data, $lignes) {
            $data['numero_commande'] = $this->genererNumeroCommande();
            $data['statut'] = 'brouillon';
            $data['montant_total'] = 0;

            $bc = BonCommande::create($data);

            foreach ($lignes as $ligne) {
                $bc->lignesCommande()->create([
                    'article_id' => $ligne['article_id'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'quantite_livree' => 0,
                ]);
            }

            $bc->recalculerMontantTotal();

            return $bc;
        });
    }

    /**
     * Modifie un Bon de Commande en brouillon.
     *
     * @throws Exception
     */
    public function modifier(BonCommande $bc, array $data, array $lignes): BonCommande
    {
        if (! $bc->estModifiable()) {
            throw new Exception("Ce bon de commande ne peut plus être modifié car il n'est plus en statut brouillon.");
        }

        return DB::transaction(function () use ($bc, $data, $lignes) {
            $bc->update($data);

            // Supprimer les anciennes lignes et récréer les nouvelles
            $bc->lignesCommande()->delete();

            foreach ($lignes as $ligne) {
                $bc->lignesCommande()->create([
                    'article_id' => $ligne['article_id'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'quantite_livree' => 0,
                ]);
            }

            $bc->recalculerMontantTotal();

            return $bc;
        });
    }

    /**
     * Valide le bon de commande.
     *
     * @throws Exception
     */
    public function valider(BonCommande $bc, int $userId): void
    {
        if (! $bc->estValidable()) {
            throw new Exception("Ce bon de commande ne peut pas être validé (soit il n'est pas en brouillon, soit il n'a pas de lignes).");
        }

        $bc->update([
            'statut' => 'valide',
            'valide_par' => $userId,
            'date_validation' => now(),
        ]);

        event(new BonCommandeValide($bc));
    }

    /**
     * Annule le bon de commande.
     *
     * @throws Exception
     */
    public function annuler(BonCommande $bc): void
    {
        if (! $bc->estAnnulable()) {
            throw new Exception('Ce bon de commande ne peut pas être annulé.');
        }

        $bc->update(['statut' => 'annule']);

        event(new BonCommandeAnnule($bc));
    }

    /**
     * Génère le numéro séquentiel unique du Bon de Commande (BC-YYYY-XXXX).
     */
    protected function genererNumeroCommande(): string
    {
        $prefix = Parametre::getVal('prefix_bon_commande', 'BC');
        $annee = date('Y');

        $count = BonCommande::whereYear('date_commande', $annee)->count();
        $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        $numero = "{$prefix}-{$annee}-{$sequence}";

        // Gérer le cas improbable de doublon
        while (BonCommande::where('numero_commande', $numero)->exists()) {
            $count++;
            $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $numero = "{$prefix}-{$annee}-{$sequence}";
        }

        return $numero;
    }
}
