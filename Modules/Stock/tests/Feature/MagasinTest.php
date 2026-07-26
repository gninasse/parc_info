<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Stock\Models\Magasin;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MagasinTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Bypass permission checks in tests
        \Illuminate\Support\Facades\Gate::before(fn () => true);
    }

    public function test_can_access_magasins_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('stock.magasins.index'));
        $response->assertStatus(200);
        $response->assertViewIs('stock::magasins.index');
    }

    public function test_can_get_magasins_data(): void
    {
        Magasin::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson(route('stock.magasins.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_can_create_magasin(): void
    {
        $data = [
            'code' => 'MAG-TEST',
            'nom' => 'Magasin de Test',
            'type' => 'TECHNIQUE',
            'description' => 'Un magasin de test',
            'est_actif' => 1,
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.magasins.store'), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_magasins', [
            'code' => 'MAG-TEST',
            'nom' => 'Magasin de Test',
        ]);
    }

    public function test_can_update_magasin(): void
    {
        $magasin = Magasin::factory()->create([
            'code' => 'MAG-EDIT',
            'nom' => 'Nom Original',
        ]);

        $data = [
            'code' => 'MAG-EDIT',
            'nom' => 'Nom Modifie',
            'type' => 'CONSOMMABLE',
            'est_actif' => 1,
        ];

        $response = $this->actingAs($this->user)->putJson(route('stock.magasins.update', $magasin->id), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_magasins', [
            'id' => $magasin->id,
            'nom' => 'Nom Modifie',
            'type' => 'CONSOMMABLE',
        ]);
    }

    public function test_can_delete_magasin(): void
    {
        $magasin = Magasin::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson(route('stock.magasins.destroy', $magasin->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('stock_magasins', [
            'id' => $magasin->id,
        ]);
    }

    public function test_can_add_responsible(): void
    {
        $magasin = Magasin::factory()->create();
        $employe = Employe::factory()->create();

        $data = [
            'employe_id' => $employe->id,
            'role' => 'principal',
            'date_debut' => '2026-07-13',
            'date_fin' => '2026-07-20',
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.magasins.responsables.store', $magasin->id), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_responsables_magasin', [
            'magasin_id' => $magasin->id,
            'employe_id' => $employe->id,
            'role' => 'principal',
        ]);
    }

    public function test_can_add_rights(): void
    {
        $magasin = Magasin::factory()->create();
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $data = [
            'type_sujet' => 'ROLE',
            'sujet_id' => $role->id,
            'peut_lire' => 1,
            'peut_entrer_stock' => 1,
            'peut_sortir_stock' => 0,
        ];

        $response = $this->actingAs($this->user)->postJson(route('stock.magasins.droits.store', $magasin->id), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_droits_magasin', [
            'magasin_id' => $magasin->id,
            'type_sujet' => 'ROLE',
            'sujet_id' => $role->id,
            'peut_lire' => true,
            'peut_entrer_stock' => true,
            'peut_sortir_stock' => false,
        ]);
    }

    public function test_can_create_stock_entries_from_bl(): void
    {
        $marque = \Modules\ParcInfo\Models\Marque::create(['libelle' => 'HP']);
        $categorie = \Modules\ParcInfo\Models\CategorieEquipement::create(['code' => 'consommable', 'libelle' => 'Consommables']);
        $fournisseur = \Modules\ParcInfo\Models\Fournisseur::create(['code' => 'FOUR-HP', 'nom' => 'HP France', 'est_actif' => true]);

        $article = \Modules\Achat\Models\Article::create([
            'code_article' => 'ART-HP-800',
            'designation' => 'HP EliteDesk 800',
            'type_article' => 'consommable',
            'marque_id' => $marque->id,
            'categorie_equipement_id' => $categorie->id,
            'prix_indicatif' => 1500.00,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);

        // Le module Achat distingue désormais les montants HT, TVA et TTC :
        // « montant_total » a été remplacé par ces trois colonnes.
        $bc = \Modules\Achat\Models\BonCommande::create([
            'numero_commande' => 'BC-TEST-WIZ',
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'valide',
            'montant_ht' => 1500.00,
            'montant_tva' => 270.00,
            'montant_ttc' => 1770.00,
        ]);

        $bc->lignesCommande()->create([
            'article_id' => $article->id,
            'quantite' => 10,
            'prix_unitaire' => 150.00,
            'taux_tva' => 18,
            'quantite_livree' => 0,
        ]);

        $bl = \Modules\Achat\Models\BordereauLivraison::create([
            'numero_livraison' => 'BL-TEST-WIZ',
            'bon_de_commande_id' => $bc->id,
            'date_livraison' => now()->toDateString(),
            'ref_bordereau_physique' => 'BL-PHY-WIZ',
            'statut' => 'brouillon',
        ]);

        $bl->lignesLivraison()->create([
            'article_id' => $article->id,
            'quantite_livree' => 5,
        ]);

        $bl->load('lignesLivraison.article');

        // Execute Entry Stock Service
        $service = app(\Modules\Stock\Services\EntreeStockService::class);
        $service->creerDepuisBL($bl, $this->user->id);

        $this->assertDatabaseHas('stock_mouvements', [
            'article_id' => $article->id,
            'quantite' => 5,
            'type_origine' => 'BL',
            'origine_id' => $bl->id,
        ]);

        $this->assertDatabaseHas('stock_lots', [
            'article_id' => $article->id,
            'quantite_initiale' => 5,
            'quantite_restante' => 5,
            'cout_unitaire' => 150.00,
        ]);

        $this->assertDatabaseHas('stock_articles_magasin', [
            'article_id' => $article->id,
            'quantite_actuelle' => 5,
            'valeur_stock_fifo' => 750.00,
        ]);
    }
}
