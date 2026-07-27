<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Traits\GeneratesDocumentNumbers;

/**
 * Entrées de stock (F3) — approvisionnements manuels, retours,
 * régularisations positives et entrées automatiques depuis les bordereaux
 * de livraison du module Achat.
 */
class EntreeStockService
{
    use GeneratesDocumentNumbers;

    private const TYPES_ENTREE = ['ENTREE', 'REGULARISATION_PLUS'];

    public function __construct(
        protected FifoService $fifoService,
        protected AchatIntegrationInterface $achat,
    ) {}

    /**
     * Primitive unique d'entrée en stock (RG-F3-01→07).
     *
     * @param array{
     *     type_mouvement?:string, article_id:int, magasin_id:int, quantite:int,
     *     cout_unitaire:float|int|string, type_origine?:string, origine_id?:?int,
     *     reference_document?:?string, motif?:?string, date_entree?:?string
     * } $donnees
     *
     * @throws RegleMetierException
     */
    public function enregistrerEntree(array $donnees, int $userId): StockMouvement
    {
        $type = $donnees['type_mouvement'] ?? 'ENTREE';
        $origine = $donnees['type_origine'] ?? 'MANUEL';
        $quantite = (int) $donnees['quantite'];
        $coutUnitaire = isset($donnees['cout_unitaire']) ? (float) $donnees['cout_unitaire'] : null;

        if (! in_array($type, self::TYPES_ENTREE, true)) {
            throw new RegleMetierException("Type de mouvement {$type} invalide pour une entrée.");
        }

        // RG-F3-07
        if ($quantite <= 0) {
            throw new RegleMetierException('La quantité doit être strictement positive.');
        }

        // RG-F3-02
        if ($coutUnitaire === null || $coutUnitaire < 0) {
            throw new RegleMetierException('Le coût unitaire est obligatoire pour une entrée de stock.');
        }

        // RG-F3-03
        if ($type === 'REGULARISATION_PLUS' && blank($donnees['motif'] ?? null)) {
            throw new RegleMetierException('Le motif est obligatoire pour une régularisation.');
        }

        $magasin = Magasin::find($donnees['magasin_id']);

        if (! $magasin) {
            throw new RegleMetierException('Le magasin sélectionné est introuvable.');
        }

        // RG-F1-03 / RG-F3-01
        if (! $magasin->estActif()) {
            throw new RegleMetierException("Le magasin {$magasin->libelle} est inactif : aucun mouvement n'est autorisé.");
        }

        if (! $this->achat->estStockable((int) $donnees['article_id'])) {
            throw new RegleMetierException("Cet article n'alimente pas le stock physique.");
        }

        // RG-F3-04 — mouvement + lot + projection dans une transaction unique.
        return DB::transaction(function () use ($donnees, $type, $origine, $quantite, $coutUnitaire, $magasin, $userId) {
            $mouvement = StockMouvement::create([
                'numero_mouvement' => $this->genererNumero('BE', 'BE'),
                'type_mouvement' => $type,
                'article_id' => $donnees['article_id'],
                'magasin_id' => $magasin->id,
                'quantite' => $quantite,
                'cout_unitaire' => $coutUnitaire,
                'type_origine' => $origine,
                'origine_id' => $donnees['origine_id'] ?? null,
                'reference_document' => $donnees['reference_document'] ?? null,
                'motif' => $donnees['motif'] ?? null,
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            StockLot::create([
                'article_id' => $donnees['article_id'],
                'magasin_id' => $magasin->id,
                'quantite_initiale' => $quantite,
                'quantite_restante' => $quantite,
                'cout_unitaire' => $coutUnitaire,
                'date_entree' => $donnees['date_entree'] ?? now()->toDateString(),
                'mouvement_id' => $mouvement->id,
            ]);

            $this->actualiserProjection((int) $donnees['article_id'], $magasin->id, entree: true);

            activity()->performedOn($mouvement)->causedBy(User::find($userId))
                ->log("Entrée de stock {$mouvement->numero_mouvement} ({$type}, {$quantite} unité(s))");

            return $mouvement;
        });
    }

    /**
     * RG-F3-05 / RG-F3-06 — Suppression d'une entrée manuelle récente.
     *
     * L'habilitation (stock.entrees.admin) est contrôlée par l'appelant.
     *
     * @throws RegleMetierException
     */
    public function supprimer(StockMouvement $mouvement, int $userId): void
    {
        if (! in_array($mouvement->type_mouvement, self::TYPES_ENTREE, true)) {
            throw new RegleMetierException('Seule une entrée de stock peut être supprimée par cet écran.');
        }

        // RG-F3-05
        if ($mouvement->provientDunBordereau()) {
            throw new RegleMetierException('Une entrée issue d\'un bordereau de livraison ne peut pas être supprimée.');
        }

        // RG-F3-06
        if ($mouvement->created_at->diffInHours(now()) >= 24) {
            throw new RegleMetierException('Une entrée ne peut être supprimée que dans les 24 heures suivant sa création.');
        }

        DB::transaction(function () use ($mouvement, $userId) {
            $lot = StockLot::where('mouvement_id', $mouvement->id)->lockForUpdate()->first();

            if (! $lot || ! $lot->estIntact()) {
                throw new RegleMetierException('Le lot de cette entrée a déjà été consommé : suppression impossible.');
            }

            $lot->delete();
            $this->actualiserProjection($mouvement->article_id, $mouvement->magasin_id, entree: false);
            $mouvement->delete();

            activity()->performedOn($mouvement)->causedBy(User::find($userId))
                ->log("Entrée de stock {$mouvement->numero_mouvement} supprimée");
        });
    }

    /**
     * Recalcule la projection stock_articles_magasin depuis les lots
     * (RG-F2-05), en la créant au besoin (initialisation automatique).
     */
    protected function actualiserProjection(int $articleId, int $magasinId, bool $entree): void
    {
        $stockArticle = StockArticleMagasin::firstOrCreate(
            ['article_id' => $articleId, 'magasin_id' => $magasinId]
        );

        $quantite = (int) StockLot::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->sum('quantite_restante');

        $stockArticle->quantite_actuelle = $quantite;
        $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasinId, $articleId);

        if ($entree) {
            $stockArticle->derniere_entree_at = now();
        }

        $stockArticle->save();
    }
}
