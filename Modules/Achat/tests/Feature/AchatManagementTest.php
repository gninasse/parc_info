<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;
use Tests\TestCase;

class AchatManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Marque $marque;

    protected CategorieEquipement $categorie;

    protected Fournisseur $fournisseur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Autoriser toutes les gates dans les tests
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        $this->marque = Marque::create(['libelle' => 'HP']);
        $this->categorie = CategorieEquipement::create(['code' => 'ordinateur', 'libelle' => 'Ordinateurs']);
        $this->fournisseur = Fournisseur::create(['code' => 'FOUR-HP', 'nom' => 'HP France', 'est_actif' => true]);
    }

    public function test_can_access_achat_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('achat.dashboard.index'));
        $response->assertStatus(200);
    }

    public function test_can_access_articles_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('achat.articles.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_article(): void
    {
        $data = [
            'code_article' => 'ART-HP-800',
            'designation' => 'HP EliteDesk 800 G6',
            'type_article' => 'equipement',
            'marque_id' => $this->marque->id,
            'categorie_equipement_id' => $this->categorie->id,
            'fournisseur_prefere_id' => $this->fournisseur->id,
            'prix_indicatif' => 450000,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
        ];

        $response = $this->actingAs($this->user)->post(route('achat.articles.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('achat_articles', [
            'code_article' => 'ART-HP-800',
            'designation' => 'HP EliteDesk 800 G6',
        ]);
    }

    public function test_can_create_bon_commande(): void
    {
        $article = Article::create([
            'code_article' => 'ART-TEST',
            'designation' => 'Test Article',
            'type_article' => 'equipement',
            'marque_id' => $this->marque->id,
            'categorie_equipement_id' => $this->categorie->id,
            'prix_indicatif' => 100000,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);

        $data = [
            'fournisseur_id' => $this->fournisseur->id,
            'date_commande' => now()->toDateString(),
            'commentaire' => 'Commande urgente',
            'lignes' => [
                [
                    'article_id' => $article->id,
                    'quantite' => 5,
                    'prix_unitaire' => 95000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('achat.bons-commande.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('achat_bons_commande', [
            'fournisseur_id' => $this->fournisseur->id,
            'statut' => 'brouillon',
        ]);
    }

    public function test_can_validate_bon_commande(): void
    {
        $article = Article::create([
            'code_article' => 'ART-TEST',
            'designation' => 'Test Article',
            'type_article' => 'equipement',
            'marque_id' => $this->marque->id,
            'categorie_equipement_id' => $this->categorie->id,
            'prix_indicatif' => 100000,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);

        $bc = BonCommande::create([
            'numero_commande' => 'BC-TEST-01',
            'fournisseur_id' => $this->fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'brouillon',
            'montant_total' => 95000,
        ]);

        $bc->lignesCommande()->create([
            'article_id' => $article->id,
            'quantite' => 1,
            'prix_unitaire' => 95000,
            'quantite_livree' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('achat.bons-commande.valider', $bc->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('achat_bons_commande', [
            'id' => $bc->id,
            'statut' => 'valide',
        ]);
    }

    public function test_can_create_bordereau_livraison(): void
    {
        $article = Article::create([
            'code_article' => 'ART-TEST',
            'designation' => 'Test Article',
            'type_article' => 'equipement',
            'marque_id' => $this->marque->id,
            'categorie_equipement_id' => $this->categorie->id,
            'prix_indicatif' => 100000,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);

        $bc = BonCommande::create([
            'numero_commande' => 'BC-TEST-02',
            'fournisseur_id' => $this->fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'valide',
            'montant_total' => 95000,
        ]);

        $bc->lignesCommande()->create([
            'article_id' => $article->id,
            'quantite' => 1,
            'prix_unitaire' => 95000,
            'quantite_livree' => 0,
        ]);

        $data = [
            'bon_de_commande_id' => $bc->id,
            'date_livraison' => now()->toDateString(),
            'ref_bordereau_physique' => 'BL-PHY-TEST-02',
            'commentaire' => 'Livré sans encombre',
            'lignes' => [
                [
                    'article_id' => $article->id,
                    'quantite_livree' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('achat.bordereaux.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('achat_bordereaux_livraison', [
            'bon_de_commande_id' => $bc->id,
            'ref_bordereau_physique' => 'BL-PHY-TEST-02',
            'statut' => 'brouillon',
        ]);
    }

    public function test_can_validate_bordereau_livraison_via_wizard(): void
    {
        $article = Article::create([
            'code_article' => 'ART-TEST-WIZ',
            'designation' => 'HP EliteDesk 800',
            'type_article' => 'equipement',
            'marque_id' => $this->marque->id,
            'categorie_equipement_id' => $this->categorie->id,
            'prix_indicatif' => 450000,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ]);

        $bc = BonCommande::create([
            'numero_commande' => 'BC-TEST-WIZ',
            'fournisseur_id' => $this->fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'valide',
            'montant_total' => 450000,
        ]);

        $bc->lignesCommande()->create([
            'article_id' => $article->id,
            'quantite' => 1,
            'prix_unitaire' => 450000,
            'quantite_livree' => 0,
        ]);

        $bl = BordereauLivraison::create([
            'numero_livraison' => 'BL-TEST-WIZ',
            'bon_de_commande_id' => $bc->id,
            'date_livraison' => now()->toDateString(),
            'ref_bordereau_physique' => 'BL-PHY-WIZ',
            'statut' => 'brouillon',
        ]);

        $bl->lignesLivraison()->create([
            'article_id' => $article->id,
            'quantite_livree' => 1,
        ]);

        // 1. Sauvegarder étape du wizard
        $wizardData = [
            'unites' => [
                [
                    'numero_serie' => 'SN-WIZARD-123',
                    'code_inventaire' => 'INV-WIZARD-123',
                ],
            ],
            'completed' => true,
        ];

        $responseSave = $this->actingAs($this->user)->post(
            route('achat.bordereaux.wizard.sauvegarder', [$bl->id, $article->id]),
            $wizardData
        );
        $responseSave->assertStatus(200)->assertJson(['success' => true]);

        // 2. Valider bordereau (validation finale)
        $responseValidate = $this->actingAs($this->user)->post(
            route('achat.bordereaux.wizard.valider', $bl->id)
        );
        $responseValidate->assertStatus(200)->assertJson(['success' => true]);

        // 3. Vérifier que les équipements sont créés dans ParcInfo
        $this->assertDatabaseHas('parc_info_equipements', [
            'numero_serie' => 'SN-WIZARD-123',
            'code_inventaire' => 'INV-WIZARD-123',
            'modele' => 'HP EliteDesk 800',
            'statut' => 'en_stock',
        ]);

        // 4. Vérifier l'historique
        $this->assertDatabaseHas('parc_info_historique_changements', [
            'nouvel_etat' => 'bon',
            'nouveau_statut' => 'en_stock',
        ]);

        // 5. Vérifier que le statut du BL est valide
        $this->assertDatabaseHas('achat_bordereaux_livraison', [
            'id' => $bl->id,
            'statut' => 'valide',
        ]);

        // 6. Vérifier que le BC a été mis à jour comme livré
        $this->assertDatabaseHas('achat_bons_commande', [
            'id' => $bc->id,
            'statut' => 'livre',
        ]);
    }

    public function test_can_print_bon_commande(): void
    {
        $bc = BonCommande::create([
            'numero_commande' => 'BC-TEST-PRINT',
            'fournisseur_id' => $this->fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'brouillon',
            'montant_total' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('achat.bons-commande.imprimer', $bc->id));
        $response->assertStatus(200)
            ->assertViewIs('achat::bons_commande.imprimer')
            ->assertSee($bc->numero_commande);
    }

    public function test_can_stream_pdf_bon_commande(): void
    {
        $bc = BonCommande::create([
            'numero_commande' => 'BC-TEST-PDF',
            'fournisseur_id' => $this->fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'brouillon',
            'montant_total' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('achat.bons-commande.imprimer', ['bon_commande' => $bc->id, 'pdf' => 1]));
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
