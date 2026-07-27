<?php

namespace Modules\Stock\Services;

use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockMouvement;

/**
 * Stock par article et magasin (F2) — initialisation et consultation.
 *
 * Le seuil d'alerte est global et porté par achat_articles.seuil_alerte ;
 * il s'édite dans le module Achat, Stock ne fait que le lire.
 */
class StockArticleService
{
    public function __construct(
        protected AchatIntegrationInterface $achat,
        protected EntreeStockService $entreeStockService,
    ) {}

    /**
     * RG-F2-01→03 — Initialise un article dans un magasin, avec un stock de
     * départ optionnel enregistré comme régularisation positive (lot FIFO).
     *
     * @throws RegleMetierException
     */
    public function initialiser(array $donnees, int $userId): StockArticleMagasin
    {
        $articleId = (int) $donnees['article_id'];
        $magasin = Magasin::find($donnees['magasin_id']);

        if (! $magasin) {
            throw new RegleMetierException('Le magasin sélectionné est introuvable.');
        }

        // RG-F2-02
        if (! $magasin->estActif()) {
            throw new RegleMetierException("Le magasin {$magasin->libelle} est inactif : initialisation impossible.");
        }

        if (! $this->achat->estStockable($articleId)) {
            throw new RegleMetierException("Cet article n'alimente pas le stock physique.");
        }

        // RG-F2-01
        $existe = StockArticleMagasin::where('article_id', $articleId)
            ->where('magasin_id', $magasin->id)
            ->exists();

        if ($existe) {
            throw new RegleMetierException('Cet article est déjà initialisé dans ce magasin.');
        }

        $quantite = (int) ($donnees['quantite_initiale'] ?? 0);

        if ($quantite > 0) {
            // RG-F2-03 — le coût est obligatoire dès qu'un lot FIFO est créé.
            $this->entreeStockService->enregistrerEntree([
                'type_mouvement' => 'REGULARISATION_PLUS',
                'article_id' => $articleId,
                'magasin_id' => $magasin->id,
                'quantite' => $quantite,
                'cout_unitaire' => $donnees['cout_unitaire'] ?? null,
                'motif' => 'Initialisation du stock',
            ], $userId);

            return StockArticleMagasin::where('article_id', $articleId)
                ->where('magasin_id', $magasin->id)
                ->firstOrFail();
        }

        $stockArticle = StockArticleMagasin::create([
            'article_id' => $articleId,
            'magasin_id' => $magasin->id,
        ]);

        activity()->performedOn($stockArticle)->log('Article initialisé en stock (quantité nulle)');

        return $stockArticle;
    }
}
