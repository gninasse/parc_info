<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PostesTravailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = \Modules\Organisation\Models\Service::all();

        if ($services->isEmpty()) {
            $this->command->warn('Aucun service trouvé. Veuillez d\'abord créer des services.');

            return;
        }

        foreach ($services->take(3) as $service) {
            for ($i = 1; $i <= 3; $i++) {
                \Modules\Organisation\Models\PosteTravail::create([
                    'code' => \Modules\Organisation\Models\PosteTravail::generateCode($service->id),
                    'libelle' => "Poste {$service->libelle} {$i}",
                    'description' => "Description du poste {$i} du service {$service->libelle}",
                    'direction_id' => $service->direction_id,
                    'service_id' => $service->id,
                    'unite_id' => $service->unites->first()?->id,
                    'statut' => 'actif',
                    'actif' => true,
                ]);
            }
        }

        $this->command->info('Postes de travail créés avec succès !');
    }
}
