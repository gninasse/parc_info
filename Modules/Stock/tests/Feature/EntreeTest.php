<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Marque;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockMouvement;
use Tests\TestCase;

class EntreeTest extends TestCase
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

        // Create article
        $marque = Marque::create(['libelle' => 'Dell']);
        $categorie = CategorieEquipement::create(['code' => 'ordinateur', 'libelle' => 'Ordinateurs']);

        $this->article = Article::create([
            'code_article' => 'ART-DELL-5490',
            'designation' => 'Dell Latitude 5490',
            'type_article' => 'equipement',
            'marque_id' => $marque->id,
            'categorie_equipement_id' => $categorie->id,
            'prix_indicatif' => 800.00,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);
    }

    public function test_can_access_entrees_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('stock.entrees.index'));
        $response->assertStatus(200);
        $response->assertViewIs('stock::entrees.index');
    }

    public function test_can_get_entrees_data(): void
    {
        StockMouvement::create([
            'type_mouvement' => 'ENTREE',
            'article_id' => $this->article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 150.00,
            'type_origine' => 'MANUEL',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('stock.entrees.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_can_create_manual_stock_entry(): void
    {
        $data = [
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'cout_unitaire' => 750.00,
            'reference_document' => 'BL-TEST-1234',
            'motif' => 'Entree test de stock',
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.entrees.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert movement logged
        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'ENTREE',
            'article_id' => $this->article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 750.00,
            'type_origine' => 'MANUEL',
            'reference_document' => 'BL-TEST-1234',
        ]);

        // Assert lot created
        $this->assertDatabaseHas('stock_lots', [
            'article_id' => $this->article->id,
            'magasin_id' => $this->magasin->id,
            'quantite_initiale' => 5,
            'quantite_restante' => 5,
            'cout_unitaire' => 750.00,
        ]);

        // Assert stock levels updated
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $this->article->id,
            'magasin_id' => $this->magasin->id,
            'quantite_actuelle' => 5,
            'valeur_stock_fifo' => 3750.00, // 5 * 750
        ]);
    }

    public function test_can_delete_manual_entry_within_24_hours(): void
    {
        // 1. Create manual entry
        $service = app(\Modules\Stock\Services\EntreeStockService::class);
        $mouvement = $service->creerEntreeManuelle(
            $this->magasin->id,
            $this->article->id,
            10,
            100.00,
            'REF-DEL',
            'Saisie manuelle',
            $this->user->id
        );

        // Assert stock has 10
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $this->article->id,
            'quantite_actuelle' => 10,
        ]);

        // 2. Delete it via HTTP DELETE
        $response = $this->actingAs($this->user)->deleteJson(route('stock.entrees.destroy', $mouvement->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert movement and lot deleted, and stock level is 0
        $this->assertSoftDeleted('stock_mouvements', ['id' => $mouvement->id]);
        $this->assertDatabaseMissing('stock_lots', ['mouvement_id' => $mouvement->id]);
        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $this->article->id,
            'quantite_actuelle' => 0,
        ]);
    }

    public function test_cannot_delete_manual_entry_older_than_24_hours(): void
    {
        $service = app(\Modules\Stock\Services\EntreeStockService::class);
        $mouvement = $service->creerEntreeManuelle(
            $this->magasin->id,
            $this->article->id,
            10,
            100.00,
            'REF-DEL',
            'Saisie manuelle',
            $this->user->id
        );

        // Force creation date to 2 days ago
        $mouvement->created_at = now()->subDays(2);
        $mouvement->save();

        // Expect 400 bad request due to 24h rule
        $response = $this->actingAs($this->user)->deleteJson(route('stock.entrees.destroy', $mouvement->id));
        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_delete_bl_automatic_entry(): void
    {
        $mouvement = StockMouvement::create([
            'type_mouvement' => 'ENTREE',
            'article_id' => $this->article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 150.00,
            'type_origine' => 'BL', // Generated automatically from BL
        ]);

        // Expect 400
        $response = $this->actingAs($this->user)->deleteJson(route('stock.entrees.destroy', $mouvement->id));
        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }
}
