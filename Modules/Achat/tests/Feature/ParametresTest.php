<?php

namespace Modules\Achat\Tests\Feature;

use Modules\Achat\Database\Seeders\ParametresSeeder;
use Modules\Achat\Models\Parametre;

/**
 * EF-ADM-01→03 — Administration du paramétrage dynamique.
 */
class ParametresTest extends AchatTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametresSeeder::class);
    }

    public function test_l_ecran_des_parametres_s_affiche(): void
    {
        $this->get(route('achat.parametres.index'))->assertOk();
    }

    public function test_get_data_retourne_les_cinq_parametres_seedes(): void
    {
        $reponse = $this->getJson(route('achat.parametres.data'))->assertOk()->json();

        $this->assertSame(5, $reponse['total']);
        $this->assertContains('taux_tva_defaut', array_column($reponse['rows'], 'cle'));
    }

    public function test_modification_d_un_parametre(): void
    {
        $parametre = Parametre::where('cle', 'taux_tva_defaut')->firstOrFail();

        $this->putJson(route('achat.parametres.update', $parametre), ['valeur' => '19.25'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('19.25', $parametre->refresh()->valeur);
        $this->assertSame($this->utilisateur->id, $parametre->updated_by);
    }

    public function test_une_valeur_incompatible_avec_le_type_est_refusee(): void
    {
        $parametre = Parametre::where('cle', 'reliquat_alerte_jours')->firstOrFail();

        $this->putJson(route('achat.parametres.update', $parametre), ['valeur' => 'abc'])
            ->assertStatus(422);

        $this->assertNotSame('abc', $parametre->refresh()->valeur);
    }

    public function test_un_parametre_verrouille_est_intouchable(): void
    {
        $parametre = Parametre::where('cle', 'taux_tva_defaut')->firstOrFail();
        $parametre->update(['modifiable' => false]);

        // EF-ADM-03
        $this->putJson(route('achat.parametres.update', $parametre), ['valeur' => '20'])
            ->assertStatus(422);
    }
}
