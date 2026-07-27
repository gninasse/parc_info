<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Database\Seeders\ParcInfoConfigSeeder;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\BonRepartition;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Tests\TestCase;

class BonRepartitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Site $site;

    protected Direction $direction;

    protected Service $service;

    protected CategorieEquipement $category;

    protected Fournisseur $fournisseur;

    protected function setUp(): void
    {
        parent::setUp();

        // Les habilitations sont couvertes par HabilitationsTest.
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $this->user = User::factory()->create();

        // Seed dynamic categories and configurations
        $this->seed(ParcInfoConfigSeeder::class);
        $this->category = CategorieEquipement::where('code', 'ordinateur')->first();

        // Create testing organisation units
        $this->site = Site::create([
            'code' => 'SITE-TEST',
            'libelle' => 'Site Principal',
            'actif' => true,
        ]);

        $this->direction = Direction::create([
            'site_id' => $this->site->id,
            'code' => 'DIR-TEST',
            'libelle' => 'Direction Test',
            'actif' => true,
        ]);

        $this->service = Service::create([
            'direction_id' => $this->direction->id,
            'site_id' => $this->site->id,
            'code' => 'SERV-TEST',
            'libelle' => 'Service Test',
            'type_service' => 'administratif',
            'actif' => true,
        ]);

        // Create testing supplier
        $this->fournisseur = Fournisseur::create([
            'code' => 'FOUR-REP-TEST',
            'nom' => 'Fournisseur Répartition',
            'est_actif' => true,
        ]);
    }

    public function test_can_access_index_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.bons-repartition.index'));
        $response->assertStatus(200)
            ->assertViewIs('parcinfo::informatique.bons-repartition.index')
            ->assertViewHas('fournisseurs');
    }

    public function test_can_create_bon_repartition_with_auto_generated_number(): void
    {
        $data = [
            'date_bon' => '2026-07-05',
            'fournisseur_id' => $this->fournisseur->id,
            'observation' => 'Test observation',
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.bons-repartition.store'), $data);
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'bon_id', 'numero_bon', 'redirect']);

        $bon = BonRepartition::latest()->first();
        $this->assertNotNull($bon);
        $this->assertEquals(now()->year, explode('-', $bon->numero_bon)[1] ?? null);
        $this->assertEquals($this->user->id, $bon->created_by);
    }

    public function test_can_add_equipment_to_bon(): void
    {
        $bon = BonRepartition::create([
            'date_bon' => '2026-07-05',
            'fournisseur_id' => $this->fournisseur->id,
        ]);

        $equipement = Equipement::create([
            'categorie_id' => $this->category->id,
            'code_inventaire' => 'INV-TEST-001',
            'numero_serie' => 'SN-REP-001',
            'modele' => 'Latitude 5430',
            'statut' => 'en_stock_magasin',
            'etat' => 'bon',
        ]);

        $data = [
            'equipement_id' => $equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'nom_receptionniste' => 'M. Responsable',
            'date_livraison' => '2026-07-05',
            'observation' => 'Ligne obs',
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.bons-repartition.lignes.add', $bon->id), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_lignes_bon_repartition', [
            'bon_id' => $bon->id,
            'equipement_id' => $equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'nom_receptionniste' => 'M. Responsable',
            'est_signe' => false,
        ]);
    }

    public function test_cannot_add_out_of_stock_equipment_to_bon(): void
    {
        $bon = BonRepartition::create([
            'date_bon' => '2026-07-05',
            'fournisseur_id' => $this->fournisseur->id,
        ]);

        $equipement = Equipement::create([
            'categorie_id' => $this->category->id,
            'code_inventaire' => 'INV-TEST-002',
            'numero_serie' => 'SN-REP-002',
            'modele' => 'Latitude 5430',
            'statut' => 'en_service', // Already in service!
            'etat' => 'bon',
        ]);

        $data = [
            'equipement_id' => $equipement->id,
            'type_cible' => 'SERVICE',
            'service_id' => $this->service->id,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.bons-repartition.lignes.add', $bon->id), $data);
        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_can_update_ligne_bon(): void
    {
        $bon = BonRepartition::create(['date_bon' => '2026-07-05']);
        $equipement = Equipement::create([
            'categorie_id' => $this->category->id,
            'code_inventaire' => 'INV-TEST-003',
            'numero_serie' => 'SN-REP-003',
            'modele' => 'Latitude 5430',
            'statut' => 'en_stock',
            'etat' => 'bon',
        ]);
        $ligne = $bon->lignes()->create([
            'equipement_id' => $equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'nom_receptionniste' => 'Old Person',
        ]);

        $data = [
            'type_cible' => 'SERVICE',
            'service_id' => $this->service->id,
            'nom_receptionniste' => 'New Person',
            'date_livraison' => '2026-07-06',
        ];

        $response = $this->actingAs($this->user)->put(route('parc-info.bons-repartition.lignes.update', [$bon->id, $ligne->id]), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_lignes_bon_repartition', [
            'id' => $ligne->id,
            'type_cible' => 'SERVICE',
            'service_id' => $this->service->id,
            'nom_receptionniste' => 'New Person',
        ]);
    }

    public function test_can_sign_ligne_and_trigger_affectation(): void
    {
        $bon = BonRepartition::create(['date_bon' => '2026-07-05']);
        $equipement = Equipement::create([
            'categorie_id' => $this->category->id,
            'code_inventaire' => 'INV-TEST-004',
            'numero_serie' => 'SN-REP-004',
            'modele' => 'Latitude 5430',
            'statut' => 'en_stock_magasin',
            'etat' => 'bon',
        ]);
        $ligne = $bon->lignes()->create([
            'equipement_id' => $equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'nom_receptionniste' => 'Directeur General',
            'date_livraison' => '2026-07-05',
        ]);

        $response = $this->actingAs($this->user)->post(route('parc-info.bons-repartition.lignes.signer', [$bon->id, $ligne->id]));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify ligne updated
        $ligne->refresh();
        $this->assertTrue($ligne->est_signe);
        $this->assertNotNull($ligne->date_signature);
        $this->assertNotNull($ligne->affectation_id);

        // Verify AffectationEquipement created
        $affectation = AffectationEquipement::find($ligne->affectation_id);
        $this->assertNotNull($affectation);
        $this->assertEquals($this->direction->id, $affectation->direction_id);
        $this->assertEquals('DIRECTION', $affectation->type_cible);
        $this->assertTrue($affectation->statut);

        // Verify equipment status changed to en_service
        $equipement->refresh();
        $this->assertEquals('en_service', $equipement->statut);

        // Verify HistoriqueChangement recorded
        $this->assertDatabaseHas('parc_info_historique_changements', [
            'equipement_id' => $equipement->id,
            'type_changement' => 'STATUT',
            'ancien_statut' => 'en_stock_magasin',
            'nouveau_statut' => 'en_service',
            'reference_document' => $bon->numero_bon,
        ]);
    }

    public function test_cannot_delete_bon_with_signed_lignes(): void
    {
        $bon = BonRepartition::create(['date_bon' => '2026-07-05']);
        $equipement = Equipement::create([
            'categorie_id' => $this->category->id,
            'code_inventaire' => 'INV-TEST-005',
            'numero_serie' => 'SN-REP-005',
            'modele' => 'Latitude 5430',
            'statut' => 'en_stock',
            'etat' => 'bon',
        ]);
        $ligne = $bon->lignes()->create([
            'equipement_id' => $equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'est_signe' => true, // Simulating signed
            'date_signature' => now(),
        ]);

        $response = $this->actingAs($this->user)->delete(route('parc-info.bons-repartition.destroy', $bon->id));
        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('parc_info_bons_repartition', ['id' => $bon->id]);
    }

    public function test_can_stream_pdf_imprimer(): void
    {
        $bon = BonRepartition::create([
            'date_bon' => '2026-07-05',
            'fournisseur_id' => $this->fournisseur->id,
            'observation' => 'Test observation',
        ]);

        $response = $this->actingAs($this->user)->get(route('parc-info.bons-repartition.imprimer', $bon->id));
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
