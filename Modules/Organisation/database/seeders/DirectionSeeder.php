<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;

class DirectionSeeder extends Seeder
{
    public function run(): void
    {
        $siteId = Site::where('code', 'SITE-PRINCIPAL')->first()?->id ?? 1;

        $directions = [
            ['code' => 'DAF', 'libelle' => 'Direction de l’Administration et des Finances'],
            ['code' => 'DSMT', 'libelle' => 'Direction des Services Médicaux et Techniques'],
            ['code' => 'DSIO', 'libelle' => 'Direction des Soins Infirmiers et Obstétricaux', 'description' => 'Directrice actuelle : Madame Da Atian Elise depuis le 20 mai 2025'],
            ['code' => 'DRH', 'libelle' => 'Direction des Ressources Humaines'],
            ['code' => 'DQ', 'libelle' => 'Direction de la Qualité'],
            ['code' => 'DPHUC', 'libelle' => 'Direction de la Prospective Hospitalo-Universitaire et de la Coopération'],
            ['code' => 'DSGL', 'libelle' => 'Direction des Services Généraux et de la Logistique'],
            ['code' => 'DMP', 'libelle' => 'Direction des Marchés Publics'],
            ['code' => 'DCI', 'libelle' => 'Direction du Contrôle Interne'],
            ['code' => 'AC', 'libelle' => 'Agence Comptable'],
            ['code' => 'DSI', 'libelle' => 'Direction des Services Informatiques'],
            ['code' => 'DCMP', 'libelle' => 'Direction du contrôle des marchés publics et des engagements financiers'],
            ['code' => 'DG', 'libelle' => 'Direction Générale'],
        ];

        foreach ($directions as $direction) {
            Direction::updateOrCreate(
                ['code' => $direction['code']],
                [
                    'libelle' => $direction['libelle'],
                    'site_id' => $siteId,
                    'description' => $direction['description'] ?? null,
                    'actif' => true,
                ]
            );
        }
    }
}
