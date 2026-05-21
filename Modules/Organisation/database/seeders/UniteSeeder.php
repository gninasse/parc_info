<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Unite;

class UniteSeeder extends Seeder
{
    public function run(): void
    {
        $siteId = Site::where('code', 'SITE-PRINCIPAL')->first()?->id ?? 1;
        $dsmt = Direction::where('code', 'DSMT')->first();

        if (! $dsmt) {
            return;
        }

        // Créer un service générique pour regrouper les unités cliniques si nécessaire
        $serviceClinique = Service::updateOrCreate(
            ['code' => 'SERV-CLINIQUE'],
            [
                'libelle' => 'Service des Unités Cliniques',
                'direction_id' => $dsmt->id,
                'site_id' => $siteId,
                'type_service' => 'clinique',
                'actif' => true,
            ]
        );

        $unites = [
            // Services Médicaux
            ['code' => 'URG', 'libelle' => 'Urgences Médicales'],
            ['code' => 'PNEU', 'libelle' => 'Pneumologie'],
            ['code' => 'HGE', 'libelle' => 'Hépato-gastro-entérologie'],
            ['code' => 'NEPH', 'libelle' => 'Néphrologie'],
            ['code' => 'CARD', 'libelle' => 'Cardiologie'],
            ['code' => 'MED-INT', 'libelle' => 'Médecine Interne'],
            ['code' => 'MAL-INF', 'libelle' => 'Maladies Infectieuses'],
            ['code' => 'NEURO', 'libelle' => 'Neurologie'],
            ['code' => 'PSY', 'libelle' => 'Psychiatrie'],
            ['code' => 'DERMATO', 'libelle' => 'Dermatologie vénérologie'],
            ['code' => 'HEMATO', 'libelle' => 'Hématologie clinique'],
            ['code' => 'RHUMATO', 'libelle' => 'Rhumatologie'],
            ['code' => 'ACUP', 'libelle' => 'Acupuncture'],
            ['code' => 'MPR', 'libelle' => 'Médecine physique et réadaptation'],
            ['code' => 'CHIR-GEN', 'libelle' => 'Chirurgie Générale et Digestive'],
            ['code' => 'ORTHO-TRAU', 'libelle' => 'Orthopédie-Traumatologie'],
            ['code' => 'ORL', 'libelle' => 'Oto-rhino-laryngologie (ORL)'],
            ['code' => 'OPHTA', 'libelle' => 'Ophtalmologie'],
            ['code' => 'NEURO-CHIR', 'libelle' => 'Neurochirurgie'],
            ['code' => 'URO-ANDRO', 'libelle' => 'Urologie-andrologie'],
            ['code' => 'GYNECO-OBST', 'libelle' => 'Gynécologie et Obstétrique'],
            ['code' => 'ONCO', 'libelle' => 'Oncologie'],
            ['code' => 'PEDIATRIE', 'libelle' => 'Pédiatrie'],
            ['code' => 'AR', 'libelle' => 'Anesthésie et réanimation'],
            ['code' => 'CHIR-DENT', 'libelle' => 'Chirurgie dentaire'],
            ['code' => 'STOMATO', 'libelle' => 'Stomatologie et chirurgie maxillo-faciale'],
            ['code' => 'SANTE-PUB', 'libelle' => 'Santé publique'],
            ['code' => 'MED-TRAVAIL', 'libelle' => 'Médecine du travail'],

            // Services Médicotechniques
            ['code' => 'IMAGERIE', 'libelle' => 'Imagerie médicale et radiodiagnostic'],
            ['code' => 'PHYSIO', 'libelle' => 'Physiologie, Exploration fonctionnelle et Médecine du sport'],
            ['code' => 'LABO', 'libelle' => 'Laboratoire'],
            ['code' => 'ANAPATH', 'libelle' => 'Anatomocytologie pathologique et médecine légale'],
            ['code' => 'PHARMACIE', 'libelle' => 'Pharmacie hospitalière'],
        ];

        foreach ($unites as $unite) {
            Unite::updateOrCreate(
                ['code' => $unite['code']],
                [
                    'libelle' => $unite['libelle'],
                    'service_id' => $serviceClinique->id,
                    'site_id' => $siteId,
                    'actif' => true,
                ]
            );
        }
    }
}
