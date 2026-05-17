<?php

namespace Modules\ParcInfo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ParcInfo\Models\Editeur;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\TypeLicence;

class LicenceReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Types de licences
        $types = [
            ['code' => 'VOL', 'libelle' => 'Volume (CAL)', 'description' => 'Licence en volume pour entreprise'],
            ['code' => 'SUB', 'libelle' => 'Abonnement', 'description' => 'Licence par abonnement (SaaS)'],
            ['code' => 'PER', 'libelle' => 'Perpétuelle', 'description' => 'Achat unique sans limite de temps'],
            ['code' => 'OEM', 'libelle' => 'OEM', 'description' => 'Liée au matériel'],
            ['code' => 'FREE', 'libelle' => 'Gratuite / Open Source', 'description' => 'Licence sans coût'],
        ];

        foreach ($types as $type) {
            TypeLicence::updateOrCreate(['code' => $type['code']], $type);
        }

        // Éditeurs
        $editeurs = [
            ['code' => 'MS', 'nom' => 'Microsoft'],
            ['code' => 'ADBE', 'nom' => 'Adobe'],
            ['code' => 'ORA', 'nom' => 'Oracle'],
            ['code' => 'IBM', 'nom' => 'IBM'],
            ['code' => 'GOOG', 'nom' => 'Google'],
        ];

        foreach ($editeurs as $editeur) {
            Editeur::updateOrCreate(['code' => $editeur['code']], $editeur);
        }

        // Fournisseurs
        $fournisseurs = [
            ['code' => 'FR-001', 'nom' => 'SoftSell France', 'type' => 'Revendeur'],
            ['code' => 'FR-002', 'nom' => 'Cloud Provider SA', 'type' => 'Distributeur'],
        ];

        foreach ($fournisseurs as $fournisseur) {
            Fournisseur::updateOrCreate(['code' => $fournisseur['code']], $fournisseur);
        }

        // Logiciels de base
        $ms = Editeur::where('code', 'MS')->first();
        $sub = TypeLicence::where('code', 'SUB')->first();

        Logiciel::updateOrCreate(
            ['code' => 'SW-O365'],
            [
                'nom' => 'Microsoft Office 365 Business',
                'editeur_id' => $ms->id,
                'type_licence_id' => $sub->id,
                'categorie' => 'Bureautique',
                'est_actif' => true,
            ]
        );
    }
}
