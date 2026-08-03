<?php

namespace Modules\Stock\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organisation\Models\Site;
use Modules\Stock\Models\Magasin;
use RuntimeException;

class StockMagasinsSeeder extends Seeder
{
    /**
     * Les 2 magasins du CHU-YO — un par site (D2), code MAG-{CODE_SITE}.
     * Idempotent : updateOrCreate par code.
     */
    private const MAGASINS = [
        'SITE-PRINCIPAL' => 'Magasin — Site Principal',
        'SITE-GERIATRIE' => 'Magasin — Site Gériatrie',
    ];

    public function run(): void
    {
        foreach (self::MAGASINS as $codeSite => $libelle) {
            $site = Site::where('code', $codeSite)->first();

            if (! $site) {
                throw new RuntimeException(
                    "Le site « {$codeSite} » est introuvable dans organisation_sites : "
                    .'exécutez les migrations du module Organisation avant de seeder les magasins.'
                );
            }

            Magasin::updateOrCreate(
                ['code' => 'MAG-'.$site->code],
                [
                    'libelle' => $libelle,
                    'site_id' => $site->id,
                    'est_actif' => true,
                ]
            );
        }
    }
}
