<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Marque;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockSnapshot;
use Modules\Stock\Services\StockArticleService;
use Tests\TestCase;

class ValorisationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Magasin $magasin;

    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Bypass permission checks
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        // Create store
        $this->magasin = Magasin::factory()->create(['est_actif' => true]);

        // Create articles
        $marque = Marque::create(['libelle' => 'Lenovo']);
        $categorie = CategorieEquipement::create(['code' => 'ordinateur', 'libelle' => 'Ordinateurs']);

        $this->article = Article::create([
            'code_article' => 'ART-LEN-T480',
            'designation' => 'Lenovo ThinkPad T480',
            'type_article' => 'equipement',
            'marque_id' => $marque->id,
            'categorie_equipement_id' => $categorie->id,
            'prix_indicatif' => 600.00,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);
    }

    public function test_can_access_valorisation_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('stock.valorisation.index'));
        $response->assertStatus(200);
        $response->assertViewIs('stock::valorisation.index');
    }

    public function test_can_get_valorisation_data(): void
    {
        StockSnapshot::create([
            'reference' => 'VAL-2026-07-0001',
            'type' => 'PONCTUEL',
            'date_snapshot' => now(),
            'valeur_totale_globale' => 1200.00,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('stock.valorisation.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_can_generate_adhoc_snapshot_via_http(): void
    {
        // 1. Initialize stock with lots of different prices
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 5, 500.00); // 2500

        // Add a second entry of 5 at 700.00
        $entryService = app(\Modules\Stock\Services\EntreeStockService::class);
        $entryService->creerEntreeManuelle(
            $this->magasin->id,
            $this->article->id,
            5,
            700.00,
            'FAC-222',
            'Ajout lot supplementaire',
            $this->user->id
        ); // 3500. Total stock: 10, total value: 6000

        // 2. Trigger snapshot generation via HTTP store endpoint
        $response = $this->actingAs($this->user)->postJson(route('stock.valorisation.store'));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // 3. Assert database snapshot
        $this->assertDatabaseHas('stock_snapshots', [
            'type' => 'PONCTUEL',
            'valeur_totale_globale' => 6000.00,
        ]);

        $snapshot = StockSnapshot::first();

        // Assert snapshot line calculates average unit cost: 6000 / 10 = 600
        $this->assertDatabaseHas('stock_snapshot_lignes', [
            'snapshot_id' => $snapshot->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 10,
            'valeur_fifo' => 6000.00,
            'cout_unitaire_moyen' => 600.00,
        ]);
    }

    public function test_can_generate_monthly_snapshot_via_artisan_command(): void
    {
        // 1. Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 4, 150.00); // 600

        // 2. Run artisan command
        $this->artisan('stock:generate-snapshot --type=MENSUEL')
            ->assertExitCode(0);

        // 3. Assert snapshot created
        $this->assertDatabaseHas('stock_snapshots', [
            'type' => 'MENSUEL',
            'valeur_totale_globale' => 600.00,
        ]);
    }
}
