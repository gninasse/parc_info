<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;

class ArticlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Marques
        $marques = ['Dell', 'HP', 'Cisco', 'Microsoft', 'APC', 'Generic'];
        $marqueModels = [];
        foreach ($marques as $libelle) {
            $marqueModels[$libelle] = Marque::firstOrCreate(['libelle' => $libelle]);
        }

        // 2. Seed Fournisseurs
        $fournisseurs = [
            'dell' => ['nom' => 'Dell France', 'email' => 'sales@dell.fr', 'telephone' => '+33102030405'],
            'hp' => ['nom' => 'HP Shop', 'email' => 'sales@hp.fr', 'telephone' => '+33102030406'],
            'cisco' => ['nom' => 'Cisco Direct', 'email' => 'sales@cisco.fr', 'telephone' => '+33102030407'],
            'microsoft' => ['nom' => 'Microsoft France', 'email' => 'sales@microsoft.fr', 'telephone' => '+33102030408'],
            'apc' => ['nom' => 'APC Power Shop', 'email' => 'sales@apc.fr', 'telephone' => '+33102030409'],
        ];
        $fournisseurModels = [];
        foreach ($fournisseurs as $code => $data) {
            $fournisseurModels[$code] = Fournisseur::updateOrCreate(
                ['code' => strtoupper($code)],
                [
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'telephone' => $data['telephone'],
                    'est_actif' => true,
                ]
            );
        }

        // Récupérer les identifiants des catégories existantes
        $ordinateurCat = CategorieEquipement::where('code', 'ordinateur')->first();
        $switchCat = CategorieEquipement::where('code', 'switch')->first();
        $imprimanteCat = CategorieEquipement::where('code', 'imprimante')->first();
        $onduleurCat = CategorieEquipement::where('code', 'onduleur')->first();

        // 3. Seed Articles
        $articles = [
            // Équipements
            [
                'code_article' => 'ART-DELL-LAT5440',
                'designation' => 'Dell Latitude 5440 Core i5',
                'description' => 'Ordinateur portable professionnel Dell Latitude 5440, Intel Core i5, 16 Go RAM, 512 Go SSD.',
                'type_article' => 'equipement',
                'reference_constructeur' => 'LAT5440-I5',
                'marque_id' => $marqueModels['Dell']->id,
                'categorie_equipement_id' => $ordinateurCat ? $ordinateurCat->id : null,
                'fournisseur_prefere_id' => $fournisseurModels['dell']->id,
                'prix_indicatif' => 1200.00,
                'seuil_alerte' => 0,
                'duree_validite_mois' => null,
            ],
            [
                'code_article' => 'ART-CISCO-C9200',
                'designation' => 'Cisco Catalyst 9200 24 ports',
                'description' => 'Switch réseau manageable Cisco Catalyst 9200, 24 ports PoE+, 4 SFP+.',
                'type_article' => 'equipement',
                'reference_constructeur' => 'C9200-24P-A',
                'marque_id' => $marqueModels['Cisco']->id,
                'categorie_equipement_id' => $switchCat ? $switchCat->id : null,
                'fournisseur_prefere_id' => $fournisseurModels['cisco']->id,
                'prix_indicatif' => 2500.00,
                'seuil_alerte' => 0,
                'duree_validite_mois' => null,
            ],
            [
                'code_article' => 'ART-HP-LJPRO',
                'designation' => 'HP LaserJet Pro M404dn',
                'description' => 'Imprimante laser noir et blanc recto-verso avec connexion réseau.',
                'type_article' => 'equipement',
                'reference_constructeur' => 'W1A53A',
                'marque_id' => $marqueModels['HP']->id,
                'categorie_equipement_id' => $imprimanteCat ? $imprimanteCat->id : null,
                'fournisseur_prefere_id' => $fournisseurModels['hp']->id,
                'prix_indicatif' => 350.00,
                'seuil_alerte' => 0,
                'duree_validite_mois' => null,
            ],
            [
                'code_article' => 'ART-APC-SMART1500',
                'designation' => 'APC Smart-UPS SMT 1500VA',
                'description' => 'Onduleur réseau interactif avec écran LCD, autonomie 20 min.',
                'type_article' => 'equipement',
                'reference_constructeur' => 'SMT1500IC',
                'marque_id' => $marqueModels['APC']->id,
                'categorie_equipement_id' => $onduleurCat ? $onduleurCat->id : null,
                'fournisseur_prefere_id' => $fournisseurModels['apc']->id,
                'prix_indicatif' => 650.00,
                'seuil_alerte' => 0,
                'duree_validite_mois' => null,
            ],
            // Consommables
            [
                'code_article' => 'ART-HP-TONER26A',
                'designation' => 'Toner HP LaserJet CF226A',
                'description' => 'Cartouche de toner noir d\'origine HP 26A pour LaserJet Pro M402/M426.',
                'type_article' => 'consommable',
                'reference_constructeur' => 'CF226A',
                'marque_id' => $marqueModels['HP']->id,
                'categorie_equipement_id' => null,
                'fournisseur_prefere_id' => $fournisseurModels['hp']->id,
                'prix_indicatif' => 110.00,
                'seuil_alerte' => 5,
                'duree_validite_mois' => null,
            ],
            [
                'code_article' => 'ART-GEN-PAPIERA4',
                'designation' => 'Rame de Papier A4 80g',
                'description' => 'Rame de 500 feuilles de papier blanc A4 pour impression quotidienne.',
                'type_article' => 'consommable',
                'reference_constructeur' => 'PA4-80G',
                'marque_id' => $marqueModels['Generic']->id,
                'categorie_equipement_id' => null,
                'fournisseur_prefere_id' => $fournisseurModels['hp']->id,
                'prix_indicatif' => 5.50,
                'seuil_alerte' => 20,
                'duree_validite_mois' => null,
            ],
            // Licences
            [
                'code_article' => 'ART-MS-O365BUS',
                'designation' => 'Microsoft 365 Business Standard',
                'description' => 'Abonnement d\'un an pour Microsoft 365, inclut Word, Excel, Teams, Outlook.',
                'type_article' => 'licence',
                'reference_constructeur' => 'O365-BUS-STD',
                'marque_id' => $marqueModels['Microsoft']->id,
                'categorie_equipement_id' => null,
                'fournisseur_prefere_id' => $fournisseurModels['microsoft']->id,
                'prix_indicatif' => 140.00,
                'seuil_alerte' => 0,
                'duree_validite_mois' => 12,
            ],
            [
                'code_article' => 'ART-MS-WIN11PRO',
                'designation' => 'Licence Windows 11 Pro OEM',
                'description' => 'Licence perpétuelle OEM pour système d\'exploitation Windows 11 Pro 64-bit.',
                'type_article' => 'licence',
                'reference_constructeur' => 'FQC-10528',
                'marque_id' => $marqueModels['Microsoft']->id,
                'categorie_equipement_id' => null,
                'fournisseur_prefere_id' => $fournisseurModels['microsoft']->id,
                'prix_indicatif' => 150.00,
                'seuil_alerte' => 0,
                'duree_validite_mois' => null,
            ],
            // Prestations
            [
                'code_article' => 'ART-CISCO-MAINT',
                'designation' => 'Installation et configuration réseau',
                'description' => 'Prestation de service par un ingénieur réseau certifié Cisco.',
                'type_article' => 'prestation',
                'reference_constructeur' => 'SERV-NET-CONF',
                'marque_id' => $marqueModels['Cisco']->id,
                'categorie_equipement_id' => null,
                'fournisseur_prefere_id' => $fournisseurModels['cisco']->id,
                'prix_indicatif' => 850.00,
                'seuil_alerte' => 0,
                'duree_validite_mois' => null,
            ],
        ];

        foreach ($articles as $art) {
            DB::table('achat_articles')->updateOrInsert(
                ['code_article' => $art['code_article']],
                array_merge($art, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
