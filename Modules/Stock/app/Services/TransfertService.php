<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Models\StockTransfert;
use Modules\Stock\Traits\GeneratesDocumentNumbers;

class TransfertService
{
    use GeneratesDocumentNumbers;

    public function __construct(protected FifoService $fifoService) {}

    public function creerTransfert(int $sourceId, int $destId, int $articleId, int $quantite, string $motif, int $userId): StockTransfert
    {
        return DB::transaction(function () use ($sourceId, $destId, $articleId, $quantite, $motif, $userId) {
            $source = Magasin::findOrFail($sourceId);
            $dest = Magasin::findOrFail($destId);

            // RG-F5-01: Les magasins source et destination doivent être actifs
            if (! $source->est_actif) {
                throw new \Exception("Le magasin source n'est pas actif.");
            }
            if (! $dest->est_actif) {
                throw new \Exception("Le magasin destination n'est pas actif.");
            }

            // RG-F5-02: Les magasins doivent être différents
            if ($sourceId === $destId) {
                throw new \Exception('Les magasins source et destination doivent être différents.');
            }

            if ($quantite <= 0) {
                throw new \Exception('La quantité à transférer doit être supérieure à 0.');
            }

            // RG-F5-03: Le stock dans le magasin source doit être suffisant
            $stockSource = StockArticleMagasin::where('magasin_id', $sourceId)
                ->where('article_id', $articleId)
                ->first();

            if (! $stockSource || $stockSource->quantite_actuelle < $quantite) {
                $actuel = $stockSource ? $stockSource->quantite_actuelle : 0;
                throw new \Exception("Stock insuffisant dans le magasin source. Disponible : {$actuel}, Demandé : {$quantite}.");
            }

            // Générer le numéro de transfert
            $numero = $this->genererNumero('TRANSFERT', 'TR');

            $transfert = StockTransfert::create([
                'numero_transfert' => $numero,
                'magasin_source_id' => $sourceId,
                'magasin_destination_id' => $destId,
                'article_id' => $articleId,
                'quantite' => $quantite,
                'statut' => 'EN_ATTENTE',
                'motif_creation' => $motif,
                'created_by' => $userId,
            ]);

            activity()
                ->performedOn($transfert)
                ->log('transfer_request_created');

            return $transfert;
        });
    }

    public function validerTransfert(int $transfertId, int $userId): void
    {
        DB::transaction(function () use ($transfertId, $userId) {
            $transfert = StockTransfert::lockForUpdate()->findOrFail($transfertId);

            if ($transfert->statut !== 'EN_ATTENTE') {
                throw new \Exception("Ce transfert ne peut plus être validé (statut actuel : {$transfert->statut}).");
            }

            $sourceId = $transfert->magasin_source_id;
            $destId = $transfert->magasin_destination_id;
            $articleId = $transfert->article_id;
            $quantite = $transfert->quantite;

            // Vérifier à nouveau la disponibilité du stock
            $stockSource = StockArticleMagasin::where('magasin_id', $sourceId)
                ->where('article_id', $articleId)
                ->lockForUpdate()
                ->first();

            if (! $stockSource || $stockSource->quantite_actuelle < $quantite) {
                $actuel = $stockSource ? $stockSource->quantite_actuelle : 0;
                throw new \Exception("Stock insuffisant dans le magasin source lors de la validation. Disponible : {$actuel}, Demandé : {$quantite}.");
            }

            // 1. Mouvement de SORTIE (FIFO) dans magasin source
            $mouvementSortie = StockMouvement::create([
                'type_mouvement' => 'SORTIE',
                'article_id' => $articleId,
                'magasin_id' => $sourceId,
                'quantite' => $quantite,
                'cout_unitaire' => 0, // Sera mis à jour après calcul moyen
                'type_origine' => 'TRANSFERT',
                'origine_id' => $transfert->id,
                'reference_document' => $transfert->numero_transfert,
                'motif' => "Sortie pour transfert {$transfert->numero_transfert}",
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            // Consommer les lots FIFO
            $consumptions = $this->fifoService->consommerLots($sourceId, $articleId, $quantite, $mouvementSortie->id);

            // Calculer le coût unitaire moyen des lots sortis
            $coutTotal = 0.0;
            foreach ($consumptions as $c) {
                $coutTotal += $c['quantite_consommee'] * $c['cout_unitaire'];
            }
            $coutMoyen = round($coutTotal / $quantite, 4);

            // Mettre à jour le coût unitaire de la sortie
            $mouvementSortie->update(['cout_unitaire' => $coutMoyen]);

            // Mettre à jour le stock magasin source
            $stockSource->quantite_actuelle -= $quantite;
            $stockSource->valeur_stock_fifo = $this->fifoService->calculerValeur($sourceId, $articleId);
            $stockSource->save();

            app(StockArticleService::class)->verifierAlerte($stockSource);

            // 2. Mouvement d'ENTREE dans le magasin destination
            $mouvementEntree = StockMouvement::create([
                'type_mouvement' => 'ENTREE',
                'article_id' => $articleId,
                'magasin_id' => $destId,
                'quantite' => $quantite,
                'cout_unitaire' => $coutMoyen,
                'type_origine' => 'TRANSFERT',
                'origine_id' => $transfert->id,
                'reference_document' => $transfert->numero_transfert,
                'motif' => "Entrée depuis transfert {$transfert->numero_transfert}",
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            // Créer le nouveau lot FIFO dans le magasin destination
            StockLot::create([
                'article_id' => $articleId,
                'magasin_id' => $destId,
                'quantite_initiale' => $quantite,
                'quantite_restante' => $quantite,
                'cout_unitaire' => $coutMoyen,
                'date_entree' => now()->toDateString(),
                'mouvement_id' => $mouvementEntree->id,
            ]);

            // Mettre à jour le stock magasin destination
            $stockDest = StockArticleMagasin::firstOrNew([
                'magasin_id' => $destId,
                'article_id' => $articleId,
            ]);
            $stockDest->quantite_actuelle += $quantite;
            $stockDest->valeur_stock_fifo = $this->fifoService->calculerValeur($destId, $articleId);
            $stockDest->derniere_entree_at = now();
            $stockDest->save();

            // 3. Mettre à jour le transfert
            $transfert->update([
                'statut' => 'VALIDE',
                'valide_par' => $userId,
                'date_validation' => now(),
                'mouvement_sortant_id' => $mouvementSortie->id,
                'mouvement_entrant_id' => $mouvementEntree->id,
            ]);

            activity()
                ->performedOn($transfert)
                ->log('transfer_approved');
        });
    }

    public function rejeterTransfert(int $transfertId, string $motifRejet, int $userId): void
    {
        DB::transaction(function () use ($transfertId, $motifRejet, $userId) {
            $transfert = StockTransfert::lockForUpdate()->findOrFail($transfertId);

            if ($transfert->statut !== 'EN_ATTENTE') {
                throw new \Exception("Ce transfert ne peut pas être rejeté (statut actuel : {$transfert->statut}).");
            }

            $transfert->update([
                'statut' => 'REJETE',
                'motif_rejet' => $motifRejet,
                'valide_par' => $userId,
                'date_validation' => now(),
            ]);

            activity()
                ->performedOn($transfert)
                ->log('transfer_rejected');
        });
    }

    public function annulerTransfert(int $transfertId, int $userId): void
    {
        DB::transaction(function () use ($transfertId) {
            $transfert = StockTransfert::lockForUpdate()->findOrFail($transfertId);

            if ($transfert->statut !== 'EN_ATTENTE') {
                throw new \Exception("Ce transfert ne peut pas être annulé (statut actuel : {$transfert->statut}).");
            }

            $transfert->update([
                'statut' => 'ANNULE',
            ]);

            activity()
                ->performedOn($transfert)
                ->log('transfer_cancelled');
        });
    }
}
