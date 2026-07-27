<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\EntreeStockService;

class EntreeStockTest extends StockTestCase
{
    public function test_l_ecran_des_entrees_s_affiche(): void
    {
        $this->get(route('stock.entrees.index'))->assertOk();
    }

    public function test_une_entree_cree_mouvement_lot_et_projection(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        $this->postJson(route('stock.entrees.store'), [
            'type_mouvement' => 'ENTREE',
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 10,
            'cout_unitaire' => 2500,
        ])->assertOk()->assertJson(['success' => true]);

        // RG-F3-04 — les trois écritures sont présentes et cohérentes.
        $this->assertDatabaseHas('stock_mouvements', [
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'type_mouvement' => 'ENTREE',
            'quantite' => 10,
        ]);
        $this->assertDatabaseHas('stock_lots', [
            'article_id' => $article->id,
            'quantite_initiale' => 10,
            'quantite_restante' => 10,
        ]);
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite_actuelle' => 10,
            'valeur_stock_fifo' => 25000,
        ]);
    }

    public function test_le_numero_suit_le_format_be_annee_sequence(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        $mouvement = app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 1,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);

        $this->assertSame('BE-'.date('Y').'-0001', $mouvement->numero_mouvement);
    }

    public function test_entree_refusee_sur_magasin_inactif(): void
    {
        $magasin = $this->creerMagasin(['statut' => 'inactif']);
        $article = $this->creerConsommable();

        // RG-F3-01
        $this->postJson(route('stock.entrees.store'), [
            'type_mouvement' => 'ENTREE',
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 100,
        ])->assertStatus(422);

        $this->assertDatabaseCount('stock_mouvements', 0);
    }

    public function test_regularisation_sans_motif_refusee(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        // RG-F3-03
        $this->postJson(route('stock.entrees.store'), [
            'type_mouvement' => 'REGULARISATION_PLUS',
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 100,
        ])->assertStatus(422);
    }

    public function test_un_equipement_n_alimente_pas_le_stock(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerEquipement();

        // AN-12 — seuls les types de config('achat.types_avec_stock') entrent.
        $this->postJson(route('stock.entrees.store'), [
            'type_mouvement' => 'ENTREE',
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 1,
            'cout_unitaire' => 100,
        ])->assertStatus(422);
    }

    public function test_suppression_d_une_entree_manuelle_recente(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        $mouvement = app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 4,
            'cout_unitaire' => 500,
        ], $this->utilisateur->id);

        $this->deleteJson(route('stock.entrees.destroy', $mouvement))->assertOk();

        $this->assertSoftDeleted('stock_mouvements', ['id' => $mouvement->id]);
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $article->id,
            'quantite_actuelle' => 0,
        ]);
    }

    public function test_une_entree_issue_d_un_bl_est_intouchable(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        $mouvement = app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 4,
            'cout_unitaire' => 500,
            'type_origine' => 'BL',
            'origine_id' => 1,
        ], $this->utilisateur->id);

        // RG-F3-05
        $this->deleteJson(route('stock.entrees.destroy', $mouvement))->assertStatus(422);
        $this->assertNull($mouvement->refresh()->deleted_at);
    }

    public function test_une_entree_au_lot_entame_est_intouchable(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        $mouvement = app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 4,
            'cout_unitaire' => 500,
        ], $this->utilisateur->id);

        StockLot::where('mouvement_id', $mouvement->id)->decrement('quantite_restante');

        $this->deleteJson(route('stock.entrees.destroy', $mouvement))->assertStatus(422);
    }

    public function test_initialisation_d_un_article_en_stock(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        $this->postJson(route('stock.articles.initialiser'), [
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite_initiale' => 12,
            'cout_unitaire' => 1000,
        ])->assertOk();

        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite_actuelle' => 12,
        ]);

        // RG-F2-01 — une seconde initialisation est refusée.
        $this->postJson(route('stock.articles.initialiser'), [
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite_initiale' => 1,
            'cout_unitaire' => 1000,
        ])->assertStatus(422);
    }

    public function test_initialisation_avec_quantite_sans_cout_refusee(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable();

        // RG-F2-03
        $this->postJson(route('stock.articles.initialiser'), [
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite_initiale' => 5,
        ])->assertStatus(422);
    }

    public function test_l_ecran_stock_par_article_s_affiche_et_alerte(): void
    {
        $magasin = $this->creerMagasin();
        $article = $this->creerConsommable(['seuil_alerte' => 10]);

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);

        $this->get(route('stock.articles.index'))->assertOk();

        $reponse = $this->getJson(route('stock.articles.data'))->assertOk()->json();

        $this->assertSame(1, $reponse['total']);
        $this->assertSame('ALERTE', $reponse['rows'][0]['statut_alerte']);
    }
}
