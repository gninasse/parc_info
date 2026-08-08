<?php

namespace Modules\Catalogue\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogue\Models\Categorie;

/**
 * Référentiel minimal du module (C7) : une catégorie « Non classé » par
 * nature, marquée est_systeme (insupprimable — garde dans
 * CategorieController::destroy). Idempotent, lancé en production.
 */
class CatalogueReferenceSeeder extends Seeder
{
    public const NON_CLASSE = [
        'CAT-NC-CONS' => 'Non classé — Consommables',
        'CAT-NC-PIE' => 'Non classé — Pièces détachées',
        'CAT-NC-EQP' => 'Non classé — Équipements',
        'CAT-NC-LIC' => 'Non classé — Licences',
    ];

    public function run(): void
    {
        foreach (self::NON_CLASSE as $code => $libelle) {
            Categorie::updateOrCreate(
                ['code' => $code],
                ['libelle' => $libelle, 'est_systeme' => true]
            );
        }
    }
}
