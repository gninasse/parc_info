<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\Article;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;

class StockArticleService
{
    public function __construct(protected FifoService $fifoService) {}

    public function initialiser(int $magasinId, int $articleId, int $quantiteInitiale, float $coutUnitaire, ?string $dateEntree = null): StockArticleMagasin
    {
        return DB::transaction(function () use ($magasinId, $articleId, $quantiteInitiale, $coutUnitaire, $dateEntree) {
            $magasin = Magasin::findOrFail($magasinId);

            // RG-F2-02: Initialisation impossible sur magasin inactif
            if (! $magasin->est_actif) {
                throw new \Exception("Impossible d'initialiser un article sur un magasin inactif.");
            }

            // RG-F2-01: Un article ne peut être initialisé qu'une seule fois par magasin
            $exists = StockArticleMagasin::where('magasin_id', $magasinId)
                ->where('article_id', $articleId)
                ->exists();

            if ($exists) {
                throw new \Exception('Cet article est déjà initialisé dans ce magasin.');
            }

            // RG-F2-03: Coût unitaire obligatoire à l'initialisation
            if ($coutUnitaire < 0) {
                throw new \Exception('Le coût unitaire doit être positif.');
            }

            if ($quantiteInitiale < 0) {
                throw new \Exception('La quantité initiale ne peut pas être négative.');
            }

            $date = $dateEntree ? date('Y-m-d', strtotime($dateEntree)) : date('Y-m-d');

            // Créer le lot
            $lot = StockLot::create([
                'article_id' => $articleId,
                'magasin_id' => $magasinId,
                'quantite_initiale' => $quantiteInitiale,
                'quantite_restante' => $quantiteInitiale,
                'cout_unitaire' => $coutUnitaire,
                'date_entree' => $date,
            ]);

            // Créer la ligne de stock
            $stockArticle = StockArticleMagasin::create([
                'article_id' => $articleId,
                'magasin_id' => $magasinId,
                'quantite_actuelle' => $quantiteInitiale,
                'valeur_stock_fifo' => $quantiteInitiale * $coutUnitaire,
                'derniere_entree_at' => now(),
            ]);

            // Logger l'activité
            activity()
                ->performedOn($stockArticle)
                ->withProperty('lot_id', $lot->id)
                ->log('article_initialized');

            return $stockArticle;
        });
    }

    public function updateSeuil(int $articleId, int $seuil): void
    {
        DB::transaction(function () use ($articleId, $seuil) {
            $article = Article::findOrFail($articleId);
            $article->update(['seuil_alerte' => $seuil]);

            activity()
                ->performedOn($article)
                ->withProperty('seuil_alerte', $seuil)
                ->log('seuil_alerte_updated');
        });
    }

    public function verifierAlerte(StockArticleMagasin $stock): void
    {
        $seuil = $stock->article?->seuil_alerte ?? 0;
        if ($stock->quantite_actuelle <= $seuil) {
            activity()
                ->performedOn($stock)
                ->withProperty('quantite_actuelle', $stock->quantite_actuelle)
                ->withProperty('seuil_alerte', $seuil)
                ->log('stock_alert_low');

            try {
                $recipient = config('stock.alert_email', 'admin@example.com');
                \Illuminate\Support\Facades\Mail::to($recipient)->send(new \Modules\Stock\Emails\LowStockAlertMail($stock));
            } catch (\Exception $e) {
                // Ignorer pour éviter de bloquer l'application
            }
        }
    }
}
