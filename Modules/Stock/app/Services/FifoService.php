<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\StockLot;

class FifoService
{
    public function calculerValeur(int $magasinId, int $articleId): float
    {
        return round((float) StockLot::where('magasin_id', $magasinId)
            ->where('article_id', $articleId)
            ->where('quantite_restante', '>', 0)
            ->sum(DB::raw('quantite_restante * cout_unitaire')), 2);
    }

    public function consommerLots(int $magasinId, int $articleId, int $quantiteAEnlever, int $mouvementId): array
    {
        if ($quantiteAEnlever <= 0) {
            return [];
        }

        $lots = StockLot::where('magasin_id', $magasinId)
            ->where('article_id', $articleId)
            ->where('quantite_restante', '>', 0)
            ->orderBy('date_entree', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $lotsConsommes = [];
        $restant = $quantiteAEnlever;

        foreach ($lots as $lot) {
            if ($restant <= 0) {
                break;
            }

            $aPrelever = min($lot->quantite_restante, $restant);
            $lot->quantite_restante -= $aPrelever;
            $lot->save();

            $lotsConsommes[] = [
                'lot_id' => $lot->id,
                'quantite_consommee' => $aPrelever,
                'cout_unitaire' => (float) $lot->cout_unitaire,
            ];

            $restant -= $aPrelever;
        }

        if ($restant > 0) {
            throw new \Exception("Stock insuffisant pour l'article ID {$articleId} dans le magasin ID {$magasinId}. Manque {$restant} unités.");
        }

        return $lotsConsommes;
    }
}
