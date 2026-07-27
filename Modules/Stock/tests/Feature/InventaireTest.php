<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockInventaire;
use Modules\Stock\Services\EntreeStockService;
use Modules\Stock\Services\InventaireService;

class InventaireTest extends StockTestCase
{
    protected Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->magasin = $this->creerMagasin();
    }

    protected function ouvrirInventaire(array $articles): StockInventaire
    {
        foreach ($articles as [$article, $quantite, $cout]) {
            app(EntreeStockService::class)->enregistrerEntree([
                'article_id' => $article->id,
                'magasin_id' => $this->magasin->id,
                'quantite' => $quantite,
                'cout_unitaire' => $cout,
            ], $this->utilisateur->id);
        }

        return app(InventaireService::class)->creer($this->magasin->id, null, $this->utilisateur->id);
    }

    public function test_l_ecran_des_inventaires_s_affiche(): void
    {
        $this->get(route('stock.inventaires.index'))->assertOk();
    }

    public function test_ouverture_fige_le_stock_theorique(): void
    {
        $article = $this->creerConsommable();
        $inventaire = $this->ouvrirInventaire([[$article, 10, 100]]);

        $this->assertSame('INV-'.date('Y').'-0001', $inventaire->numero_inventaire);
        $this->assertDatabaseHas('stock_inventaire_lignes', [
            'inventaire_id' => $inventaire->id,
            'article_id' => $article->id,
            'quantite_theorique' => 10,
        ]);

        // RG-F6-02 — une entrée postérieure ne modifie pas le théorique figé.
        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);

        $this->assertSame(10, $inventaire->lignes()->first()->quantite_theorique);
    }

    public function test_un_seul_inventaire_en_cours_par_magasin(): void
    {
        $article = $this->creerConsommable();
        $this->ouvrirInventaire([[$article, 10, 100]]);

        // RG-F6-01
        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
        ])->assertStatus(422);
    }

    public function test_validation_bloquee_si_lignes_non_comptees(): void
    {
        $article = $this->creerConsommable();
        $inventaire = $this->ouvrirInventaire([[$article, 10, 100]]);

        // RG-F6-04
        $this->postJson(route('stock.inventaires.valider', $inventaire))->assertStatus(422);
        $this->assertSame('EN_COURS', $inventaire->refresh()->statut);
    }

    public function test_validation_regularise_les_ecarts(): void
    {
        $articlePlus = $this->creerConsommable();
        $articleMoins = $this->creerConsommable();
        $articleJuste = $this->creerConsommable();

        $inventaire = $this->ouvrirInventaire([
            [$articlePlus, 10, 100],
            [$articleMoins, 10, 200],
            [$articleJuste, 10, 300],
        ]);

        $lignes = $inventaire->lignes()->get()->keyBy('article_id');

        $this->postJson(route('stock.inventaires.lignes', $inventaire), [
            'comptages' => [
                $lignes[$articlePlus->id]->id => 13,
                $lignes[$articleMoins->id]->id => 6,
                $lignes[$articleJuste->id]->id => 10,
            ],
        ])->assertOk();

        // RG-F6-03 — la saisie seule ne produit aucun mouvement.
        $this->assertDatabaseCount('stock_mouvements', 3);

        $this->postJson(route('stock.inventaires.valider', $inventaire))->assertOk();

        $inventaire->refresh();
        $this->assertSame('CLOTURE', $inventaire->statut);
        $this->assertSame(2, $inventaire->nombre_ecarts);

        // RG-F6-05 — écart positif : lot au coût de référence.
        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'INVENTAIRE_PLUS',
            'article_id' => $articlePlus->id,
            'quantite' => 3,
            'cout_unitaire' => 100,
        ]);
        // RG-F6-06 — écart négatif : consommation FIFO.
        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'INVENTAIRE_MOINS',
            'article_id' => $articleMoins->id,
            'quantite' => 4,
        ]);
        // RG-F6-07 — aucun mouvement pour un écart nul.
        $this->assertDatabaseMissing('stock_mouvements', [
            'type_origine' => 'INVENTAIRE',
            'article_id' => $articleJuste->id,
        ]);

        // Projections réalignées.
        $this->assertSame(13, StockArticleMagasin::where('article_id', $articlePlus->id)->value('quantite_actuelle'));
        $this->assertSame(6, StockArticleMagasin::where('article_id', $articleMoins->id)->value('quantite_actuelle'));

        // Un inventaire clôturé est terminal.
        $this->postJson(route('stock.inventaires.valider', $inventaire))->assertStatus(422);
    }

    public function test_annulation_sans_mouvement(): void
    {
        $article = $this->creerConsommable();
        $inventaire = $this->ouvrirInventaire([[$article, 10, 100]]);

        $this->postJson(route('stock.inventaires.annuler', $inventaire))->assertOk();

        $this->assertSame('ANNULE', $inventaire->refresh()->statut);
        $this->assertDatabaseMissing('stock_mouvements', ['type_origine' => 'INVENTAIRE']);
    }
}
