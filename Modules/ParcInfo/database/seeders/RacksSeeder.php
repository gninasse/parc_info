<?php

namespace Modules\ParcInfo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organisation\Models\Local;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Infrastructure;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeInfrastructure;

class RacksSeeder extends Seeder
{
    public function run(): void
    {
        // Create brands
        $brands = [
            ['libelle' => 'APC'],
            ['libelle' => 'Eaton'],
            ['libelle' => 'Rittal'],
            ['libelle' => 'Dell'],
        ];

        $marques = [];
        foreach ($brands as $brand) {
            $marques[] = Marque::firstOrCreate($brand, $brand);
        }

        // Create infrastructure type
        $typeRack = TypeInfrastructure::firstOrCreate(
            ['libelle' => 'RACK'],
            ['libelle' => 'RACK']
        );

        $typeBaie = TypeInfrastructure::firstOrCreate(
            ['libelle' => 'BAIE'],
            ['libelle' => 'BAIE']
        );

        // Get local for affectation
        $local = Local::first();

        // Create racks
        $racksData = [
            [
                'code_inventaire' => 'RACK-2026-0001',
                'numero_serie' => 'SN-APC-001',
                'marque_id' => $marques[0]->id, // APC
                'modele' => 'NetShelter SX 42U',
                'statut' => 'en_stock',
                'etat' => 'bon',
                'date_acquisition' => '2024-01-15',
                'date_mise_en_service' => '2024-02-01',
                'u_capacite_totale' => 42,
                'nb_prises_pdu' => 12,
                'est_redondant' => true,
                'has_affectation' => false,
            ],
            [
                'code_inventaire' => 'RACK-2026-0002',
                'numero_serie' => 'SN-EATON-001',
                'marque_id' => $marques[1]->id, // Eaton
                'modele' => '93PM',
                'statut' => 'en_service',
                'etat' => 'bon',
                'date_acquisition' => '2023-06-10',
                'date_mise_en_service' => '2023-07-01',
                'u_capacite_totale' => 42,
                'nb_prises_pdu' => 16,
                'est_redondant' => false,
                'has_affectation' => true,
            ],
            [
                'code_inventaire' => 'RACK-2026-0003',
                'numero_serie' => 'SN-RITTAL-001',
                'marque_id' => $marques[2]->id, // Rittal
                'modele' => 'VX IT 800x2000',
                'statut' => 'en_reparation',
                'etat' => 'mauvais',
                'date_acquisition' => '2022-03-20',
                'date_mise_en_service' => '2022-04-15',
                'u_capacite_totale' => 48,
                'nb_prises_pdu' => 20,
                'est_redondant' => true,
                'has_affectation' => false,
            ],
            [
                'code_inventaire' => 'RACK-2026-0004',
                'numero_serie' => 'SN-DELL-001',
                'marque_id' => $marques[3]->id, // Dell
                'modele' => 'PowerEdge 42U',
                'statut' => 'en_service',
                'etat' => 'bon',
                'date_acquisition' => '2024-08-01',
                'date_mise_en_service' => '2024-08-15',
                'u_capacite_totale' => 42,
                'nb_prises_pdu' => 10,
                'est_redondant' => false,
                'has_affectation' => true,
            ],
        ];

        foreach ($racksData as $data) {
            $hasAffectation = $data['has_affectation'];
            $uCapacite = $data['u_capacite_totale'];
            $nbPrises = $data['nb_prises_pdu'];
            $estRedondant = $data['est_redondant'];

            unset($data['has_affectation'], $data['u_capacite_totale'], $data['nb_prises_pdu'], $data['est_redondant']);

            $equipement = Equipement::create($data);

            Infrastructure::create([
                'equipement_id' => $equipement->id,
                'type_infra_id' => $typeRack->id,
                'u_capacite_totale' => $uCapacite,
                'nb_prises_pdu' => $nbPrises,
                'est_redondant' => $estRedondant,
            ]);

            if ($hasAffectation && $local) {
                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => 'LOCAL',
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now(),
                    'local_id' => $local->id,
                    'niveau_rattachement' => null,
                    'direction_id' => null,
                    'service_id' => null,
                    'unite_id' => null,
                ]);
            }
        }

        $this->command->info('Racks seeded successfully!');
    }
}
