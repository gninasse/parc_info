<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organisation\Models\Batiment;
use Modules\Organisation\Models\Etage;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Site;

class InfrastructureSeeder extends Seeder
{
    public function run(): void
    {
        $siteId = Site::where('code', 'SITE-PRINCIPAL')->first()?->id ?? 1;

        $infrastructure = [
            [
                'code' => 'BAT-ADMIN',
                'libelle' => 'Bâtiment Administratif',
                'description' => 'Direction générale et services d’appui',
                'etages' => [
                    ['numero' => 0, 'libelle' => 'Rez-de-chaussée', 'locaux' => [
                        ['code' => 'ADM-RDC-01', 'libelle' => 'Accueil', 'type' => 'salle_attente'],
                        ['code' => 'ADM-RDC-02', 'libelle' => 'Bureau Courrier', 'type' => 'bureau'],
                    ]],
                    ['numero' => 1, 'libelle' => '1er Étage', 'locaux' => [
                        ['code' => 'ADM-E1-01', 'libelle' => 'Bureau DG', 'type' => 'bureau'],
                        ['code' => 'ADM-E1-02', 'libelle' => 'Salle de réunion DG', 'type' => 'bureau'],
                    ]],
                ],
            ],
            [
                'code' => 'BAT-CHIR',
                'libelle' => 'Bâtiment Chirurgie',
                'description' => 'Blocs opératoires et hospitalisation chirurgie',
                'etages' => [
                    ['numero' => 0, 'libelle' => 'Rez-de-chaussée', 'locaux' => [
                        ['code' => 'CHIR-RDC-01', 'libelle' => 'Urgences chirurgicales', 'type' => 'salle_soins'],
                    ]],
                    ['numero' => 1, 'libelle' => '1er Étage', 'locaux' => [
                        ['code' => 'CHIR-E1-01', 'libelle' => 'Bloc opératoire 1', 'type' => 'salle_soins'],
                    ]],
                ],
            ],
            [
                'code' => 'BAT-MED',
                'libelle' => 'Bâtiment Médecine',
                'description' => 'Services de médecine interne et spécialités',
                'etages' => [
                    ['numero' => 0, 'libelle' => 'Rez-de-chaussée', 'locaux' => [
                        ['code' => 'MED-RDC-01', 'libelle' => 'Consultations externes', 'type' => 'salle_soins'],
                    ]],
                ],
            ],
            [
                'code' => 'BAT-DSI',
                'libelle' => 'Bâtiment DSI',
                'description' => 'Services informatiques',
                'etages' => [
                    ['numero' => 0, 'libelle' => 'Rez-de-chaussée', 'locaux' => [
                        ['code' => 'DSI-RDC-01', 'libelle' => 'Salle Serveur', 'type' => 'magasin'],
                        ['code' => 'DSI-RDC-02', 'libelle' => 'Open Space Maintenance', 'type' => 'bureau'],
                    ]],
                ],
            ],
        ];

        foreach ($infrastructure as $b) {
            $batiment = Batiment::updateOrCreate(
                ['code' => $b['code']],
                [
                    'libelle' => $b['libelle'],
                    'description' => $b['description'],
                    'site_id' => $siteId,
                    'nombre_etages' => count($b['etages']),
                    'actif' => true,
                ]
            );

            foreach ($b['etages'] as $e) {
                $etage = Etage::updateOrCreate(
                    ['batiment_id' => $batiment->id, 'numero' => $e['numero']],
                    ['libelle' => $e['libelle'], 'actif' => true]
                );

                foreach ($e['locaux'] as $l) {
                    Local::updateOrCreate(
                        ['code' => $l['code']],
                        [
                            'libelle' => $l['libelle'],
                            'type_local' => $l['type'],
                            'etage_id' => $etage->id,
                            'actif' => true,
                        ]
                    );
                }
            }
        }
    }
}
