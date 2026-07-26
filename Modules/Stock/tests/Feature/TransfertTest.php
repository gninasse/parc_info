<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Marque;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockTransfert;
use Modules\Stock\Services\StockArticleService;
use Tests\TestCase;

class TransfertTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Magasin $source;

    protected Magasin $dest;

    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Bypass permission checks
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        // Create stores
        $this->source = Magasin::factory()->create(['est_actif' => true, 'nom' => 'Source Store']);
        $this->dest = Magasin::factory()->create(['est_actif' => true, 'nom' => 'Destination Store']);

        // Create article
        $marque = Marque::create(['libelle' => 'Lenovo']);
        $categorie = CategorieEquipement::create(['code' => 'consommable', 'libelle' => 'Consommables']);
        $this->article = Article::create([
            'code_article' => 'ART-LEN-T490',
            'designation' => 'Lenovo ThinkPad T490',
            'type_article' => 'consommable',
            'marque_id' => $marque->id,
            'categorie_equipement_id' => $categorie->id,
            'prix_indicatif' => 1200.00,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);
    }

    public function test_can_access_transferts_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('stock.transferts.index'));
        $response->assertStatus(200);
        $response->assertViewIs('stock::transferts.index');
    }

    public function test_can_get_transferts_data(): void
    {
        // Request a transfer (requires stock first)
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->source->id, $this->article->id, 10, 100.00);

        StockTransfert::create([
            'numero_transfert' => 'TR-2026-0001',
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'statut' => 'EN_ATTENTE',
            'motif_creation' => 'Besoin urgent de stock',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('stock.transferts.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_cannot_request_transfert_with_insufficient_stock(): void
    {
        $data = [
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'motif_creation' => 'Besoin urgent',
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.transferts.store'), $data);
        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_can_request_transfert_with_sufficient_stock(): void
    {
        // Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->source->id, $this->article->id, 10, 100.00);

        $data = [
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'motif_creation' => 'Besoin urgent',
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.transferts.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_transferts', [
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'statut' => 'EN_ATTENTE',
        ]);
    }

    public function test_can_validate_transfert_performing_dual_mouvements(): void
    {
        // Initialize stock in source
        // Let's create two lots in source with different costs to verify average cost calculation!
        // Lot 1: 5 units at 100.00
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->source->id, $this->article->id, 5, 100.00);

        // Lot 2: 5 units at 120.00 (by adding a manual entry)
        $entryService = app(\Modules\Stock\Services\EntreeStockService::class);
        $entryService->creerEntreeManuelle($this->source->id, $this->article->id, 5, 120.00);

        // Total available: 10 units. Total value: 500 + 600 = 1100.00

        // Request a transfer of 7 units.
        // It should consume 5 units from Lot 1 (cost 100) and 2 units from Lot 2 (cost 120).
        // Total cost of transferred units: 5 * 100 + 2 * 120 = 740.00.
        // Average cost: 740 / 7 = 105.7143.

        $transfert = StockTransfert::create([
            'numero_transfert' => 'TR-2026-0002',
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite' => 7,
            'statut' => 'EN_ATTENTE',
            'motif_creation' => 'Besoin urgent de stock',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('stock.transferts.valider', $transfert->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert Transfert is VALIDE
        $this->assertDatabaseHas('stock_transferts', [
            'id' => $transfert->id,
            'statut' => 'VALIDE',
        ]);

        // Assert Source stock decremented
        $this->assertDatabaseHas('stock_articles_magasin', [
            'magasin_id' => $this->source->id,
            'article_id' => $this->article->id,
            'quantite_actuelle' => 3, // 10 - 7
            'valeur_stock_fifo' => 360.00, // 3 units remaining in Lot 2 (3 * 120 = 360.00)
        ]);

        // Assert Destination stock incremented with correct average cost
        $this->assertDatabaseHas('stock_articles_magasin', [
            'magasin_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite_actuelle' => 7,
            'valeur_stock_fifo' => 740.00, // 7 * 105.7143 = 740.00
        ]);

        // Assert new lot created in destination
        $this->assertDatabaseHas('stock_lots', [
            'magasin_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite_initiale' => 7,
            'quantite_restante' => 7,
            'cout_unitaire' => 105.7143,
        ]);
    }

    public function test_can_reject_transfert(): void
    {
        // Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->source->id, $this->article->id, 10, 100.00);

        $transfert = StockTransfert::create([
            'numero_transfert' => 'TR-2026-0003',
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'statut' => 'EN_ATTENTE',
            'motif_creation' => 'Besoin urgent de stock',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('stock.transferts.rejeter', $transfert->id), [
            'motif_rejet' => 'Non justifie',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_transferts', [
            'id' => $transfert->id,
            'statut' => 'REJETE',
            'motif_rejet' => 'Non justifie',
        ]);
    }

    public function test_can_cancel_transfert(): void
    {
        // Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->source->id, $this->article->id, 10, 100.00);

        $transfert = StockTransfert::create([
            'numero_transfert' => 'TR-2026-0004',
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->dest->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'statut' => 'EN_ATTENTE',
            'motif_creation' => 'Besoin urgent de stock',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('stock.transferts.annuler', $transfert->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_transferts', [
            'id' => $transfert->id,
            'statut' => 'ANNULE',
        ]);
    }
}
