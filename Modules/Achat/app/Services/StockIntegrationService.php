<?php

namespace Modules\Achat\Services;

use Modules\Achat\Contracts\StockIntegrationInterface;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\LigneCommande;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\FifoService;

/**
 * Seul point de contact du module Achat avec les modèles Stock.
 *
 * Correction AN-12 : l'implémentation précédente enregistrait en stock
 * l'intégralité des lignes du bordereau, équipements et licences compris,
 * alors que ces natures donnent lieu à des fiches individuelles dans ParcInfo.
 * Le filtre sur config('achat.types_avec_stock') supprime cette
 * comptabilisation indue.
 */
class StockIntegrationService implements StockIntegrationInterface
{
    public function __construct(protected FifoService $fifoService) {}

    public function estDisponible(): bool
    {
        return config('achat.integration.stock', true)
            && class_exists(StockMouvement::class);
    }

    public function enregistrerEntreesDepuisBordereau(BordereauLivraison $bordereau, int $userId): int
    {
        if (! $this->estDisponible()) {
            return 0;
        }

        $typesStockables = config('achat.types_avec_stock', ['consommable']);
        $magasin = $this->magasinDeReception();
        $enregistrees = 0;

        foreach ($bordereau->lignesLivraison as $ligne) {
            $article = $ligne->article;

            if (! in_array($article->type_article, $typesStockables, true)) {
                continue;
            }

            $ligneCommande = LigneCommande::where('bon_de_commande_id', $bordereau->bon_de_commande_id)
                ->where('article_id', $article->id)
                ->first();

            $coutUnitaire = $ligneCommande ? (float) $ligneCommande->prix_unitaire : 0.0;

            $mouvement = StockMouvement::create([
                'type_mouvement' => 'ENTREE',
                'article_id' => $article->id,
                'magasin_id' => $magasin->id,
                'quantite' => $ligne->quantite_livree,
                'cout_unitaire' => $coutUnitaire,
                'type_origine' => 'BL',
                'origine_id' => $bordereau->id,
                'reference_document' => $bordereau->numero_livraison,
                'motif' => "Entrée automatique via validation du BL n° {$bordereau->numero_livraison}",
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            StockLot::create([
                'article_id' => $article->id,
                'magasin_id' => $magasin->id,
                'quantite_initiale' => $ligne->quantite_livree,
                'quantite_restante' => $ligne->quantite_livree,
                'cout_unitaire' => $coutUnitaire,
                'date_entree' => $bordereau->date_livraison ?? now()->toDateString(),
                'mouvement_id' => $mouvement->id,
            ]);

            $stockArticle = StockArticleMagasin::firstOrNew([
                'article_id' => $article->id,
                'magasin_id' => $magasin->id,
            ]);

            $stockArticle->quantite_actuelle = ($stockArticle->quantite_actuelle ?? 0) + $ligne->quantite_livree;
            $stockArticle->derniere_entree_at = now();
            $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasin->id, $article->id);
            $stockArticle->save();

            $enregistrees++;
        }

        return $enregistrees;
    }

    /** Magasin de destination des réceptions, créé au besoin. */
    protected function magasinDeReception(): Magasin
    {
        $magasin = Magasin::where('type', 'CONSOMMABLE')->where('est_actif', true)->first()
            ?? Magasin::where('est_actif', true)->first();

        return $magasin ?? Magasin::create([
            'code' => 'MAG-DEFAULT',
            'nom' => 'Magasin principal par défaut',
            'type' => 'CONSOMMABLE',
            'est_actif' => true,
        ]);
    }
}
