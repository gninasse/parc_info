<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Organisation\Models\Batiment;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Etage;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Unite;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Equipement;
use Tests\TestCase;

class AffectationReformTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected CategorieEquipement $category;

    protected Equipement $equipement;

    protected Site $site;

    protected Batiment $batiment;

    protected Etage $etage;

    protected Local $local;

    protected Direction $direction;

    protected Service $service;

    protected Unite $unite;

    protected function setUp(): void
    {
        parent::setUp();

        // Les habilitations sont couvertes par HabilitationsTest.
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $this->user = User::factory()->create();

        $this->category = CategorieEquipement::create([
            'code' => 'ordinateur',
            'libelle' => 'Ordinateur',
            'table_name' => 'parc_info_ordinateurs',
        ]);

        $this->equipement = Equipement::create([
            'categorie_id' => $this->category->id,
            'code_inventaire' => 'INV-2026-TEST',
            'numero_serie' => 'SN-TEST-999',
            'modele' => 'Test Model',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => [],
        ]);

        // Setup physical locations
        $this->site = Site::create([
            'code' => 'SITE-A',
            'libelle' => 'Site Principal',
            'statut' => 'actif',
        ]);

        // Setup organization hierarchy
        $this->direction = Direction::create([
            'site_id' => $this->site->id,
            'code' => 'DIR-IT',
            'libelle' => 'Direction des Systèmes d\'Information',
            'statut' => 'actif',
        ]);

        $this->service = Service::create([
            'direction_id' => $this->direction->id,
            'code' => 'SRV-DEV',
            'libelle' => 'Service Développement',
            'type_service' => 'administratif',
            'statut' => 'actif',
        ]);

        $this->unite = Unite::create([
            'service_id' => $this->service->id,
            'code' => 'UNT-WEB',
            'libelle' => 'Unité Web',
            'statut' => 'actif',
        ]);

        $this->batiment = Batiment::create([
            'site_id' => $this->site->id,
            'code' => 'BAT-1',
            'libelle' => 'Bâtiment Principal',
            'statut' => 'actif',
        ]);

        $this->etage = Etage::create([
            'batiment_id' => $this->batiment->id,
            'numero' => 1,
            'libelle' => '1er Étage',
            'statut' => 'actif',
        ]);

        $this->local = Local::create([
            'etage_id' => $this->etage->id,
            'code' => 'BUR-101',
            'libelle' => 'Bureau 101',
            'statut' => 'actif',
        ]);
    }

    public function test_can_assign_equipment_to_direction_with_physical_location(): void
    {
        $response = $this->actingAs($this->user)->post(route('parc-info.ordinateurs.store-affectation'), [
            'equipement_id' => $this->equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'local_id' => $this->local->id,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // Check assignment was created
        $this->assertDatabaseHas('parc_info_affectation_equipements', [
            'equipement_id' => $this->equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'local_id' => $this->local->id,
            'statut' => true,
        ]);

        // Check equipment physical location synced
        $this->assertDatabaseHas('parc_info_equipements', [
            'id' => $this->equipement->id,
            'local_id' => $this->local->id,
            'statut' => 'en_service',
        ]);

        // Check change logs
        $this->assertDatabaseHas('parc_info_historique_changements', [
            'equipement_id' => $this->equipement->id,
            'type_changement' => 'AFFECTATION',
            'motif' => 'Nouvelle affectation : DIRECTION - Direction des Systèmes d\'Information',
        ]);

        $this->assertDatabaseHas('parc_info_historique_changements', [
            'equipement_id' => $this->equipement->id,
            'type_changement' => 'MOUVEMENT',
            'motif' => 'Mise en place physique : Site Principal > Bâtiment Principal > 1er Étage > Bureau 101',
        ]);
    }

    public function test_can_assign_equipment_to_service(): void
    {
        $response = $this->actingAs($this->user)->post(route('parc-info.ordinateurs.store-affectation'), [
            'equipement_id' => $this->equipement->id,
            'type_cible' => 'SERVICE',
            'direction_id' => $this->direction->id,
            'service_id' => $this->service->id,
            'local_id' => $this->local->id,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_affectation_equipements', [
            'equipement_id' => $this->equipement->id,
            'type_cible' => 'SERVICE',
            'service_id' => $this->service->id,
            'direction_id' => $this->direction->id,
            'statut' => true,
        ]);

        $this->assertDatabaseHas('parc_info_historique_changements', [
            'equipement_id' => $this->equipement->id,
            'type_changement' => 'AFFECTATION',
            'motif' => 'Nouvelle affectation : SERVICE - Service Développement',
        ]);
    }

    public function test_can_assign_equipment_to_unite(): void
    {
        $response = $this->actingAs($this->user)->post(route('parc-info.ordinateurs.store-affectation'), [
            'equipement_id' => $this->equipement->id,
            'type_cible' => 'UNITE',
            'direction_id' => $this->direction->id,
            'service_id' => $this->service->id,
            'unite_id' => $this->unite->id,
            'local_id' => $this->local->id,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_affectation_equipements', [
            'equipement_id' => $this->equipement->id,
            'type_cible' => 'UNITE',
            'unite_id' => $this->unite->id,
            'statut' => true,
        ]);

        $this->assertDatabaseHas('parc_info_historique_changements', [
            'equipement_id' => $this->equipement->id,
            'type_changement' => 'AFFECTATION',
            'motif' => 'Nouvelle affectation : UNITE - Unité Web',
        ]);
    }

    public function test_can_deassign_equipment_clears_location_and_logs(): void
    {
        // First assign
        $this->actingAs($this->user)->post(route('parc-info.ordinateurs.store-affectation'), [
            'equipement_id' => $this->equipement->id,
            'type_cible' => 'DIRECTION',
            'direction_id' => $this->direction->id,
            'local_id' => $this->local->id,
        ]);

        // Then de-assign
        $response = $this->actingAs($this->user)->post(route('parc-info.ordinateurs.desaffecter', $this->equipement->id), [
            'motif' => 'Retour de matériel',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // Check assignment closed
        $this->assertDatabaseHas('parc_info_affectation_equipements', [
            'equipement_id' => $this->equipement->id,
            'statut' => false,
        ]);

        // Check equipment physical location cleared and back in stock
        $this->assertDatabaseHas('parc_info_equipements', [
            'id' => $this->equipement->id,
            'local_id' => null,
            'statut' => 'en_stock',
        ]);

        // Check logs
        $this->assertDatabaseHas('parc_info_historique_changements', [
            'equipement_id' => $this->equipement->id,
            'type_changement' => 'AFFECTATION',
            'motif' => 'Désaffectation : Retour de matériel',
        ]);

        $this->assertDatabaseHas('parc_info_historique_changements', [
            'equipement_id' => $this->equipement->id,
            'type_changement' => 'MOUVEMENT',
            'motif' => 'Retrait de l\'emplacement physique (Retour en stock) : Site Principal > Bâtiment Principal > 1er Étage > Bureau 101',
        ]);
    }
}
