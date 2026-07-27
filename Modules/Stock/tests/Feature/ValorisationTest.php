<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockSnapshot;
use Modules\Stock\Services\EntreeStockService;
use Modules\Stock\Services\RecalculFifoService;
use Modules\Stock\Services\SnapshotService;

class ValorisationTest extends StockTestCase
{
    public function test_l_ecran_de_valorisation_s_affiche(): void
    {
        $this->get(route('stock.valorisation.index'))->assertOk();
    }

    public function test_snapshot_manuel_photographie_tous_les_stocks(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 4,
            'cout_unitaire' => 250,
        ], $this->utilisateur->id);

        $this->postJson(route('stock.valorisation.snapshots.store'))->assertOk();

        $snapshot = StockSnapshot::first();
        // RG-F7-05
        $this->assertSame(now()->format('Y-m').'-MANUEL-1', $snapshot->reference);
        $this->assertSame(1000.0, (float) $snapshot->valeur_totale_globale);
        $this->assertDatabaseHas('stock_snapshot_lignes', [
            'snapshot_id' => $snapshot->id,
            'article_id' => $article->id,
            'quantite' => 4,
            'valeur_fifo' => 1000,
        ]);
    }

    public function test_le_snapshot_mensuel_est_idempotent(): void
    {
        // RG-F7-04
        app(SnapshotService::class)->creer('MENSUEL');
        $this->assertSame(now()->format('Y-m'), StockSnapshot::first()->reference);

        // RG-F7-03 — immuable : la même référence ne peut pas être recréée.
        $this->artisan('stock:snapshot-mensuel')->assertSuccessful();
        $this->assertSame(1, StockSnapshot::count());
    }

    public function test_le_recalcul_realigne_les_projections_sur_les_lots(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 10,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);

        // Projection volontairement faussée.
        StockArticleMagasin::query()->update(['quantite_actuelle' => 99, 'valeur_stock_fifo' => 1]);

        $resultat = app(RecalculFifoService::class)->recalculerTout();

        $this->assertSame(1, $resultat['corrigees']);
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $article->id,
            'quantite_actuelle' => 10,
            'valeur_stock_fifo' => 1000,
        ]);
    }

    public function test_le_snapshot_mensuel_est_planifie(): void
    {
        // RG-F7-06
        $planifie = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->contains(fn ($event) => str_contains($event->command ?? '', 'stock:snapshot-mensuel'));

        $this->assertTrue($planifie);
    }
}
