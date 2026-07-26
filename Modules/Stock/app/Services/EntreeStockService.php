<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\LigneCommande;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;

class EntreeStockService
{
    public function __construct(protected FifoService $fifoService) {}

    public function creerEntreeManuelle(int $magasinId, int $articleId, int $quantite, float $coutUnitaire, ?string $referenceDocument = null, ?string $motif = null): StockMouvement
    {
        return DB::transaction(function () use ($magasinId, $articleId, $quantite, $coutUnitaire, $referenceDocument, $motif) {
            $magasin = Magasin::findOrFail($magasinId);

            // RG-F3-01: Entrée impossible sur magasin inactif
            if (! $magasin->est_actif) {
                throw new \Exception("Impossible d'effectuer une entrée sur un magasin inactif.");
            }

            // RG-F3-07: quantite > 0 obligatoire
            if ($quantite <= 0) {
                throw new \Exception('La quantité entrée doit être supérieure à 0.');
            }

            // RG-F3-02: Coût unitaire obligatoire
            if ($coutUnitaire < 0) {
                throw new \Exception('Le coût unitaire doit être positif.');
            }

            // Mouvement
            $mouvement = StockMouvement::create([
                'type_mouvement' => 'ENTREE',
                'article_id' => $articleId,
                'magasin_id' => $magasinId,
                'quantite' => $quantite,
                'cout_unitaire' => $coutUnitaire,
                'type_origine' => 'MANUEL',
                'reference_document' => $referenceDocument,
                'motif' => $motif,
                'valide_at' => now(),
            ]);

            // Lot
            StockLot::create([
                'article_id' => $articleId,
                'magasin_id' => $magasinId,
                'quantite_initiale' => $quantite,
                'quantite_restante' => $quantite,
                'cout_unitaire' => $coutUnitaire,
                'date_entree' => now()->toDateString(),
                'mouvement_id' => $mouvement->id,
            ]);

            // Mettre à jour StockArticleMagasin
            $stockArticle = StockArticleMagasin::firstOrNew([
                'article_id' => $articleId,
                'magasin_id' => $magasinId,
            ]);

            $stockArticle->quantite_actuelle += $quantite;
            $stockArticle->derniere_entree_at = now();
            $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasinId, $articleId);
            $stockArticle->save();

            activity()
                ->performedOn($mouvement)
                ->log('manual_entry_created');

            return $mouvement;
        });
    }

    public function creerDepuisBL(BordereauLivraison $bl, int $userId): void
    {
        DB::transaction(function () use ($bl, $userId) {
            $magasin = Magasin::where('type', 'CONSOMMABLE')->where('est_actif', true)->first()
                ?? Magasin::where('est_actif', true)->first();

            if (! $magasin) {
                $magasin = Magasin::create([
                    'code' => 'MAG-DEFAULT',
                    'nom' => 'Magasin Principal par Défaut',
                    'type' => 'CONSOMMABLE',
                    'est_actif' => true,
                ]);
            }

            foreach ($bl->lignesLivraison as $line) {
                $article = $line->article;

                $ligneCommande = LigneCommande::where('bon_de_commande_id', $bl->bon_de_commande_id)
                    ->where('article_id', $article->id)
                    ->first();

                $prixUnitaire = $ligneCommande ? $ligneCommande->prix_unitaire : 0.00;

                $mouvement = StockMouvement::create([
                    'type_mouvement' => 'ENTREE',
                    'article_id' => $article->id,
                    'magasin_id' => $magasin->id,
                    'quantite' => $line->quantite_livree,
                    'cout_unitaire' => $prixUnitaire,
                    'type_origine' => 'BL',
                    'origine_id' => $bl->id,
                    'reference_document' => $bl->numero_livraison,
                    'motif' => "Entrée automatique via validation BL n° {$bl->numero_livraison}",
                    'valide_at' => now(),
                    'created_by' => $userId,
                ]);

                StockLot::create([
                    'article_id' => $article->id,
                    'magasin_id' => $magasin->id,
                    'quantite_initiale' => $line->quantite_livree,
                    'quantite_restante' => $line->quantite_livree,
                    'cout_unitaire' => $prixUnitaire,
                    'date_entree' => $bl->date_livraison ?? now()->toDateString(),
                    'mouvement_id' => $mouvement->id,
                ]);

                $stockArticle = StockArticleMagasin::firstOrNew([
                    'article_id' => $article->id,
                    'magasin_id' => $magasin->id,
                ]);

                $stockArticle->quantite_actuelle += $line->quantite_livree;
                $stockArticle->derniere_entree_at = now();
                $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasin->id, $article->id);
                $stockArticle->save();
            }

            activity()
                ->performedOn($bl)
                ->log('stock_entries_generated_from_bl');
        });
    }

    public function supprimerEntreeManuelle(int $mouvementId, int $userId): void
    {
        DB::transaction(function () use ($mouvementId) {
            $mouvement = StockMouvement::findOrFail($mouvementId);

            if ($mouvement->type_origine === 'BL') {
                throw new \Exception('Impossible de supprimer une entrée générée automatiquement depuis un bon de livraison (BL).');
            }

            if ($mouvement->type_origine !== 'MANUEL') {
                throw new \Exception('Seules les entrées manuelles peuvent être supprimées.');
            }

            if ($mouvement->created_at->diffInHours(now()) >= 24) {
                throw new \Exception('Impossible de supprimer une entrée créée il y a plus de 24 heures.');
            }

            $lot = StockLot::where('mouvement_id', $mouvement->id)->first();
            if ($lot) {
                if ($lot->quantite_restante < $lot->quantite_initiale) {
                    throw new \Exception('Impossible de supprimer cette entrée car une partie du lot a déjà été consommée.');
                }
                $lot->delete();
            }

            $stockArticle = StockArticleMagasin::where('article_id', $mouvement->article_id)
                ->where('magasin_id', $mouvement->magasin_id)
                ->first();

            if ($stockArticle) {
                $stockArticle->quantite_actuelle = max(0, $stockArticle->quantite_actuelle - $mouvement->quantite);
                $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($mouvement->magasin_id, $mouvement->article_id);
                $stockArticle->save();
            }

            $mouvement->delete();

            activity()
                ->performedOn($mouvement)
                ->log('manual_entry_deleted');
        });
    }
}
