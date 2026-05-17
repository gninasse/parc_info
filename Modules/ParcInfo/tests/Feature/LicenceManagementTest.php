<?php

namespace Modules\ParcInfo\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ParcInfo\Models\Editeur;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\TypeLicence;
use Tests\TestCase;

class LicenceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_access_licence_index()
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.licences.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_licence()
    {
        $type = TypeLicence::create(['code' => 'TEST', 'libelle' => 'Test Type']);
        $editeur = Editeur::create(['code' => 'ED', 'nom' => 'Test Editeur']);
        $fournisseur = Fournisseur::create(['code' => 'F1', 'nom' => 'Test Fournisseur']);
        $logiciel = Logiciel::create([
            'code' => 'SW1',
            'nom' => 'Test SW',
            'type_licence_id' => $type->id,
            'editeur_id' => $editeur->id,
        ]);

        $data = [
            'logiciel_id' => $logiciel->id,
            'type_activation' => 'volume',
            'modele_licencing' => 'device',
            'nombre_postes_accordes' => 10,
            'date_acquisition' => now()->format('Y-m-d'),
            'date_expiration' => now()->addYear()->format('Y-m-d'),
            'devise' => 'EUR',
            'fournisseur_id' => $fournisseur->id,
            'statut' => 'actif',
            'actif' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.licences.store'), $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('parc_info_licences', [
            'logiciel_id' => $logiciel->id,
            'nombre_postes_accordes' => 10,
        ]);
    }
}
