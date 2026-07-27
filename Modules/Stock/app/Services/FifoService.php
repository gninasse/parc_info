<?php

namespace Modules\Stock\Services;

use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\StockLot;

/**
 * Moteur FIFO — consommation des lots et valorisation (F2).
 *
 * Toutes les méthodes d'écriture supposent une transaction ouverte par le
 * service appelant (RG-F3-04, RG-F4-02) ; les lots sont verrouillés
 * (lockForUpdate) pour sérialiser les consommations concurrentes.
 */
class FifoService
{
    /**
     * Plan de consommation FIFO sans écriture (simulation).
     *
     * @return array{plan: list<array{lot_id:int,quantite:int,cout_unitaire:float}>, cout_total: float}
     *
     * @throws RegleMetierException si le stock disponible est insuffisant
     */
    public function calculerSortie(int $articleId, int $magasinId, int $quantite): array
    {
        $lots = StockLot::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->disponible()
            ->ordreFifo()
            ->get();

        return $this->repartirSurLots($lots->all(), $quantite);
    }

    /**
     * Consomme les lots FIFO (RG-F2-06) et retourne le plan appliqué.
     *
     * @return array{plan: list<array{lot_id:int,quantite:int,cout_unitaire:float}>, cout_total: float}
     *
     * @throws RegleMetierException si le stock disponible est insuffisant
     */
    public function consommerLots(int $articleId, int $magasinId, int $quantite): array
    {
        $lots = StockLot::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->disponible()
            ->ordreFifo()
            ->lockForUpdate()
            ->get();

        $resultat = $this->repartirSurLots($lots->all(), $quantite);

        foreach ($resultat['plan'] as $prise) {
            StockLot::where('id', $prise['lot_id'])->decrement('quantite_restante', $prise['quantite']);
        }

        return $resultat;
    }

    /** RG-F2-05 — Valeur FIFO = Σ(quantité restante × coût unitaire du lot). */
    public function calculerValeur(int $magasinId, int $articleId): float
    {
        return (float) StockLot::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->disponible()
            ->selectRaw('COALESCE(SUM(quantite_restante * cout_unitaire), 0) as valeur')
            ->value('valeur');
    }

    /**
     * @param  list<StockLot>  $lots  Lots dans l'ordre FIFO
     * @return array{plan: list<array{lot_id:int,quantite:int,cout_unitaire:float}>, cout_total: float}
     */
    protected function repartirSurLots(array $lots, int $quantite): array
    {
        $restant = $quantite;
        $plan = [];
        $coutTotal = 0.0;

        foreach ($lots as $lot) {
            if ($restant === 0) {
                break;
            }

            $prise = min($lot->quantite_restante, $restant);
            $plan[] = [
                'lot_id' => $lot->id,
                'quantite' => $prise,
                'cout_unitaire' => (float) $lot->cout_unitaire,
            ];
            $coutTotal += $prise * (float) $lot->cout_unitaire;
            $restant -= $prise;
        }

        if ($restant > 0) {
            $disponible = $quantite - $restant;

            throw new RegleMetierException(
                "Stock insuffisant : {$disponible} unité(s) disponible(s) en lots pour {$quantite} demandée(s)."
            );
        }

        return ['plan' => $plan, 'cout_total' => round($coutTotal, 2)];
    }
}
