<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockSnapshot;
use Modules\Stock\Models\StockSnapshotLigne;

/**
 * Photographies de valorisation (F7) — RG-F7-01→05.
 */
class SnapshotService
{
    /**
     * RG-F7-02 — Snapshot atomique de tous les magasins.
     *
     * @throws RegleMetierException
     */
    public function creer(string $type, ?int $userId = null): StockSnapshot
    {
        if (! in_array($type, ['MENSUEL', 'MANUEL'], true)) {
            throw new RegleMetierException("Type de snapshot {$type} invalide.");
        }

        $reference = $this->genererReference($type);

        if (StockSnapshot::where('reference', $reference)->exists()) {
            throw new RegleMetierException("Le snapshot {$reference} existe déjà (immuable).");
        }

        return DB::transaction(function () use ($type, $reference, $userId) {
            $snapshot = StockSnapshot::create([
                'reference' => $reference,
                'type' => $type,
                'date_snapshot' => now(),
                'created_by' => $userId,
            ]);

            $valeurTotale = 0.0;

            StockArticleMagasin::query()
                ->orderBy('id')
                ->each(function (StockArticleMagasin $stock) use ($snapshot, &$valeurTotale) {
                    $valeurTotale += (float) $stock->valeur_stock_fifo;

                    StockSnapshotLigne::create([
                        'snapshot_id' => $snapshot->id,
                        'magasin_id' => $stock->magasin_id,
                        'article_id' => $stock->article_id,
                        'quantite' => $stock->quantite_actuelle,
                        'valeur_fifo' => $stock->valeur_stock_fifo,
                        'cout_unitaire_moyen' => $stock->quantite_actuelle > 0
                            ? round((float) $stock->valeur_stock_fifo / $stock->quantite_actuelle, 4)
                            : 0,
                    ]);
                });

            $snapshot->update(['valeur_totale_globale' => round($valeurTotale, 2)]);

            activity()->performedOn($snapshot)->log("Snapshot {$reference} créé");

            return $snapshot;
        });
    }

    /** RG-F7-04/05 — YYYY-MM (mensuel) ou YYYY-MM-MANUEL-N. */
    protected function genererReference(string $type): string
    {
        $prefixe = now()->format('Y-m');

        if ($type === 'MENSUEL') {
            return $prefixe;
        }

        $numero = StockSnapshot::where('type', 'MANUEL')
            ->where('reference', 'like', "{$prefixe}-MANUEL-%")
            ->count() + 1;

        return "{$prefixe}-MANUEL-{$numero}";
    }
}
