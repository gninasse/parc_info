<?php

namespace Modules\ParcInfo\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeReseau;
use Tests\TestCase;

class SwitchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test can access the switches index page.
     */
    public function test_can_access_switch_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.switches.index'));
        $response->assertStatus(200);
    }

    /**
     * Test can get switches JSON data.
     */
    public function test_can_get_switches_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.switches.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    /**
     * Test can store a new switch in stock.
     */
    public function test_can_create_switch_in_stock(): void
    {
        $marque = Marque::create(['libelle' => 'Cisco']);
        $typeReseau = TypeReseau::create(['libelle' => 'Switch']);

        $data = [
            'numero_serie' => 'SN-SWITCH-100',
            'marque_id' => $marque->id,
            'modele' => 'Catalyst 9200',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'nb_ports' => 48,
            'vitesse_max_mbps' => 1000,
            'est_poe' => 1,
            'est_manageable' => 1,
            'adresse_ip' => '192.168.1.100',
            'masque_sous_reseau' => '255.255.255.0',
            'passerelle' => '192.168.1.1',
            'version_firmware' => '17.3',
            'skip_affectation' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.switches.store'), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_equipements', [
            'numero_serie' => 'SN-SWITCH-100',
            'modele' => 'Catalyst 9200',
            'statut' => 'en_stock',
        ]);

        $this->assertDatabaseHas('parc_info_equipements_reseaux', [
            'adresse_ip' => '192.168.1.100',
            'nb_ports' => 48,
            'est_poe' => true,
            'est_manageable' => true,
        ]);
    }

    /**
     * Test validation rules during store.
     */
    public function test_store_validation_requires_serial_and_model(): void
    {
        $response = $this->actingAs($this->user)->post(route('parc-info.switches.store'), [
            'numero_serie' => '',
            'modele' => '',
            'statut' => 'en_stock',
            'etat' => 'bon',
        ]);

        $response->assertStatus(302); // Redirect back on validation error
        $response->assertSessionHasErrors(['numero_serie', 'modele']);
    }

    /**
     * Test quick creation of a network brand.
     */
    public function test_can_quick_add_brand(): void
    {
        $response = $this->actingAs($this->user)->post(route('parc-info.switches.store-marque'), [
            'libelle' => 'Netgear',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_marques', [
            'libelle' => 'Netgear',
        ]);
    }
}
