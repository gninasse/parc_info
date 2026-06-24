<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parametres = [
            [
                'cle' => 'pattern_code_inventaire',
                'valeur' => 'INV-{YYYY}-{SEQUENCE:4}',
                'description' => 'Pattern de génération des codes inventaire',
            ],
            [
                'cle' => 'prefix_bon_commande',
                'valeur' => 'BC',
                'description' => 'Préfixe des Bons de Commande',
            ],
            [
                'cle' => 'prefix_bordereau_livraison',
                'valeur' => 'BL',
                'description' => 'Préfixe des Bordereaux de Livraison',
            ],
            [
                'cle' => 'compteur_inventaire_annee',
                'valeur' => '0',
                'description' => 'Dernier numéro séquentiel de code inventaire utilisé dans l\'année en cours',
            ],
        ];

        foreach ($parametres as $param) {
            DB::table('achat_parametres')->updateOrInsert(
                ['cle' => $param['cle']],
                [
                    'valeur' => $param['valeur'],
                    'description' => $param['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
