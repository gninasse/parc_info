<?php

namespace Modules\ParcInfo\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeReseau;
use Tests\TestCase;

class RouteurManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test can access the routeurs index page.
     */
    public function test_can_access_routeur_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.routeurs.index'));
        $response->assertStatus(200);
    }

    /**
     * Test can get routeurs JSON data.
     */
    public function test_can_get_routeurs_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.routeurs.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    /**
     * Test can store a new routeur in stock.
     */
    public function test_can_create_routeur_in_stock(): void
    {
        $marque = Marque::create(['libelle' => 'Cisco']);
        $typeReseau = TypeReseau::create(['libelle' => 'Routeur']);

        $data = [
            'numero_serie' => 'SN-ROUTER-100',
            'marque_id' => $marque->id,
            'modele' => 'ISR 4331',
            'statut' => 'en_stock',
            'etat' => 'bon',
            'nb_ports' => 4,
            'vitesse_max_mbps' => 1000,
            'est_manageable' => 1,
            'adresse_ip' => '192.168.1.254',
            'masque_sous_reseau' => '255.255.255.0',
            'passerelle' => '192.168.1.253',
            'version_firmware' => '16.9',
            'skip_affectation' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.routeurs.store'), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_equipements', [
            'numero_serie' => 'SN-ROUTER-100',
            'modele' => 'ISR 4331',
            'statut' => 'en_stock',
        ]);

        $this->assertDatabaseHas('parc_info_equipements_reseaux', [
            'adresse_ip' => '192.168.1.254',
            'nb_ports' => 4,
            'est_manageable' => true,
        ]);
    }

    /**
     * Test validation rules during store.
     */
    public function test_store_validation_requires_serial_and_model(): void
    {
        $response = $this->actingAs($this->user)->post(route('parc-info.routeurs.store'), [
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
        $response = $this->actingAs($this->user)->post(route('parc-info.routeurs.store-marque'), [
            'libelle' => 'Huawei',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_marques', [
            'libelle' => 'Huawei',
        ]);
    }
}
