<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\Article;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeConsommable;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\StockArticleService;
use Tests\TestCase;

class SortieTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Magasin $magasin;

    protected Article $article;

    protected Employe $employe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employe = Employe::factory()->create();

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

    public function test_can_access_sorties_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('stock.sorties.index'));
        $response->assertStatus(200);
        $response->assertViewIs('stock::sorties.index');
    }

    public function test_can_get_sorties_data(): void
    {
        // Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 10, 150.00);

        // Create a movement
        StockMouvement::create([
            'type_mouvement' => 'SORTIE',
            'article_id' => $this->article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 2,
            'cout_unitaire' => 150.00,
            'type_origine' => 'MANUEL',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('stock.sorties.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_can_create_sortie_for_equipement_and_routes_affectation(): void
    {
        // 1. Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 5, 800.00);

        // 2. Create physical Equipement in ParcInfo
        $equipement = Equipement::create([
            'categorie_id' => $this->article->categorie_equipement_id,
            'code_inventaire' => 'INV-DELL-001',
            'numero_serie' => 'SN-DELL-12345',
            'marque_id' => $this->article->marque_id,
            'modele' => 'Latitude 5490',
            'statut' => 'en_stock_magasin',
            'etat' => 'BON',
        ]);

        // 3. Post release request
        $data = [
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 1,
            'type_affectation_parcinfo' => 'EQUIPEMENT',
            'type_cible' => 'EMPLOYE',
            'cible_id' => $this->employe->id,
            'equipement_id' => $equipement->id,
            'motif' => 'Affectation a un nouvel employe',
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.sorties.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // 4. Assert stock decremented
        $this->assertDatabaseHas('stock_articles_magasin', [
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite_actuelle' => 4,
            'valeur_stock_fifo' => 3200.00,
        ]);

        // Assert movement and affectation logged
        $this->assertDatabaseHas('stock_mouvements', [
            'type_mouvement' => 'SORTIE',
            'article_id' => $this->article->id,
            'quantite' => 1,
        ]);

        // Assert Equipement status updated in ParcInfo
        $this->assertDatabaseHas('parc_info_equipements', [
            'id' => $equipement->id,
            'statut' => 'en_service',
        ]);

        // Assert AffectationEquipement created
        $this->assertDatabaseHas('parc_info_affectation_equipements', [
            'equipement_id' => $equipement->id,
            'statut' => true,
            'type_cible' => 'EMPLOYE',
            'dossier_employe_id' => $this->employe->id,
        ]);
    }

    public function test_can_create_sortie_for_consommable_and_updates_parcinfo_stock(): void
    {
        // 1. Initialize stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 20, 10.00);

        // 2. Create Consommable in ParcInfo matching the article code
        $fournisseur = \Modules\ParcInfo\Models\Fournisseur::create([
            'code' => 'FOUR-DELL',
            'nom' => 'Dell France',
            'est_actif' => true,
        ]);

        $typeCons = TypeConsommable::create([
            'code' => 'GEN-CONS',
            'nom' => 'Consommables Divers',
            'categorie' => 'Accessoires',
            'unite_stock' => 'Unité',
            'seul_reapprovisionnement' => 5,
        ]);

        $consommable = Consommable::create([
            'code' => $this->article->code_article,
            'nom' => $this->article->designation,
            'type_consommable_id' => $typeCons->id,
            'marque_id' => $this->article->marque_id,
            'cout_unitaire' => 10.00,
            'quantite_stock_actuel' => 50,
            'quantite_stock_min' => 5,
            'est_actif' => true,
            'fournisseur_principal_id' => $fournisseur->id,
        ]);

        // Create target Equipement in ParcInfo (since consumable affectation requires equipement_id NOT NULL)
        $eqTarget = Equipement::create([
            'categorie_id' => $this->article->categorie_equipement_id,
            'code_inventaire' => 'INV-TARGET-001',
            'numero_serie' => 'SN-TARGET-999',
            'marque_id' => $this->article->marque_id,
            'modele' => 'Latitude 5490 Target',
            'statut' => 'en_service',
            'etat' => 'BON',
        ]);

        // 3. Post release request
        $data = [
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
            'type_affectation_parcinfo' => 'CONSOMMABLE',
            'type_cible' => 'SERVICE',
            'cible_id' => 1,
            'equipement_id' => $eqTarget->id,
            'motif' => 'Ravitaillement imprimantes',
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.sorties.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // 4. Assert stock decremented
        $this->assertDatabaseHas('stock_articles_magasin', [
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite_actuelle' => 15,
        ]);

        // Assert ParcInfo Consommable stock updated
        $this->assertDatabaseHas('parc_info_consommables', [
            'id' => $consommable->id,
            'quantite_stock_actuel' => 45, // 50 - 5
        ]);

        // Assert AffectationConsommable created
        $this->assertDatabaseHas('parc_info_affectations_consommables', [
            'consommable_id' => $consommable->id,
            'quantite_fournie' => 5,
        ]);
    }
}
