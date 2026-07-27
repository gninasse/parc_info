<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;

/**
 * Réalignement des projections stock_articles_magasin sur les lots FIFO,
 * qui font foi (RG-F2-05). Utilisé en maintenance (F7).
 */
class RecalculFifoService
{
    /**
     * Recalcule toutes les projections et rapporte les écarts corrigés.
     *
     * @return array{corrigees:int, total:int, ecarts:list<array{article_id:int,magasin_id:int,avant:int,apres:int}>}
     */
    public function recalculerTout(): array
    {
        return DB::transaction(function () {
            $ecarts = [];
            $total = 0;

            StockArticleMagasin::query()->orderBy('id')->each(function (StockArticleMagasin $stock) use (&$ecarts, &$total) {
                $total++;

                $quantite = (int) StockLot::where('article_id', $stock->article_id)
                    ->where('magasin_id', $stock->magasin_id)
                    ->sum('quantite_restante');

                $valeur = (float) StockLot::where('article_id', $stock->article_id)
                    ->where('magasin_id', $stock->magasin_id)
                    ->where('quantite_restante', '>', 0)
                    ->selectRaw('COALESCE(SUM(quantite_restante * cout_unitaire), 0) as valeur')
                    ->value('valeur');

                if ($stock->quantite_actuelle !== $quantite || (float) $stock->valeur_stock_fifo !== round($valeur, 2)) {
                    $ecarts[] = [
                        'article_id' => $stock->article_id,
                        'magasin_id' => $stock->magasin_id,
                        'avant' => $stock->quantite_actuelle,
                        'apres' => $quantite,
                    ];

                    $stock->update([
                        'quantite_actuelle' => $quantite,
                        'valeur_stock_fifo' => round($valeur, 2),
                    ]);
                }
            });

            if ($ecarts !== []) {
                activity()->log('Recalcul FIFO : '.count($ecarts).' projection(s) réalignée(s)');
            }

            return ['corrigees' => count($ecarts), 'total' => $total, 'ecarts' => $ecarts];
        });
    }
}
