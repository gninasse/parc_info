<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockSnapshot;
use Modules\Stock\Models\StockSnapshotLigne;

class ValorisationService
{
    public function genererSnapshot(string $type = 'PONCTUEL', ?int $userId = null): StockSnapshot
    {
        return DB::transaction(function () use ($type, $userId) {
            $date = now();
            $count = StockSnapshot::count() + 1;
            $reference = 'VAL-'.$date->format('Y-m').'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            // Créer d'abord le snapshot vide
            $snapshot = StockSnapshot::create([
                'reference' => $reference,
                'type' => $type,
                'date_snapshot' => $date,
                'valeur_totale_globale' => 0.00,
                'created_by' => $userId,
            ]);

            // Récupérer toutes les fiches de stock non vides
            $stocks = StockArticleMagasin::with('article', 'magasin')
                ->where('quantite_actuelle', '>', 0)
                ->get();

            $valeurTotaleGlobale = 0.00;

            foreach ($stocks as $stock) {
                $valeurFifo = (float) $stock->valeur_stock_fifo;
                $quantite = (int) $stock->quantite_actuelle;
                $coutMoyen = $quantite > 0 ? round($valeurFifo / $quantite, 4) : 0.00;

                StockSnapshotLigne::create([
                    'snapshot_id' => $snapshot->id,
                    'magasin_id' => $stock->magasin_id,
                    'article_id' => $stock->article_id,
                    'quantite' => $quantite,
                    'valeur_fifo' => $valeurFifo,
                    'cout_unitaire_moyen' => $coutMoyen,
                ]);

                $valeurTotaleGlobale += $valeurFifo;
            }

            // Mettre à jour la valeur globale cumulée
            $snapshot->update([
                'valeur_totale_globale' => round($valeurTotaleGlobale, 2),
            ]);

            activity()
                ->performedOn($snapshot)
                ->log('stock_snapshot_generated');

            return $snapshot;
        });
    }
}
