<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Contracts\GrhIntegrationInterface;
use Modules\Stock\Contracts\OrganisationIntegrationInterface;
use Modules\Stock\Contracts\ParcInfoIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Models\StockMouvementAffectation;
use Modules\Stock\Traits\GeneratesDocumentNumbers;

/**
 * Sorties de stock (F4) — consommation FIFO avec affectation ParcInfo
 * obligatoire, et régularisations négatives.
 */
class SortieStockService
{
    use GeneratesDocumentNumbers;

    public function __construct(
        protected FifoService $fifoService,
        protected AchatIntegrationInterface $achat,
        protected ParcInfoIntegrationInterface $parcInfo,
        protected GrhIntegrationInterface $grh,
        protected OrganisationIntegrationInterface $organisation,
    ) {}

    /**
     * RG-F4-01→06 — Sortie avec affectation ParcInfo routée par type d'article.
     *
     * @param array{
     *     article_id:int, magasin_id:int, quantite:int,
     *     type_cible:string, cible_id:int, motif?:?string,
     *     reference_document?:?string, equipement_id?:?int, licence_id?:?int
     * } $donnees
     *
     * @throws RegleMetierException
     */
    public function creer(array $donnees, int $userId): StockMouvement
    {
        $magasin = $this->magasinOperable($donnees['magasin_id']);
        $article = $this->achat->article((int) $donnees['article_id']);

        if (! $article) {
            throw new RegleMetierException('Article introuvable.');
        }

        $quantite = (int) $donnees['quantite'];

        if ($quantite <= 0) {
            throw new RegleMetierException('La quantité doit être strictement positive.');
        }

        $this->controlerCible($donnees['type_cible'], (int) $donnees['cible_id']);

        // RG-F4-01 — le disponible est contrôlé avant d'ouvrir la transaction.
        $disponible = (int) StockArticleMagasin::where('article_id', $article['id'])
            ->where('magasin_id', $magasin->id)
            ->value('quantite_actuelle');

        if ($disponible < $quantite) {
            throw new RegleMetierException(
                "Stock insuffisant : {$disponible} unité(s) disponible(s) pour {$quantite} demandée(s)."
            );
        }

        // RG-F4-02/03 — sortie + FIFO + affectation dans une transaction unique.
        return DB::transaction(function () use ($donnees, $article, $magasin, $quantite, $userId) {
            $fifo = $this->fifoService->consommerLots($article['id'], $magasin->id, $quantite);
            $coutUnitaireMoyen = $quantite > 0 ? round($fifo['cout_total'] / $quantite, 4) : 0;

            $mouvement = StockMouvement::create([
                'numero_mouvement' => $this->genererNumero('BS', 'BS'),
                'type_mouvement' => 'SORTIE',
                'article_id' => $article['id'],
                'magasin_id' => $magasin->id,
                'quantite' => $quantite,
                'cout_unitaire' => $coutUnitaireMoyen,
                'type_origine' => 'MANUEL',
                'reference_document' => $donnees['reference_document'] ?? null,
                'motif' => $donnees['motif'] ?? null,
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            $affectationId = $this->routerAffectation($article, $donnees, $quantite, $userId);
            $typeAffectation = $this->typeAffectation($article['type_article']);

            if ($typeAffectation !== null) {
                StockMouvementAffectation::create([
                    'mouvement_id' => $mouvement->id,
                    'type_affectation_parcinfo' => $typeAffectation,
                    'affectation_id' => $affectationId,
                    'type_cible' => $donnees['type_cible'],
                    'cible_id' => $donnees['cible_id'],
                ]);
            }

            $this->actualiserProjection($article['id'], $magasin->id);

            activity()->performedOn($mouvement)->causedBy(User::find($userId))
                ->log("Sortie de stock {$mouvement->numero_mouvement} ({$quantite} unité(s))");

            return $mouvement;
        });
    }

    /**
     * RG-F4-04 — Régularisation négative : motif obligatoire, aucune
     * affectation. L'habilitation (stock.sorties.admin) relève de l'appelant.
     *
     * @throws RegleMetierException
     */
    public function creerRegularisation(array $donnees, int $userId): StockMouvement
    {
        $magasin = $this->magasinOperable($donnees['magasin_id']);
        $quantite = (int) $donnees['quantite'];

        if ($quantite <= 0) {
            throw new RegleMetierException('La quantité doit être strictement positive.');
        }

        if (blank($donnees['motif'] ?? null)) {
            throw new RegleMetierException('Le motif est obligatoire pour une régularisation.');
        }

        return DB::transaction(function () use ($donnees, $magasin, $quantite, $userId) {
            $this->fifoService->consommerLots((int) $donnees['article_id'], $magasin->id, $quantite);

            $mouvement = StockMouvement::create([
                'numero_mouvement' => $this->genererNumero('BS', 'BS'),
                'type_mouvement' => 'REGULARISATION_MOINS',
                'article_id' => $donnees['article_id'],
                'magasin_id' => $magasin->id,
                'quantite' => $quantite,
                'type_origine' => 'MANUEL',
                'motif' => $donnees['motif'],
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            $this->actualiserProjection((int) $donnees['article_id'], $magasin->id);

            activity()->performedOn($mouvement)->causedBy(User::find($userId))
                ->log("Régularisation négative {$mouvement->numero_mouvement} ({$quantite} unité(s))");

            return $mouvement;
        });
    }

    // ── Internes ───────────────────────────────────────────────────────────

    protected function magasinOperable(int $magasinId): Magasin
    {
        $magasin = Magasin::find($magasinId);

        if (! $magasin) {
            throw new RegleMetierException('Le magasin sélectionné est introuvable.');
        }

        // RG-F1-03 / RG-F4-05
        if (! $magasin->estActif()) {
            throw new RegleMetierException("Le magasin {$magasin->libelle} est inactif : aucun mouvement n'est autorisé.");
        }

        return $magasin;
    }

    protected function controlerCible(string $typeCible, int $cibleId): void
    {
        $existe = $typeCible === 'EMPLOYE'
            ? $this->grh->employeExiste($cibleId)
            : $this->organisation->cibleExiste($typeCible, $cibleId);

        if (! $existe) {
            throw new RegleMetierException('La cible de l\'affectation est introuvable.');
        }
    }

    /** Routage F4 : equipement → affectation, consommable → traçage, licence → affectation, prestation → rien. */
    protected function routerAffectation(array $article, array $donnees, int $quantite, int $userId): int
    {
        return match ($this->typeAffectation($article['type_article'])) {
            'EQUIPEMENT' => $this->parcInfo->affecterEquipement([
                'equipement_id' => (int) ($donnees['equipement_id'] ?? 0),
                'type_cible' => $donnees['type_cible'],
                'cible_id' => $donnees['cible_id'],
                'motif' => $donnees['motif'] ?? null,
                'user_id' => $userId,
            ]),
            'CONSOMMABLE' => $this->parcInfo->tracerConsommation([
                'code_article' => $article['code_article'],
                'designation' => $article['designation'],
                'quantite' => $quantite,
                'type_cible' => $donnees['type_cible'],
                'cible_id' => $donnees['cible_id'],
                'motif' => $donnees['motif'] ?? null,
                'user_id' => $userId,
                'reference_document' => $donnees['reference_document'] ?? null,
            ]),
            'LICENCE' => $this->parcInfo->affecterLicence([
                'licence_id' => (int) ($donnees['licence_id'] ?? 0),
                'type_cible' => $donnees['type_cible'],
                'cible_id' => $donnees['cible_id'],
                'motif' => $donnees['motif'] ?? null,
                'user_id' => $userId,
            ]),
            default => 0,
        };
    }

    protected function typeAffectation(string $typeArticle): ?string
    {
        return match ($typeArticle) {
            'equipement' => 'EQUIPEMENT',
            'consommable' => 'CONSOMMABLE',
            'licence' => 'LICENCE',
            default => null,
        };
    }

    /** Recalcule la projection stock_articles_magasin depuis les lots. */
    protected function actualiserProjection(int $articleId, int $magasinId): void
    {
        $stockArticle = StockArticleMagasin::firstOrCreate(
            ['article_id' => $articleId, 'magasin_id' => $magasinId]
        );

        $stockArticle->quantite_actuelle = (int) StockLot::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->sum('quantite_restante');
        $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasinId, $articleId);
        $stockArticle->derniere_sortie_at = now();
        $stockArticle->save();
    }
}
