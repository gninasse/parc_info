<?php

namespace Modules\Grh\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;
use Tests\TestCase;

class EmployeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_employe()
    {
        $site = Site::create(['code' => 'S1', 'libelle' => 'Site 1']);
        $direction = Direction::create([
            'site_id' => $site->id,
            'code' => 'D1',
            'libelle' => 'Direction 1',
        ]);

        $employe = Employe::create([
            'matricule' => 'EMP001',
            'nom' => 'DOE',
            'prenom' => 'John',
            'niveau_rattachement' => 'direction',
            'direction_id' => $direction->id,
            'est_actif' => true,
        ]);

        $this->assertDatabaseHas('grh_dossiers_employes', [
            'matricule' => 'EMP001',
            'nom' => 'DOE',
        ]);

        $this->assertEquals('DOE John', $employe->full_name);
        $this->assertEquals('Direction 1', $employe->organisation);
    }
}
