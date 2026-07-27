<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\ParcInfo\Database\Seeders\ParcInfoConfigSeeder;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Marque;
use Tests\TestCase;

class EquipementDynamiqueTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Les habilitations sont couvertes par HabilitationsTest.
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $this->user = User::factory()->create();

        // Seed dynamic categories and field configurations
        $this->seed(ParcInfoConfigSeeder::class);
    }

    /**
     * Test can access dynamic index view.
     */
    public function test_can_access_dynamic_index_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.ordinateurs.index'));
        $response->assertStatus(200)
            ->assertViewIs('parcinfo::informatique.index')
            ->assertViewHas('category')
            ->assertViewHas('colonnesConfig');
    }

    /**
     * Test can fetch data JSON.
     */
    public function test_can_fetch_bootstrap_table_data(): void
    {
        $category = CategorieEquipement::where('code', 'ordinateur')->first();

        Equipement::create([
            'categorie_id' => $category->id,
            'code_inventaire' => 'INV-2026-0001',
            'numero_serie' => 'SN-DYNAMIC-123',
            'modele' => 'Latitude 5430',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => ['type_pc' => 'Portable', 'ram_capacite_go' => 16],
        ]);

        $response = $this->actingAs($this->user)->get(route('parc-info.ordinateurs.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows'])
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.code_inventaire', 'INV-2026-0001')
            ->assertJsonPath('rows.0.champs_valeurs_resolus.type_pc', 'Portable');
    }

    /**
     * Test creating a dynamic equipment with valid fields.
     */
    public function test_can_create_equipment_with_valid_dynamic_fields(): void
    {
        $marque = Marque::create(['libelle' => 'Dell']);

        $data = [
            'numero_serie' => 'SN-COMP-999',
            'marque_id' => $marque->id,
            'modele' => 'Optiplex 7010',
            'date_acquisition' => '2026-06-19',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => [
                'type_pc' => 'Fixe',
                'ram_capacite_go' => 32,
            ],
            'skip_affectation' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.ordinateurs.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_equipements', [
            'numero_serie' => 'SN-COMP-999',
            'modele' => 'Optiplex 7010',
            'statut' => 'en_stock',
        ]);

        $equipement = Equipement::where('numero_serie', 'SN-COMP-999')->first();
        $this->assertEquals('Fixe', $equipement->champs_valeurs['type_pc']);
        $this->assertEquals(32, $equipement->champs_valeurs['ram_capacite_go']);
    }

    /**
     * Test dynamic field validation checks rules.
     */
    public function test_cannot_create_if_required_dynamic_field_is_missing(): void
    {
        $data = [
            'numero_serie' => 'SN-COMP-FAIL',
            'modele' => 'Optiplex Fail',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => [
                // 'type_pc' is required by config, but omitted here
                'ram_capacite_go' => 8,
            ],
            'skip_affectation' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.ordinateurs.store'), $data);
        $response->assertStatus(302); // Validation redirection
        $response->assertSessionHasErrors(['champs_valeurs.type_pc']);
    }

    /**
     * Test updating equipment dynamic fields.
     */
    public function test_can_update_equipment_dynamic_fields(): void
    {
        $category = CategorieEquipement::where('code', 'ordinateur')->first();

        $equipement = Equipement::create([
            'categorie_id' => $category->id,
            'code_inventaire' => 'INV-2026-999',
            'numero_serie' => 'SN-UPDATE-BEFORE',
            'modele' => 'Inspiron 15',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => ['type_pc' => 'Portable', 'ram_capacite_go' => 8],
        ]);

        $data = [
            'numero_serie' => 'SN-UPDATE-AFTER',
            'modele' => 'Inspiron 15 Plus',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => [
                'type_pc' => 'Workstation',
                'ram_capacite_go' => 16,
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('parc-info.ordinateurs.update', $equipement->id), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $equipement->refresh();
        $this->assertEquals('SN-UPDATE-AFTER', $equipement->numero_serie);
        $this->assertEquals('Workstation', $equipement->champs_valeurs['type_pc']);
        $this->assertEquals(16, $equipement->champs_valeurs['ram_capacite_go']);
    }

    /**
     * Test can access ecrans index view.
     */
    public function test_can_access_ecrans_index_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.ecrans.index'));
        $response->assertStatus(200)
            ->assertViewIs('parcinfo::informatique.index')
            ->assertViewHas('category')
            ->assertViewHas('colonnesConfig');
    }

    /**
     * Test can access unites centrales index view.
     */
    public function test_can_access_unites_centrales_index_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.unite-centrales.index'));
        $response->assertStatus(200)
            ->assertViewIs('parcinfo::informatique.index')
            ->assertViewHas('category')
            ->assertViewHas('colonnesConfig');
    }

    /**
     * Test creating a screen (ecran) with valid fields.
     */
    public function test_can_create_ecran_with_valid_dynamic_fields(): void
    {
        $marque = Marque::create(['libelle' => 'HP']);

        $data = [
            'numero_serie' => 'SN-ECRAN-888',
            'marque_id' => $marque->id,
            'modele' => 'EliteDisplay E243',
            'date_acquisition' => '2026-06-19',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => [
                'taille_pouces' => 24,
                'resolution' => 'FHD (1080p)',
            ],
            'skip_affectation' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.ecrans.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_equipements', [
            'numero_serie' => 'SN-ECRAN-888',
            'modele' => 'EliteDisplay E243',
            'statut' => 'en_stock',
        ]);
    }

    /**
     * Test creating a central unit (unite-centrale) with valid fields.
     */
    public function test_can_create_unite_centrale_with_valid_dynamic_fields(): void
    {
        $marque = Marque::create(['libelle' => 'Lenovo']);

        $data = [
            'numero_serie' => 'SN-UC-777',
            'marque_id' => $marque->id,
            'modele' => 'ThinkCentre M70q',
            'date_acquisition' => '2026-06-19',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => [
                'ram_capacite_go' => 16,
                'stockage_capacite_go' => 512,
            ],
            'skip_affectation' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.unite-centrales.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_equipements', [
            'numero_serie' => 'SN-UC-777',
            'modele' => 'ThinkCentre M70q',
            'statut' => 'en_stock',
        ]);
    }
}
