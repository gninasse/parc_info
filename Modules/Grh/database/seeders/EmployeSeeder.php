<?php

namespace Modules\Grh\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;

class EmployeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Direction Générale
        $dg = Direction::where('code', 'DG')->first();
        if ($dg) {
            $employeDG = Employe::updateOrCreate(
                ['matricule' => 'M001'],
                [
                    'nom' => 'SAWADOGO',
                    'prenom' => 'Issaka',
                    'genre' => 'M',
                    'poste' => 'Directeur Général',
                    'niveau_rattachement' => 'direction',
                    'direction_id' => $dg->id,
                    'date_embauche' => '2020-01-15',
                ]
            );
            $this->addContacts($employeDG, 'issaka.sawadogo@chu-yo.bf');
        }

        // 2. DSIO - Madame Da Atian Elise (mentionnée dans les directions)
        $dsio = Direction::where('code', 'DSIO')->first();
        if ($dsio) {
            $employeDSIO = Employe::updateOrCreate(
                ['matricule' => 'M002'],
                [
                    'nom' => 'DA ATIAN',
                    'prenom' => 'Elise',
                    'genre' => 'F',
                    'poste' => 'Directrice des Soins Infirmiers et Obstétricaux',
                    'niveau_rattachement' => 'direction',
                    'direction_id' => $dsio->id,
                    'date_embauche' => '2025-05-20',
                ]
            );
            $this->addContacts($employeDSIO, 'elise.da@chu-yo.bf');
        }

        // 3. DSI
        $dsi = Direction::where('code', 'DSI')->first();
        if ($dsi) {
            $chefDSI = Employe::updateOrCreate(
                ['matricule' => 'M003'],
                [
                    'nom' => 'OUEDRAOGO',
                    'prenom' => 'Adama',
                    'genre' => 'M',
                    'poste' => 'Directeur des Services Informatiques',
                    'niveau_rattachement' => 'direction',
                    'direction_id' => $dsi->id,
                    'date_embauche' => '2021-06-10',
                ]
            );
            $this->addContacts($chefDSI, 'adama.oued@chu-yo.bf');

            // Service Maintenance Info
            $servMnt = Service::where('code', 'MNT-INFO')->first();
            if ($servMnt) {
                $tech1 = Employe::updateOrCreate(
                    ['matricule' => 'M004'],
                    [
                        'nom' => 'TRAORE',
                        'prenom' => 'Souleymane',
                        'genre' => 'M',
                        'poste' => 'Technicien Supérieur en Informatique',
                        'niveau_rattachement' => 'service',
                        'direction_id' => $dsi->id,
                        'service_id' => $servMnt->id,
                    ]
                );
                $this->addContacts($tech1, 'souley.traore@chu-yo.bf');
            }
        }

        // 4. Urgences (Unité Clinique)
        $uniteUrg = Unite::where('code', 'URG')->first();
        if ($uniteUrg) {
            $infUrg = Employe::updateOrCreate(
                ['matricule' => 'M005'],
                [
                    'nom' => 'ZONGO',
                    'prenom' => 'Fatoumata',
                    'genre' => 'F',
                    'poste' => 'Infirmière Chef',
                    'niveau_rattachement' => 'unite',
                    'direction_id' => $uniteUrg->service?->direction_id,
                    'service_id' => $uniteUrg->service_id,
                    'unite_id' => $uniteUrg->id,
                ]
            );
            $this->addContacts($infUrg, 'fatou.zongo@chu-yo.bf');
        }
    }

    private function addContacts(Employe $employe, string $email): void
    {
        $employe->contacts()->updateOrCreate(
            ['type_contact' => 'email'],
            ['valeur' => $email]
        );

        $employe->contacts()->updateOrCreate(
            ['type_contact' => 'telephone'],
            ['valeur' => '7000000'.$employe->id]
        );
    }
}
