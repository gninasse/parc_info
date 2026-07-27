<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Services\EntreeStockService;
use Modules\Stock\Services\FifoService;

class FifoServiceTest extends StockTestCase
{
    public function test_les_lots_sont_consommes_dans_l_ordre_fifo(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();
        $service = app(EntreeStockService::class);

        // Trois lots à des dates et coûts différents.
        $service->enregistrerEntree([
            'article_id' => $article->id, 'magasin_id' => $magasin->id,
            'quantite' => 5, 'cout_unitaire' => 100, 'date_entree' => '2026-01-01',
        ], $this->utilisateur->id);
        $service->enregistrerEntree([
            'article_id' => $article->id, 'magasin_id' => $magasin->id,
            'quantite' => 5, 'cout_unitaire' => 200, 'date_entree' => '2026-02-01',
        ], $this->utilisateur->id);
        $service->enregistrerEntree([
            'article_id' => $article->id, 'magasin_id' => $magasin->id,
            'quantite' => 5, 'cout_unitaire' => 300, 'date_entree' => '2026-03-01',
        ], $this->utilisateur->id);

        // RG-F2-06 — 8 unités : 5 du lot à 100, 3 du lot à 200.
        $resultat = app(FifoService::class)->consommerLots($article->id, $magasin->id, 8);

        $this->assertSame(5 * 100 + 3 * 200.0, $resultat['cout_total']);
        $this->assertCount(2, $resultat['plan']);

        $restants = StockLot::where('article_id', $article->id)
            ->ordreFifo()
            ->pluck('quantite_restante')
            ->all();

        $this->assertSame([0, 2, 5], $restants);
    }

    public function test_stock_insuffisant_leve_une_exception_sans_consommer(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id, 'magasin_id' => $magasin->id,
            'quantite' => 3, 'cout_unitaire' => 100,
        ], $this->utilisateur->id);

        try {
            app(FifoService::class)->consommerLots($article->id, $magasin->id, 10);
            $this->fail('Une RegleMetierException était attendue.');
        } catch (RegleMetierException) {
            // Aucun lot n'a été entamé.
            $this->assertSame(3, (int) StockLot::sum('quantite_restante'));
        }
    }

    public function test_calculer_valeur_fifo(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();
        $service = app(EntreeStockService::class);

        $service->enregistrerEntree([
            'article_id' => $article->id, 'magasin_id' => $magasin->id,
            'quantite' => 2, 'cout_unitaire' => 150.50,
        ], $this->utilisateur->id);
        $service->enregistrerEntree([
            'article_id' => $article->id, 'magasin_id' => $magasin->id,
            'quantite' => 1, 'cout_unitaire' => 99.50,
        ], $this->utilisateur->id);

        // RG-F2-05
        $this->assertSame(2 * 150.50 + 99.50, app(FifoService::class)->calculerValeur($magasin->id, $article->id));
    }
}
