<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\User;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Contracts\GrhIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Models\StockTransfert;
use Modules\Stock\Notifications\TransfertEnAttente;
use Modules\Stock\Traits\GeneratesDocumentNumbers;

/**
 * Transferts inter-magasins (F5) — EN_ATTENTE → VALIDE | REJETE | ANNULE.
 *
 * RG-F5-04 adaptée : la validation relève de la permission
 * stock.transferts.valider (spatie), la matrice de droits par magasin ayant
 * été abandonnée.
 */
class TransfertService
{
    use GeneratesDocumentNumbers;

    public function __construct(
        protected FifoService $fifoService,
        protected AchatIntegrationInterface $achat,
        protected GrhIntegrationInterface $grh,
    ) {}

    /** @throws RegleMetierException */
    public function creer(array $donnees, int $userId): StockTransfert
    {
        $source = Magasin::find($donnees['magasin_source_id']);
        $destination = Magasin::find($donnees['magasin_destination_id']);

        if (! $source || ! $destination) {
            throw new RegleMetierException('Magasin source ou destination introuvable.');
        }

        // RG-F5-01
        if ($source->id === $destination->id) {
            throw new RegleMetierException('Les magasins source et destination doivent être distincts.');
        }

        // RG-F5-02 — les deux magasins actifs à la création.
        if (! $source->estActif() || ! $destination->estActif()) {
            throw new RegleMetierException('Les deux magasins doivent être actifs.');
        }

        if (! $this->achat->estStockable((int) $donnees['article_id'])) {
            throw new RegleMetierException("Cet article n'alimente pas le stock physique.");
        }

        if ((int) $donnees['quantite'] <= 0) {
            throw new RegleMetierException('La quantité doit être strictement positive.');
        }

        $transfert = DB::transaction(function () use ($donnees, $userId) {
            $transfert = StockTransfert::create([
                'numero_transfert' => $this->genererNumero('TRF', 'TRF'),
                'magasin_source_id' => $donnees['magasin_source_id'],
                'magasin_destination_id' => $donnees['magasin_destination_id'],
                'article_id' => $donnees['article_id'],
                'quantite' => $donnees['quantite'],
                'statut' => 'EN_ATTENTE',
                'motif_creation' => $donnees['motif_creation'],
                'created_by' => $userId,
            ]);

            activity()->performedOn($transfert)->causedBy(User::find($userId))
                ->log("Transfert {$transfert->numero_transfert} créé (en attente)");

            return $transfert;
        });

        $this->notifierResponsablesDestination($transfert);

        return $transfert;
    }

    /**
     * RG-F5-03/06 — Validation : double mouvement atomique, coût FIFO source
     * conservé sur les lots destination.
     *
     * @throws RegleMetierException
     */
    public function valider(StockTransfert $transfert, int $userId): StockTransfert
    {
        if (! $transfert->estEnAttente()) {
            throw new RegleMetierException('Seul un transfert en attente peut être validé.');
        }

        $source = $transfert->magasinSource;
        $destination = $transfert->magasinDestination;

        // RG-F5-02 — les deux magasins actifs à la validation aussi.
        if (! $source->estActif() || ! $destination->estActif()) {
            throw new RegleMetierException('Les deux magasins doivent être actifs pour valider le transfert.');
        }

        // RG-F5-03 — le stock est contrôlé à la validation, pas à la création.
        $disponible = (int) StockArticleMagasin::where('article_id', $transfert->article_id)
            ->where('magasin_id', $source->id)
            ->value('quantite_actuelle');

        if ($disponible < $transfert->quantite) {
            throw new RegleMetierException(
                "Stock source insuffisant : {$disponible} unité(s) disponible(s) pour {$transfert->quantite} demandée(s)."
            );
        }

        return DB::transaction(function () use ($transfert, $source, $destination, $userId) {
            $fifo = $this->fifoService->consommerLots($transfert->article_id, $source->id, $transfert->quantite);
            $coutUnitaireMoyen = round($fifo['cout_total'] / $transfert->quantite, 4);

            $sortant = StockMouvement::create([
                'numero_mouvement' => $this->genererNumero('BT', 'BT'),
                'type_mouvement' => 'TRANSFERT_SORTANT',
                'article_id' => $transfert->article_id,
                'magasin_id' => $source->id,
                'quantite' => $transfert->quantite,
                'cout_unitaire' => $coutUnitaireMoyen,
                'type_origine' => 'TRANSFERT',
                'origine_id' => $transfert->id,
                'reference_document' => $transfert->numero_transfert,
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            $entrant = StockMouvement::create([
                'numero_mouvement' => $this->genererNumero('BT', 'BT'),
                'type_mouvement' => 'TRANSFERT_ENTRANT',
                'article_id' => $transfert->article_id,
                'magasin_id' => $destination->id,
                'quantite' => $transfert->quantite,
                'cout_unitaire' => $coutUnitaireMoyen,
                'type_origine' => 'TRANSFERT',
                'origine_id' => $transfert->id,
                'reference_document' => $transfert->numero_transfert,
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            // RG-F5-06 — un lot destination par lot source consommé, au même coût.
            foreach ($fifo['plan'] as $prise) {
                StockLot::create([
                    'article_id' => $transfert->article_id,
                    'magasin_id' => $destination->id,
                    'quantite_initiale' => $prise['quantite'],
                    'quantite_restante' => $prise['quantite'],
                    'cout_unitaire' => $prise['cout_unitaire'],
                    'date_entree' => now()->toDateString(),
                    'mouvement_id' => $entrant->id,
                ]);
            }

            $this->actualiserProjection($transfert->article_id, $source->id, sortie: true);
            $this->actualiserProjection($transfert->article_id, $destination->id, sortie: false);

            $transfert->update([
                'statut' => 'VALIDE',
                'valide_par' => $userId,
                'date_validation' => now(),
                'mouvement_sortant_id' => $sortant->id,
                'mouvement_entrant_id' => $entrant->id,
            ]);

            activity()->performedOn($transfert)->causedBy(User::find($userId))
                ->log("Transfert {$transfert->numero_transfert} validé");

            return $transfert;
        });
    }

    /** @throws RegleMetierException */
    public function rejeter(StockTransfert $transfert, int $userId, string $motif): StockTransfert
    {
        if (! $transfert->estEnAttente()) {
            throw new RegleMetierException('Seul un transfert en attente peut être rejeté.');
        }

        if (blank($motif)) {
            throw new RegleMetierException('Le motif de rejet est obligatoire.');
        }

        $transfert->update([
            'statut' => 'REJETE',
            'motif_rejet' => $motif,
            'valide_par' => $userId,
            'date_validation' => now(),
        ]);

        activity()->performedOn($transfert)->causedBy(User::find($userId))
            ->log("Transfert {$transfert->numero_transfert} rejeté");

        return $transfert;
    }

    /**
     * Annulation par le créateur ou un administrateur (contrôlé par l'appelant
     * pour l'admin ; le créateur est vérifié ici).
     *
     * @throws RegleMetierException
     */
    public function annuler(StockTransfert $transfert, int $userId, bool $estAdmin = false): StockTransfert
    {
        if (! $transfert->estEnAttente()) {
            throw new RegleMetierException('Seul un transfert en attente peut être annulé.');
        }

        if (! $estAdmin && $transfert->created_by !== $userId) {
            throw new RegleMetierException('Seul le créateur du transfert ou un administrateur peut l\'annuler.');
        }

        $transfert->update(['statut' => 'ANNULE']);

        activity()->performedOn($transfert)->causedBy(User::find($userId))
            ->log("Transfert {$transfert->numero_transfert} annulé");

        return $transfert;
    }

    // ── Internes ───────────────────────────────────────────────────────────

    protected function notifierResponsablesDestination(StockTransfert $transfert): void
    {
        $employeIds = $transfert->magasinDestination->responsables
            ->filter(fn ($responsable) => $responsable->estEnCours())
            ->pluck('employe_id')
            ->all();

        $userIds = $this->grh->utilisateursDesEmployes($employeIds);

        if ($userIds !== []) {
            Notification::send(User::whereIn('id', $userIds)->get(), new TransfertEnAttente($transfert));
        }
    }

    protected function actualiserProjection(int $articleId, int $magasinId, bool $sortie): void
    {
        $stockArticle = StockArticleMagasin::firstOrCreate(
            ['article_id' => $articleId, 'magasin_id' => $magasinId]
        );

        $stockArticle->quantite_actuelle = (int) StockLot::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->sum('quantite_restante');
        $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasinId, $articleId);

        if ($sortie) {
            $stockArticle->derniere_sortie_at = now();
        } else {
            $stockArticle->derniere_entree_at = now();
        }

        $stockArticle->save();
    }
}
