<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Contact;
use Modules\ParcInfo\Models\Editeur;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\TypeLicence;
use Tests\TestCase;

class FournisseurManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_access_fournisseurs_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.fournisseurs.index'));
        $response->assertStatus(200);
    }

    public function test_can_get_fournisseurs_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.fournisseurs.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_can_access_create_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.fournisseurs.create'));
        $response->assertStatus(200);
    }

    public function test_can_create_fournisseur(): void
    {
        $data = [
            'code' => 'FOUR-TEST',
            'nom' => 'Test Supplier',
            'type' => 'Revendeur',
            'email' => 'contact@test.com',
            'telephone' => '0123456789',
            'adresse' => '123 Test Street',
            'code_postal' => '75001',
            'ville' => 'Paris',
            'pays' => 'France',
            'est_actif' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.fournisseurs.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_fournisseurs', [
            'code' => 'FOUR-TEST',
            'nom' => 'Test Supplier',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);
    }

    public function test_can_show_fournisseur(): void
    {
        $fournisseur = Fournisseur::create([
            'code' => 'FOUR-SHOW',
            'nom' => 'Show Supplier',
            'est_actif' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('parc-info.fournisseurs.show', $fournisseur->id));
        $response->assertStatus(200);
    }

    public function test_can_update_fournisseur(): void
    {
        $fournisseur = Fournisseur::create([
            'code' => 'FOUR-UPDATE',
            'nom' => 'Update Supplier',
            'est_actif' => true,
        ]);

        $data = [
            'code' => 'FOUR-UPDATED',
            'nom' => 'Updated Supplier',
            'type' => 'Editeur',
            'email' => 'updated@test.com',
            'telephone' => '9876543210',
            'adresse' => '456 Updated Street',
            'code_postal' => '75002',
            'ville' => 'Lyon',
            'pays' => 'France',
            'est_actif' => 1,
        ];

        $response = $this->actingAs($this->user)->put(route('parc-info.fournisseurs.update', $fournisseur->id), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_fournisseurs', [
            'id' => $fournisseur->id,
            'code' => 'FOUR-UPDATED',
            'nom' => 'Updated Supplier',
            'ville' => 'Lyon',
        ]);
    }

    public function test_can_toggle_fournisseur_status(): void
    {
        $fournisseur = Fournisseur::create([
            'code' => 'FOUR-TOGGLE',
            'nom' => 'Toggle Supplier',
            'est_actif' => true,
        ]);

        $response = $this->actingAs($this->user)->patch(route('parc-info.fournisseurs.toggle', $fournisseur->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true, 'est_actif' => false]);

        $this->assertDatabaseHas('parc_info_fournisseurs', [
            'id' => $fournisseur->id,
            'est_actif' => false,
        ]);
    }

    public function test_can_manage_contacts(): void
    {
        $fournisseur = Fournisseur::create([
            'code' => 'FOUR-CONTACT',
            'nom' => 'Contact Supplier',
            'est_actif' => true,
        ]);

        // 1. Create Contact
        $contactData = [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'fonction' => 'Commercial',
            'email' => 'j.dupont@test.com',
            'telephone' => '0612345678',
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.fournisseurs.store-contact', $fournisseur->id), $contactData);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_contacts', [
            'fournisseur_id' => $fournisseur->id,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
        ]);

        $contact = Contact::where('fournisseur_id', $fournisseur->id)->first();

        // 2. Update Contact
        $updatedContactData = [
            'nom' => 'Dupont Updated',
            'prenom' => 'Jean',
            'fonction' => 'Directeur Commercial',
            'email' => 'j.dupont.up@test.com',
            'telephone' => '0699999999',
        ];

        $response = $this->actingAs($this->user)->put(route('parc-info.fournisseurs.update-contact', [$fournisseur->id, $contact->id]), $updatedContactData);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_contacts', [
            'id' => $contact->id,
            'nom' => 'Dupont Updated',
            'fonction' => 'Directeur Commercial',
        ]);

        // 3. Delete Contact
        $response = $this->actingAs($this->user)->delete(route('parc-info.fournisseurs.delete-contact', [$fournisseur->id, $contact->id]));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('parc_info_contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_contact_validation_requires_either_nom_or_prenom(): void
    {
        $fournisseur = Fournisseur::create([
            'code' => 'FOUR-VAL',
            'nom' => 'Validation Supplier',
            'est_actif' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('parc-info.fournisseurs.store-contact', $fournisseur->id), [
            'nom' => '',
            'prenom' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['nom', 'prenom']);
    }

    public function test_cannot_delete_fournisseur_with_active_licences(): void
    {
        $fournisseur = Fournisseur::create([
            'code' => 'FOUR-DEL',
            'nom' => 'Delete Supplier',
            'est_actif' => true,
        ]);

        $typeLicence = TypeLicence::create(['code' => 'TEST-LIC', 'libelle' => 'Test Type Licence']);
        $editeur = Editeur::create(['code' => 'EDIT-TEST', 'nom' => 'Test Editeur']);
        $logiciel = Logiciel::create([
            'code' => 'LOG-TEST',
            'nom' => 'Test Software',
            'editeur_id' => $editeur->id,
            'type_licence_id' => $typeLicence->id,
        ]);

        Licence::create([
            'logiciel_id' => $logiciel->id,
            'cle_licence' => 'KEY-123',
            'type_activation' => 'volume',
            'nombre_postes_accordes' => 5,
            'modele_licencing' => 'device',
            'date_acquisition' => now(),
            'date_expiration' => now()->addYear(),
            'fournisseur_id' => $fournisseur->id,
            'statut' => 'actif',
            'actif' => true,
        ]);

        $response = $this->actingAs($this->user)->delete(route('parc-info.fournisseurs.destroy', $fournisseur->id));
        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('parc_info_fournisseurs', [
            'id' => $fournisseur->id,
        ]);
    }
}
