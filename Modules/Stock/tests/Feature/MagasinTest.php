<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Grh\Models\Employe;
use Modules\Stock\Models\Magasin;

class MagasinTest extends StockTestCase
{
    public function test_l_ecran_liste_des_magasins_s_affiche(): void
    {
        $this->get(route('stock.magasins.index'))->assertOk();
    }

    public function test_get_data_retourne_la_structure_bootstrap_table(): void
    {
        $this->creerMagasin();

        $this->getJson(route('stock.magasins.data'))
            ->assertOk()
            ->assertJsonStructure(['total', 'rows']);
    }

    public function test_creation_d_un_magasin(): void
    {
        $this->postJson(route('stock.magasins.store'), [
            'code' => 'mag-test',
            'libelle' => 'Magasin de test',
        ])->assertOk()->assertJson(['success' => true]);

        // Le code est normalisé en majuscules.
        $this->assertDatabaseHas('stock_magasins', ['code' => 'MAG-TEST']);
    }

    public function test_le_code_est_unique(): void
    {
        $this->creerMagasin(['code' => 'MAG-0001']);

        $this->postJson(route('stock.magasins.store'), [
            'code' => 'MAG-0001',
            'libelle' => 'Doublon',
        ])->assertStatus(422);
    }

    public function test_le_code_est_immuable_a_la_modification(): void
    {
        $magasin = $this->creerMagasin(['code' => 'MAG-FIXE']);

        $this->putJson(route('stock.magasins.update', $magasin), [
            'code' => 'MAG-AUTRE',
            'libelle' => 'Libellé modifié',
        ])->assertOk();

        // RG-F1-01
        $this->assertSame('MAG-FIXE', $magasin->refresh()->code);
        $this->assertSame('Libellé modifié', $magasin->libelle);
    }

    public function test_activation_et_desactivation(): void
    {
        $magasin = $this->creerMagasin();

        $this->postJson(route('stock.magasins.desactiver', $magasin))->assertOk();
        $this->assertSame('inactif', $magasin->refresh()->statut);

        $this->postJson(route('stock.magasins.activer', $magasin))->assertOk();
        $this->assertSame('actif', $magasin->refresh()->statut);
    }

    public function test_suppression_d_un_magasin_vide(): void
    {
        $magasin = $this->creerMagasin();

        $this->deleteJson(route('stock.magasins.destroy', $magasin))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('stock_magasins', ['id' => $magasin->id]);
    }

    public function test_ajout_d_un_responsable_principal(): void
    {
        $magasin = $this->creerMagasin();
        $employe = Employe::factory()->create();

        $this->postJson(route('stock.magasins.responsables.store', $magasin), [
            'employe_id' => $employe->id,
            'role' => 'principal',
        ])->assertOk();

        $this->assertDatabaseHas('stock_responsables_magasin', [
            'magasin_id' => $magasin->id,
            'employe_id' => $employe->id,
            'role' => 'principal',
        ]);
    }

    public function test_un_seul_responsable_principal_en_cours(): void
    {
        $magasin = $this->creerMagasin();
        [$premier, $second] = Employe::factory()->count(2)->create();

        $this->postJson(route('stock.magasins.responsables.store', $magasin), [
            'employe_id' => $premier->id,
            'role' => 'principal',
        ])->assertOk();

        // RG-F1-06
        $this->postJson(route('stock.magasins.responsables.store', $magasin), [
            'employe_id' => $second->id,
            'role' => 'principal',
        ])->assertStatus(422);
    }
}
