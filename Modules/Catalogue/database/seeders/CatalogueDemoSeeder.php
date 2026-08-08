<?php

namespace Modules\Catalogue\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Editeur;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeLicence;

/**
 * Jeu de démonstration (SFD §6) — volontairement NON appelé par
 * CatalogueDatabaseSeeder : à lancer manuellement hors production via
 *   php artisan module:seed Catalogue --class=CatalogueDemoSeeder
 */
class CatalogueDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        // Catégories : 5 racines + 3 sous-catégories (2 niveaux)
        $informatique = $this->categorie('Matériel informatique');
        $impression = $this->categorie('Impression');
        $reseau = $this->categorie('Réseau');
        $logiciels = $this->categorie('Logiciels');
        $this->categorie('Maintenance');

        $postes = $this->categorie('Postes de travail', $informatique);
        $toners = $this->categorie('Toners et cartouches', $impression);
        $bureautique = $this->categorie('Bureautique', $logiciels);

        // Fournisseurs
        $sitb = $this->fournisseur('SITB Burkina', 'Aïcha Ouédraogo', '+226 70 12 34 56');
        $ebf = $this->fournisseur('E-Business Faso', 'Karim Sawadogo', '+226 76 98 76 54');
        $this->fournisseur('Techno Distribution Sahel', 'Mariam Kaboré', '+226 25 40 41 42');

        // Référentiels ParcInfo
        $hp = Marque::firstOrCreate(['libelle' => 'HP']);
        $dell = Marque::firstOrCreate(['libelle' => 'Dell']);
        $ordinateurs = CategorieEquipement::firstOrCreate(['code' => 'ORDI'], ['libelle' => 'Ordinateurs']);
        $imprimantes = CategorieEquipement::firstOrCreate(['code' => 'IMPR'], ['libelle' => 'Imprimantes']);

        $windows = $this->logiciel('WIN11P', 'Windows 11 Professionnel');
        $office = $this->logiciel('OFF21', 'Office 2021 Standard');

        // Articles : 3 consommables, 2 pièces, 3 équipements, 2 licences
        $articles = [
            ['nom' => 'Toner HP 85A noir', 'nature' => Article::NATURE_CONSOMMABLE, 'categorie' => $toners, 'marque' => $hp, 'reference' => 'CE285A', 'prix' => 45000, 'seuil' => 5, 'unite' => 'cartouche', 'fournisseur' => $sitb],
            ['nom' => 'Ramette papier A4 80g', 'nature' => Article::NATURE_CONSOMMABLE, 'categorie' => $impression, 'prix' => 3500, 'seuil' => 20, 'unite' => 'ramette', 'fournisseur' => $ebf],
            ['nom' => 'Câble réseau RJ45 Cat6 3 m', 'nature' => Article::NATURE_CONSOMMABLE, 'categorie' => $reseau, 'prix' => 2500, 'seuil' => 10],
            ['nom' => 'Disque SSD 512 Go SATA', 'nature' => Article::NATURE_PIECE, 'categorie' => $postes, 'prix' => 55000, 'seuil' => 3, 'fournisseur' => $sitb],
            ['nom' => 'Barrette RAM 8 Go DDR4', 'nature' => Article::NATURE_PIECE, 'categorie' => $postes, 'prix' => 35000, 'seuil' => 4],
            ['nom' => 'Ordinateur portable Dell Latitude 3540', 'nature' => Article::NATURE_EQUIPEMENT, 'categorie' => $postes, 'marque' => $dell, 'reference' => 'LAT-3540', 'prix' => 650000, 'categorie_equipement' => $ordinateurs, 'fournisseur' => $sitb],
            ['nom' => 'Ordinateur de bureau HP ProDesk 400', 'nature' => Article::NATURE_EQUIPEMENT, 'categorie' => $postes, 'marque' => $hp, 'reference' => 'PD400G9', 'prix' => 450000, 'categorie_equipement' => $ordinateurs],
            ['nom' => 'Imprimante HP LaserJet Pro M404', 'nature' => Article::NATURE_EQUIPEMENT, 'categorie' => $impression, 'marque' => $hp, 'reference' => 'M404DN', 'prix' => 320000, 'categorie_equipement' => $imprimantes, 'fournisseur' => $ebf],
            ['nom' => 'Licence Windows 11 Professionnel', 'nature' => Article::NATURE_LICENCE, 'categorie' => $bureautique, 'prix' => 120000, 'logiciel' => $windows],
            ['nom' => 'Licence Office 2021 Standard', 'nature' => Article::NATURE_LICENCE, 'categorie' => $bureautique, 'prix' => 185000, 'logiciel' => $office],
        ];

        foreach ($articles as $data) {
            if (Article::where('nom', $data['nom'])->exists()) {
                continue;
            }

            Article::create([
                'nom' => $data['nom'],
                'nature' => $data['nature'],
                'categorie_id' => $data['categorie']->id,
                'marque_id' => isset($data['marque']) ? $data['marque']->id : null,
                'reference_constructeur' => $data['reference'] ?? null,
                'unite_stock' => $data['unite'] ?? 'unité',
                'prix_indicatif' => $data['prix'],
                'taux_tva' => 18.00,
                'seuil_defaut' => $data['seuil'] ?? null,
                'fournisseur_principal_id' => isset($data['fournisseur']) ? $data['fournisseur']->id : null,
                'categorie_equipement_id' => isset($data['categorie_equipement']) ? $data['categorie_equipement']->id : null,
                'logiciel_id' => isset($data['logiciel']) ? $data['logiciel']->id : null,
            ]);
        }
    }

    private function categorie(string $libelle, ?Categorie $parent = null): Categorie
    {
        return Categorie::firstOrCreate(
            ['libelle' => $libelle],
            ['parent_id' => $parent?->id]
        );
    }

    private function fournisseur(string $raisonSociale, string $contact, string $telephone): Fournisseur
    {
        return Fournisseur::firstOrCreate(
            ['raison_sociale' => $raisonSociale],
            ['contact' => $contact, 'telephone' => $telephone, 'adresse' => 'Ouagadougou, Burkina Faso']
        );
    }

    private function logiciel(string $code, string $nom): Logiciel
    {
        return Logiciel::firstOrCreate(
            ['code' => $code],
            [
                'nom' => $nom,
                'type_licence_id' => TypeLicence::firstOrCreate(['code' => 'VOL'], ['libelle' => 'Volume (CAL)'])->id,
                'editeur_id' => Editeur::firstOrCreate(['code' => 'MS'], ['nom' => 'Microsoft'])->id,
            ]
        );
    }
}
