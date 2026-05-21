<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $siteId = Site::where('code', 'SITE-PRINCIPAL')->first()?->id ?? 1;

        $services = [
            // Direction Générale
            ['code' => 'SP-DG', 'libelle' => 'Secrétariat particulier', 'direction' => 'DG'],
            ['code' => 'SPUB', 'libelle' => 'Service de santé publique', 'direction' => 'DG'],
            ['code' => 'CRP', 'libelle' => 'Service de la communication et des relations publiques', 'direction' => 'DG'],
            ['code' => 'SPIH', 'libelle' => 'Service de planification et d’information hospitalière', 'direction' => 'DG'],
            ['code' => 'SCAD', 'libelle' => 'Service central des archives et de la documentation', 'direction' => 'DG'],
            ['code' => 'REGIES', 'libelle' => 'Régies d’avances et de recettes', 'direction' => 'DG'],

            // DAF
            ['code' => 'BUDGET', 'libelle' => 'Service du budget', 'direction' => 'DAF'],
            ['code' => 'GAP', 'libelle' => 'Service de la gestion administrative des patients', 'direction' => 'DAF'],
            ['code' => 'ACHATS', 'libelle' => 'Service des achats', 'direction' => 'DAF'],

            // DRH
            ['code' => 'GASP', 'libelle' => 'Service de la gestion administrative et salariale du personnel', 'direction' => 'DRH'],
            ['code' => 'RFP', 'libelle' => 'Service du recrutement et de la formation professionnelle', 'direction' => 'DRH'],
            ['code' => 'SOCIAL', 'libelle' => 'Service des œuvres sociales', 'direction' => 'DRH'],

            // DSGL
            ['code' => 'PL', 'libelle' => 'Le service du patrimoine et de la logistique', 'direction' => 'DSGL'],
            ['code' => 'TM', 'libelle' => 'Le service des travaux et de la maintenance', 'direction' => 'DSGL'],
            ['code' => 'HOTEL', 'libelle' => 'Le service de l’hôtellerie', 'direction' => 'DSGL'],

            // DMP
            ['code' => 'SMFSC', 'libelle' => 'Le service des marchés de fournitures et services courants', 'direction' => 'DMP'],
            ['code' => 'SMTEPI', 'libelle' => 'Le service des marchés de travaux, équipements et prestations intellectuelles', 'direction' => 'DMP'],
            ['code' => 'SSEMP', 'libelle' => 'Le service de suivi de l’exécution des marchés publics', 'direction' => 'DMP'],

            // DSI
            ['code' => 'MNT-INFO', 'libelle' => 'Le service de maintenance informatique', 'direction' => 'DSI'],
            ['code' => 'GEST-APP', 'libelle' => 'Le service de gestion des applications informatiques', 'direction' => 'DSI'],
            ['code' => 'RESEAUX', 'libelle' => 'Le service de réseaux et télécommunications', 'direction' => 'DSI'],

            // DQ
            ['code' => 'SNPQ', 'libelle' => 'Le service de la normalisation et de la promotion de la qualité', 'direction' => 'DQ'],
            ['code' => 'SEAC', 'libelle' => 'Le service de l’évaluation et de l’amélioration continue', 'direction' => 'DQ'],
            ['code' => 'SHSSP', 'libelle' => 'Le service de l’hygiène hospitalière et de la sécurité des patients', 'direction' => 'DQ'],

            // DPHUC
            ['code' => 'PHU', 'libelle' => 'Le service de la prospective hospitalo-universitaire', 'direction' => 'DPHUC'],
            ['code' => 'COOP', 'libelle' => 'Le service de la coopération', 'direction' => 'DPHUC'],

            // DCI
            ['code' => 'CTRL-GEST', 'libelle' => 'Le service du contrôle de gestion', 'direction' => 'DCI'],
            ['code' => 'AUDIT-INT', 'libelle' => 'Le service de l’audit interne', 'direction' => 'DCI'],

            // DSMT
            ['code' => 'AFF-MED', 'libelle' => 'Le service des affaires médicales', 'direction' => 'DSMT'],
            ['code' => 'AFF-MT', 'libelle' => 'Le service des affaires médicotechniques', 'direction' => 'DSMT'],
            ['code' => 'INFO-MED', 'libelle' => 'Le service de l’information médicale', 'direction' => 'DSMT'],
            ['code' => 'RECH-INV', 'libelle' => 'Le service de la recherche et de l’innovation', 'direction' => 'DSMT'],
            ['code' => 'SOCIAL-MED', 'libelle' => 'Le service social médical', 'direction' => 'DSMT'],

            // DSIO
            ['code' => 'MUS', 'libelle' => 'Le service de management des unités de soins', 'direction' => 'DSIO'],
            ['code' => 'ERSIO', 'libelle' => 'Le service de l’évaluation et de la recherche en soins infirmiers et obstétricaux', 'direction' => 'DSIO'],

            // AC
            ['code' => 'RECETTES', 'libelle' => 'Le service de recettes', 'direction' => 'AC'],
            ['code' => 'DEPENSES', 'libelle' => 'Le service de dépenses', 'direction' => 'AC'],
            ['code' => 'COMPTA', 'libelle' => 'Le service de la comptabilité', 'direction' => 'AC'],
        ];

        foreach ($services as $service) {
            $direction = Direction::where('code', $service['direction'])->first();
            if ($direction) {
                Service::updateOrCreate(
                    ['code' => $service['code']],
                    [
                        'libelle' => $service['libelle'],
                        'direction_id' => $direction->id,
                        'site_id' => $siteId,
                        'type_service' => 'administratif',
                        'actif' => true,
                    ]
                );
            }
        }
    }
}
