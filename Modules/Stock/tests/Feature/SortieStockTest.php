<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Grh\Models\Employe;
use Modules\Stock\Contracts\ParcInfoIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Services\EntreeStockService;

class SortieStockTest extends StockTestCase
{
    protected Magasin $magasin;

    protected Employe $employe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->magasin = $this->creerMagasin();
        $this->employe = Employe::factory()->create();
    }

    protected function approvisionner(int $articleId, int $quantite = 10, float $cout = 100): void
    {
        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $articleId,
            'magasin_id' => $this->magasin->id,
            'quantite' => $quantite,
            'cout_unitaire' => $cout,
        ], $this->utilisateur->id);
    }

    public function test_l_ecran_des_sorties_s_affiche(): void
    {
        $this->get(route('stock.sorties.index'))->assertOk();
    }

    public function test_une_sortie_consomme_le_fifo_et_trace_l_affectation(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 10, 100);

        $this->postJson(route('stock.sorties.store'), [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 4,
            'type_cible' => 'EMPLOYE',
            'cible_id' => $this->employe->id,
            'motif' => 'Dotation bureau',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'SORTIE',
            'article_id' => $article->id,
            'quantite' => 4,
            'cout_unitaire' => 100,
        ]);
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $article->id,
            'quantite_actuelle' => 6,
            'valeur_stock_fifo' => 600,
        ]);
        // Trace de l'affectation côté Stock.
        $this->assertDatabaseHas('stock_mouvements_affectations', [
            'type_affectation_parcinfo' => 'CONSOMMABLE',
            'type_cible' => 'EMPLOYE',
            'cible_id' => $this->employe->id,
        ]);
        // Trace côté ParcInfo (mouvement de consommation, sans compteur).
        $this->assertDatabaseHas('parc_info_mouvements_consommables', [
            'type_mouvement' => 'Consommation',
            'quantite' => 4,
            'employe_id' => $this->employe->id,
        ]);
    }

    public function test_sortie_refusee_si_stock_insuffisant(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 3);

        // RG-F4-01
        $this->postJson(route('stock.sorties.store'), [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 5,
            'type_cible' => 'EMPLOYE',
            'cible_id' => $this->employe->id,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('stock_mouvements', ['type_mouvement' => 'SORTIE']);
    }

    public function test_l_echec_de_l_affectation_annule_toute_la_sortie(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 10, 100);

        // RG-F4-03 — le double d'intégration ParcInfo échoue systématiquement.
        $this->app->instance(ParcInfoIntegrationInterface::class, new class implements ParcInfoIntegrationInterface
        {
            public function affecterEquipement(array $donnees): int
            {
                throw new RegleMetierException('Échec simulé.');
            }

            public function tracerConsommation(array $donnees): int
            {
                throw new RegleMetierException('Échec simulé.');
            }

            public function affecterLicence(array $donnees): int
            {
                throw new RegleMetierException('Échec simulé.');
            }
        });

        $this->postJson(route('stock.sorties.store'), [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 4,
            'type_cible' => 'EMPLOYE',
            'cible_id' => $this->employe->id,
        ])->assertStatus(422);

        // Rollback total : ni mouvement, ni consommation FIFO.
        $this->assertDatabaseMissing('stock_mouvements', ['type_mouvement' => 'SORTIE']);
        $this->assertSame(10, StockArticleMagasin::where('article_id', $article->id)->value('quantite_actuelle'));
        $this->assertSame(10, (int) \Modules\Stock\Models\StockLot::where('article_id', $article->id)->sum('quantite_restante'));
    }

    public function test_sortie_refusee_sur_magasin_inactif(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 10);
        $this->magasin->update(['statut' => 'inactif']);

        // RG-F4-05
        $this->postJson(route('stock.sorties.store'), [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 1,
            'type_cible' => 'EMPLOYE',
            'cible_id' => $this->employe->id,
        ])->assertStatus(422);
    }

    public function test_sortie_refusee_si_cible_introuvable(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 10);

        $this->postJson(route('stock.sorties.store'), [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 1,
            'type_cible' => 'SERVICE',
            'cible_id' => 999999,
        ])->assertStatus(422);
    }

    public function test_regularisation_negative_avec_motif(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 10, 100);

        // RG-F4-04
        $this->postJson(route('stock.sorties.regularisation'), [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 2,
            'motif' => 'Casse constatée en réserve',
        ])->assertOk();

        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'REGULARISATION_MOINS',
            'quantite' => 2,
        ]);
        $this->assertSame(8, StockArticleMagasin::where('article_id', $article->id)->value('quantite_actuelle'));
    }

    public function test_regularisation_sans_motif_refusee(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 10);

        $this->postJson(route('stock.sorties.regularisation'), [
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 2,
        ])->assertStatus(422);
    }
}
