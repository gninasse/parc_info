<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Marque;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockInventaire;
use Modules\Stock\Services\InventaireService;
use Modules\Stock\Services\StockArticleService;
use Tests\TestCase;

class InventaireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Magasin $magasin;

    protected Article $article1;

    protected Article $article2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Bypass permission checks
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        // Create store
        $this->magasin = Magasin::factory()->create(['est_actif' => true]);

        // Create articles
        $marque = Marque::create(['libelle' => 'HP']);
        $categorie = CategorieEquipement::create(['code' => 'ordinateur', 'libelle' => 'Ordinateurs']);

        $this->article1 = Article::create([
            'code_article' => 'ART-1',
            'designation' => 'Article One',
            'type_article' => 'consommable',
            'marque_id' => $marque->id,
            'categorie_equipement_id' => $categorie->id,
            'prix_indicatif' => 100.00,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);

        $this->article2 = Article::create([
            'code_article' => 'ART-2',
            'designation' => 'Article Two',
            'type_article' => 'consommable',
            'marque_id' => $marque->id,
            'categorie_equipement_id' => $categorie->id,
            'prix_indicatif' => 200.00,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);
    }

    public function test_can_access_inventaires_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('stock.inventaires.index'));
        $response->assertStatus(200);
        $response->assertViewIs('stock::inventaires.index');
    }

    public function test_can_get_inventaires_data(): void
    {
        StockInventaire::create([
            'numero_inventaire' => 'INV-2026-0001',
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => now()->toDateString(),
            'statut' => 'BROUILLON',
            'nombre_articles' => 0,
            'nombre_ecarts' => 0,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('stock.inventaires.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_can_start_inventory_campaign_freezing_stock(): void
    {
        // Initialize stock for both articles
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article1->id, 10, 100.00);
        $stockService->initialiser($this->magasin->id, $this->article2->id, 20, 200.00);

        $response = $this->actingAs($this->user)->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_inventaires', [
            'magasin_id' => $this->magasin->id,
            'statut' => 'BROUILLON',
            'nombre_articles' => 2,
        ]);

        // Assert lines pre-populated with correct theoretical counts
        $this->assertDatabaseHas('stock_inventaire_lignes', [
            'article_id' => $this->article1->id,
            'quantite_theorique' => 10,
            'quantite_reelle' => 10,
            'ecart' => 0,
        ]);

        $this->assertDatabaseHas('stock_inventaire_lignes', [
            'article_id' => $this->article2->id,
            'quantite_theorique' => 20,
            'quantite_reelle' => 20,
            'ecart' => 0,
        ]);
    }

    public function test_can_input_physical_counts(): void
    {
        // Initialize stock & campaign
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article1->id, 10, 100.00);

        $inventaire = app(InventaireService::class)->creerInventaire($this->magasin->id, $this->user->id);
        $ligne = $inventaire->lignes()->first();

        // Submit counts
        $data = [
            'saisies' => [
                $ligne->id => 12, // Real count is 12 (surplus of +2)
            ],
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.inventaires.saisie.store', $inventaire->id), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_inventaire_lignes', [
            'id' => $ligne->id,
            'quantite_reelle' => 12,
            'ecart' => 2,
        ]);
    }

    public function test_can_validate_inventory_adjusting_stock(): void
    {
        // 1. Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article1->id, 10, 100.00);
        $stockService->initialiser($this->magasin->id, $this->article2->id, 20, 200.00);

        // 2. Start campaign
        $inventaireService = app(InventaireService::class);
        $inventaire = $inventaireService->creerInventaire($this->magasin->id, $this->user->id);

        $ligne1 = $inventaire->lignes()->where('article_id', $this->article1->id)->first();
        $ligne2 = $inventaire->lignes()->where('article_id', $this->article2->id)->first();

        // 3. Record counts
        // Ligne 1: 12 (surplus of +2)
        // Ligne 2: 17 (deficit of -3)
        $inventaireService->enregistrerSaisie($inventaire->id, [
            $ligne1->id => 12,
            $ligne2->id => 17,
        ]);

        // 4. Validate campaign
        $response = $this->actingAs($this->user)->postJson(route('stock.inventaires.valider', $inventaire->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // 5. Assert campaign is VALIDE
        $this->assertDatabaseHas('stock_inventaires', [
            'id' => $inventaire->id,
            'statut' => 'VALIDE',
            'nombre_ecarts' => 2,
        ]);

        // Assert Article 1 stock incremented
        $this->assertDatabaseHas('stock_articles_magasin', [
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article1->id,
            'quantite_actuelle' => 12,
            'valeur_stock_fifo' => 1200.00, // 12 * 100
        ]);

        // Assert Article 1 surplus entry movement created
        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'ENTREE',
            'type_origine' => 'INVENTAIRE',
            'origine_id' => $inventaire->id,
            'article_id' => $this->article1->id,
            'quantite' => 2,
        ]);

        // Assert Article 2 stock decremented
        $this->assertDatabaseHas('stock_articles_magasin', [
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article2->id,
            'quantite_actuelle' => 17,
            'valeur_stock_fifo' => 3400.00, // 17 * 200
        ]);

        // Assert Article 2 deficit exit movement created
        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'SORTIE',
            'type_origine' => 'INVENTAIRE',
            'origine_id' => $inventaire->id,
            'article_id' => $this->article2->id,
            'quantite' => 3,
        ]);
    }

    public function test_can_cancel_inventory_campaign(): void
    {
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article1->id, 10, 100.00);

        $inventaire = app(InventaireService::class)->creerInventaire($this->magasin->id, $this->user->id);

        $response = $this->actingAs($this->user)->postJson(route('stock.inventaires.annuler', $inventaire->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_inventaires', [
            'id' => $inventaire->id,
            'statut' => 'ANNULE',
        ]);
    }
}
