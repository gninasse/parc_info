<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;

class PosteTravailSeeder extends Seeder
{
    public function run(): void
    {
        $postes = [
            [
                'code' => 'POST-DSI-001',
                'libelle' => 'Poste Chef DSI',
                'direction' => 'DSI',
                'service' => 'MNT-INFO',
                'local' => 'DSI-RDC-02',
            ],
            [
                'code' => 'POST-DSI-002',
                'libelle' => 'Poste Technicien Reseau 1',
                'direction' => 'DSI',
                'service' => 'RESEAUX',
                'local' => 'DSI-RDC-02',
            ],
            [
                'code' => 'POST-DAF-001',
                'libelle' => 'Poste Comptabilité 1',
                'direction' => 'DAF',
                'service' => 'BUDGET',
                'local' => 'ADM-RDC-02',
            ],
            [
                'code' => 'POST-DG-001',
                'libelle' => 'Poste Secrétariat DG',
                'direction' => 'DG',
                'service' => 'SP-DG',
                'local' => 'ADM-E1-01',
            ],
            [
                'code' => 'POST-URG-001',
                'libelle' => 'Poste Accueil Urgences',
                'direction' => 'DSMT',
                'service' => 'SERV-CLINIQUE',
                'unite' => 'URG',
                'local' => 'CHIR-RDC-01',
            ],
        ];

        foreach ($postes as $p) {
            $direction = Direction::where('code', $p['direction'])->first();
            $service = Service::where('code', $p['service'])->first();
            $unite = isset($p['unite']) ? Unite::where('code', $p['unite'])->first() : null;
            $local = Local::where('code', $p['local'])->first();

            if ($direction && $service) {
                PosteTravail::updateOrCreate(
                    ['code' => $p['code']],
                    [
                        'libelle' => $p['libelle'],
                        'direction_id' => $direction->id,
                        'service_id' => $service->id,
                        'unite_id' => $unite?->id,
                        'local_id' => $local?->id,
                        'statut' => 'actif',
                        'actif' => true,
                    ]
                );
            }
        }
    }
}
