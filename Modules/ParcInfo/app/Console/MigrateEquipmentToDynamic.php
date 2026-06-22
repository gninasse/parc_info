<?php

namespace Modules\ParcInfo\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\ChampConfig;
use Modules\ParcInfo\Models\Dictionnaire;
use Modules\ParcInfo\Models\DictionnaireValeur;
use Modules\ParcInfo\Models\Equipement;

class MigrateEquipmentToDynamic extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'parcinfo:migrate-to-dynamic';

    /**
     * The console command description.
     */
    protected $description = 'Migrate ParcInfo CTI tables to generic JSONB structure and 2-table lookup model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting migration to dynamic architecture...');

        DB::beginTransaction();

        try {
            // 1. Seed Categories
            $this->info('Seeding categories...');
            $categories = [
                'ordinateur' => ['libelle' => 'Ordinateur', 'icone' => 'bi-cpu'],
                'imprimante' => ['libelle' => 'Imprimante', 'icone' => 'bi-printer'],
                'scanner' => ['libelle' => 'Scanner', 'icone' => 'bi-camera'],
                'mobile' => ['libelle' => 'Mobiles & Tablettes', 'icone' => 'bi-tablet-landscape'],
                'serveur' => ['libelle' => 'Serveur Physique', 'icone' => 'bi-hdd-stack'],
                'serveur-virtuel' => ['libelle' => 'Serveur Virtuel', 'icone' => 'bi-cloud'],
                'telephone' => ['libelle' => 'Téléphonie', 'icone' => 'bi-telephone'],
                'reseau' => ['libelle' => 'Équipement Réseau', 'icone' => 'bi-router'],
                'camera' => ['libelle' => 'Caméra IP', 'icone' => 'bi-webcam'],
                'infrastructure' => ['libelle' => 'Infrastructure & Racks', 'icone' => 'bi-box'],
            ];

            $categoryModels = [];
            foreach ($categories as $code => $data) {
                $categoryModels[$code] = CategorieEquipement::updateOrCreate(
                    ['code' => $code],
                    ['libelle' => $data['libelle'], 'icone' => $data['icone']]
                );
            }

            // 2. Seeding Dictionaries
            $this->info('Seeding dictionaries and importing lookup data...');
            $dictSources = [
                'type_ram' => ['table' => 'parc_info_types_rams', 'libelle' => 'Types de RAM'],
                'type_cpu' => ['table' => 'parc_info_types_cpus', 'libelle' => 'Types de CPU'],
                'type_disque' => ['table' => 'parc_info_types_disques', 'libelle' => 'Types de Disques de Stockage'],
                'type_os' => ['table' => 'parc_info_types_os', 'libelle' => 'Types de Systèmes d\'exploitation'],
                'type_imprimante' => ['table' => 'parc_info_types_imprimantes', 'libelle' => 'Types d\'imprimantes'],
                'type_reseau' => ['table' => 'parc_info_types_reseaux', 'libelle' => 'Types de matériels réseau'],
                'type_mobile' => ['table' => 'parc_info_types_mobiles', 'libelle' => 'Types de terminaux mobiles'],
                'type_infrastructure' => ['table' => 'parc_info_types_infrastructures', 'libelle' => 'Types d\'infrastructures'],
            ];

            $idMappings = []; // Format: [dict_code => [old_id => new_id]]
            foreach ($dictSources as $code => $data) {
                $dict = Dictionnaire::updateOrCreate(
                    ['code' => $code],
                    ['libelle' => $data['libelle']]
                );

                $idMappings[$code] = [];

                if (Schema::hasTable($data['table'])) {
                    $oldRecords = DB::table($data['table'])->get();
                    foreach ($oldRecords as $record) {
                        $newValue = DictionnaireValeur::updateOrCreate(
                            ['dictionnaire_id' => $dict->id, 'valeur' => $record->libelle],
                            ['description' => $record->description ?? null]
                        );
                        $idMappings[$code][$record->id] = $newValue->id;
                    }
                }
            }

            // 3. Seeding Config Fields
            $this->info('Seeding fields configuration...');
            $this->seedFields($categoryModels);

            // 4. Migrating Equipment Data
            $this->info('Migrating equipment records...');
            $this->migrateEquipmentData($categoryModels, $idMappings);

            DB::commit();
            $this->info('Migration successfully completed!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Migration failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Seed fields configuration for all categories.
     */
    protected function seedFields(array $categories): void
    {
        // ORDINATEUR
        $ordId = $categories['ordinateur']->id;
        $this->createField($ordId, 'type_pc', 'Type PC', 'select', '["Portable", "Fixe", "Workstation"]', 'required|in:Portable,Fixe,Workstation', 'Configuration', 10, true, true, true, 10);
        $this->createField($ordId, 'ram_type_id', 'Type RAM', 'select', 'DICT:type_ram', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Performances', 20, true, true, true, 20);
        $this->createField($ordId, 'ram_capacite_go', 'Capacité RAM (Go)', 'number', null, 'nullable|integer|min:1', 'Performances', 30, true, true, true, 30);
        $this->createField($ordId, 'cpu_type_id', 'Génération CPU', 'select', 'DICT:type_cpu', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Performances', 40, true, true, false);
        $this->createField($ordId, 'processeur_model', 'Modèle CPU', 'text', null, 'nullable|string|max:255', 'Performances', 50, true, true, false);
        $this->createField($ordId, 'disque_type_id', 'Type de disque', 'select', 'DICT:type_disque', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Stockage', 60, true, true, false);
        $this->createField($ordId, 'stockage_capacite_go', 'Capacité Stockage (Go)', 'number', null, 'nullable|integer|min:1', 'Stockage', 70, true, true, true, 40);
        $this->createField($ordId, 'os_type_id', 'OS', 'select', 'DICT:type_os', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Système & Licences', 80, true, true, true, 50);
        $this->createField($ordId, 'licence_windows_type', 'Licence Windows', 'select', '["OEM", "CLE", "AUCUNE"]', 'nullable|string', 'Système & Licences', 90, true, true, false);
        $this->createField($ordId, 'licence_windows_cle', 'Clé Windows', 'text', null, 'nullable|string|max:255', 'Système & Licences', 100, true, true, false);
        $this->createField($ordId, 'licence_office_type', 'Licence Office', 'select', '["CLE", "AUCUNE"]', 'nullable|string', 'Système & Licences', 110, true, true, false);
        $this->createField($ordId, 'licence_office_cle', 'Clé Office', 'text', null, 'nullable|string|max:255', 'Système & Licences', 120, true, true, false);
        $this->createField($ordId, 'support_tpm2', 'TPM 2.0', 'boolean', null, 'nullable|boolean', 'Sécurité & BIOS', 130, true, true, false);
        $this->createField($ordId, 'support_secure_boot', 'Secure Boot', 'boolean', null, 'nullable|boolean', 'Sécurité & BIOS', 140, true, true, false);
        $this->createField($ordId, 'bios_version', 'Version BIOS', 'text', null, 'nullable|string|max:255', 'Sécurité & BIOS', 150, true, true, false);
        $this->createField($ordId, 'uefi_version', 'Version UEFI', 'text', null, 'nullable|string|max:255', 'Sécurité & BIOS', 160, true, true, false);
        $this->createField($ordId, 'nom_hote', 'Nom d\'hôte', 'text', null, 'nullable|string|max:255', 'Réseau', 170, true, true, false);
        $this->createField($ordId, 'compte_admin_local', 'Compte Admin Local', 'text', null, 'nullable|string|max:255', 'Réseau', 180, true, true, false);
        $this->createField($ordId, 'domaine_workgroup', 'Domaine / Workgroup', 'text', null, 'nullable|string|max:255', 'Réseau', 190, true, true, false);
        $this->createField($ordId, 'adresse_mac_ethernet', 'Adresse MAC Ethernet', 'text', null, 'nullable|string|max:255', 'Réseau', 200, true, true, false);
        $this->createField($ordId, 'adresse_mac_wifi', 'Adresse MAC Wifi', 'text', null, 'nullable|string|max:255', 'Réseau', 210, true, true, false);
        $this->createField($ordId, 'cycle_batterie', 'Cycles Batterie', 'number', null, 'nullable|integer|min:0', 'Performances', 220, true, true, false);

        // IMPRIMANTE
        $impId = $categories['imprimante']->id;
        $this->createField($impId, 'type_imprimante_id', 'Type d\'imprimante', 'select', 'DICT:type_imprimante', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Spécifications', 10, true, true, true, 10);
        $this->createField($impId, 'est_couleur', 'Support Couleur', 'boolean', null, 'nullable|boolean', 'Spécifications', 20, true, true, false);
        $this->createField($impId, 'est_multifonction', 'Multifonction', 'boolean', null, 'nullable|boolean', 'Spécifications', 30, true, true, false);
        $this->createField($impId, 'fonctions', 'Fonctions supportées', 'text', null, 'nullable|string|max:255', 'Spécifications', 40, true, true, false);
        $this->createField($impId, 'adresse_ip', 'Adresse IP', 'text', null, 'nullable|ip', 'Réseau', 50, true, true, true, 20);
        $this->createField($impId, 'snmp_community', 'Communauté SNMP', 'text', null, 'nullable|string|max:255', 'Réseau', 60, true, true, false);

        // SCANNER
        $scanId = $categories['scanner']->id;
        $this->createField($scanId, 'resolution_dpi_max', 'Résolution max (DPI)', 'number', null, 'nullable|integer', 'Spécifications', 10, true, true, true, 10);
        $this->createField($scanId, 'format_max', 'Format max', 'select', '["A4", "A3"]', 'nullable|string', 'Spécifications', 20, true, true, true, 20);
        $this->createField($scanId, 'est_recto_verso', 'Recto-Verso', 'boolean', null, 'nullable|boolean', 'Spécifications', 30, true, true, false);
        $this->createField($scanId, 'a_chargeur_auto', 'Chargeur Automatique', 'boolean', null, 'nullable|boolean', 'Spécifications', 40, true, true, false);
        $this->createField($scanId, 'type_capteur', 'Type de capteur', 'text', null, 'nullable|string|max:255', 'Spécifications', 50, true, true, false);

        // MOBILE
        $mobId = $categories['mobile']->id;
        $this->createField($mobId, 'type_mobile_id', 'Type mobile', 'select', 'DICT:type_mobile', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Général', 10, true, true, true, 10);
        $this->createField($mobId, 'imei_1', 'IMEI 1', 'text', null, 'nullable|string|max:255', 'Général', 20, true, true, true, 20);
        $this->createField($mobId, 'imei_2', 'IMEI 2', 'text', null, 'nullable|string|max:255', 'Général', 30, true, true, false);
        $this->createField($mobId, 'num_tel_associe', 'N° Téléphone associé', 'text', null, 'nullable|string|max:255', 'Réseau & Sécurité', 40, true, true, false);
        $this->createField($mobId, 'version_os', 'Version OS', 'text', null, 'nullable|string|max:255', 'Réseau & Sécurité', 50, true, true, true, 30);
        $this->createField($mobId, 'statut_mdm', 'Statut MDM', 'select', '["Enrôlé", "Non enrôlé"]', 'nullable|string', 'Réseau & Sécurité', 60, true, true, true, 40);
        $this->createField($mobId, 'capacite_batterie_mah', 'Capacité Batterie (mAh)', 'number', null, 'nullable|integer|min:0', 'Général', 70, true, true, false);
        $this->createField($mobId, 'etat_ecran', 'État de l\'écran', 'text', null, 'nullable|string|max:255', 'Général', 80, true, true, false);
        $this->createField($mobId, 'a_coque_protection', 'Coque de protection', 'boolean', null, 'nullable|boolean', 'Général', 90, true, true, false);

        // SERVEUR
        $srvId = $categories['serveur']->id;
        $this->createField($srvId, 'type_serveur', 'Type de Serveur', 'select', '["Physique", "Virtuel"]', 'required|in:Physique,Virtuel', 'Configuration', 10, true, true, false);
        $this->createField($srvId, 'role_serveur', 'Rôle du serveur', 'text', null, 'nullable|string|max:255', 'Configuration', 20, true, true, false);
        $this->createField($srvId, 'ram_type_id', 'Type RAM', 'select', 'DICT:type_ram', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Performances', 30, true, true, false);
        $this->createField($srvId, 'ram_capacite_go', 'Capacité RAM (Go)', 'number', null, 'nullable|integer|min:1', 'Performances', 40, true, true, true, 10);
        $this->createField($srvId, 'cpu_type_id', 'Type CPU', 'select', 'DICT:type_cpu', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Performances', 50, true, true, false);
        $this->createField($srvId, 'nb_processeurs', 'Nbr Processeurs', 'number', null, 'nullable|integer|min:1', 'Performances', 60, true, true, false);
        $this->createField($srvId, 'nb_coeurs_total', 'Nbr Cœurs Total', 'number', null, 'nullable|integer|min:1', 'Performances', 70, true, true, false);
        $this->createField($srvId, 'disque_type_id', 'Type de disque', 'select', 'DICT:type_disque', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Stockage', 80, true, true, false);
        $this->createField($srvId, 'stockage_capacite_go', 'Capacité Stockage (Go)', 'number', null, 'nullable|integer|min:1', 'Stockage', 90, true, true, true, 20);
        $this->createField($srvId, 'os_type_id', 'Système d\'exploitation', 'select', 'DICT:type_os', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Système', 100, true, true, true, 30);
        $this->createField($srvId, 'nom_hote', 'Nom d\'hôte', 'text', null, 'nullable|string|max:255', 'Réseau', 110, true, true, true, 40);
        $this->createField($srvId, 'domaine', 'Domaine', 'text', null, 'nullable|string|max:255', 'Réseau', 120, true, true, false);
        $this->createField($srvId, 'adresse_ip', 'Adresse IP', 'text', null, 'nullable|ip', 'Réseau', 130, true, true, true, 50);
        $this->createField($srvId, 'adresse_mac', 'Adresse MAC', 'text', null, 'nullable|string|max:255', 'Réseau', 140, true, true, false);
        $this->createField($srvId, 'hyperviseur', 'Hyperviseur', 'text', null, 'nullable|string|max:255', 'Virtualisation', 150, true, true, false);
        $this->createField($srvId, 'serveur_hote_id', 'Serveur Hôte physique', 'number', null, 'nullable|integer', 'Virtualisation', 160, true, true, false);
        $this->createField($srvId, 'u_position_depart', 'Position U départ', 'number', null, 'nullable|integer', 'Rackage', 170, true, true, false);
        $this->createField($srvId, 'u_position_fin', 'Position U fin', 'number', null, 'nullable|integer', 'Rackage', 180, true, true, false);

        // SERVEUR VIRTUEL
        $srvVirtId = $categories['serveur-virtuel']->id;
        $this->createField($srvVirtId, 'role_serveur', 'Rôle du serveur', 'text', null, 'nullable|string|max:255', 'Configuration', 10, true, true, false);
        $this->createField($srvVirtId, 'ram_type_id', 'Type RAM', 'select', 'DICT:type_ram', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Performances', 20, true, true, false);
        $this->createField($srvVirtId, 'ram_capacite_go', 'Capacité RAM (Go)', 'number', null, 'nullable|integer|min:1', 'Performances', 30, true, true, true, 10);
        $this->createField($srvVirtId, 'cpu_type_id', 'Type CPU', 'select', 'DICT:type_cpu', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Performances', 40, true, true, false);
        $this->createField($srvVirtId, 'nb_processeurs', 'vCPU', 'number', null, 'nullable|integer|min:1', 'Performances', 50, true, true, false);
        $this->createField($srvVirtId, 'nb_coeurs_total', 'Cœurs Total', 'number', null, 'nullable|integer|min:1', 'Performances', 60, true, true, false);
        $this->createField($srvVirtId, 'disque_type_id', 'Type de disque', 'select', 'DICT:type_disque', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Stockage', 70, true, true, false);
        $this->createField($srvVirtId, 'stockage_capacite_go', 'Capacité Stockage (Go)', 'number', null, 'nullable|integer|min:1', 'Stockage', 80, true, true, true, 20);
        $this->createField($srvVirtId, 'os_type_id', 'Système d\'exploitation', 'select', 'DICT:type_os', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Système', 90, true, true, true, 30);
        $this->createField($srvVirtId, 'nom_hote', 'Nom d\'hôte', 'text', null, 'nullable|string|max:255', 'Réseau', 100, true, true, true, 40);
        $this->createField($srvVirtId, 'domaine', 'Domaine', 'text', null, 'nullable|string|max:255', 'Réseau', 110, true, true, false);
        $this->createField($srvVirtId, 'adresse_ip', 'Adresse IP', 'text', null, 'nullable|ip', 'Réseau', 120, true, true, true, 50);
        $this->createField($srvVirtId, 'adresse_mac', 'Adresse MAC', 'text', null, 'nullable|string|max:255', 'Réseau', 130, true, true, false);
        $this->createField($srvVirtId, 'hyperviseur', 'Hyperviseur', 'text', null, 'nullable|string|max:255', 'Virtualisation', 140, true, true, false);
        $this->createField($srvVirtId, 'serveur_hote_id', 'Serveur Hôte physique', 'number', null, 'nullable|integer', 'Virtualisation', 150, true, true, false);

        // TELEPHONE
        $telId = $categories['telephone']->id;
        $this->createField($telId, 'est_ip', 'Téléphone IP', 'boolean', null, 'nullable|boolean', 'Spécifications', 10, true, true, false);
        $this->createField($telId, 'extension', 'N° d\'extension', 'text', null, 'nullable|string|max:50', 'Spécifications', 20, true, true, true, 10);
        $this->createField($telId, 'protocole', 'Protocole', 'select', '["SIP", "H.323", "SCCP"]', 'nullable|string', 'Spécifications', 30, true, true, false);
        $this->createField($telId, 'adresse_mac_ethernet', 'Adresse MAC Ethernet', 'text', null, 'nullable|string|max:255', 'Réseau', 40, true, true, false);
        $this->createField($telId, 'adresse_ip', 'Adresse IP', 'text', null, 'nullable|ip', 'Réseau', 50, true, true, true, 20);
        $this->createField($telId, 'modele_expansion_count', 'Modules d\'expansion', 'number', null, 'nullable|integer|min:0', 'Spécifications', 60, true, true, false);

        // EQUIPEMENT RESEAU
        $netId = $categories['reseau']->id;
        $this->createField($netId, 'type_reseau_id', 'Type matériel réseau', 'select', 'DICT:type_reseau', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Spécifications', 10, true, true, true, 10);
        $this->createField($netId, 'nb_ports', 'Nombre de ports', 'number', null, 'nullable|integer|min:1', 'Spécifications', 20, true, true, true, 20);
        $this->createField($netId, 'vitesse_max_mbps', 'Vitesse max (Mbps)', 'number', null, 'nullable|integer', 'Spécifications', 30, true, true, false);
        $this->createField($netId, 'est_poe', 'Support PoE', 'boolean', null, 'nullable|boolean', 'Spécifications', 40, true, true, false);
        $this->createField($netId, 'version_firmware', 'Version Firmware', 'text', null, 'nullable|string|max:255', 'Système', 50, true, true, false);
        $this->createField($netId, 'u_position_depart', 'U départ', 'number', null, 'nullable|integer', 'Rackage', 60, true, true, false);
        $this->createField($netId, 'u_position_fin', 'U fin', 'number', null, 'nullable|integer', 'Rackage', 70, true, true, false);
        $this->createField($netId, 'vlan_management', 'VLAN Management', 'number', null, 'nullable|integer', 'Réseau', 80, true, true, false);
        $this->createField($netId, 'adresse_ip', 'Adresse IP', 'text', null, 'nullable|ip', 'Réseau', 90, true, true, true, 30);
        $this->createField($netId, 'masque_sous_reseau', 'Masque de sous-réseau', 'text', null, 'nullable|ip', 'Réseau', 100, true, true, false);
        $this->createField($netId, 'passerelle', 'Passerelle', 'text', null, 'nullable|ip', 'Réseau', 110, true, true, false);
        $this->createField($netId, 'communaute_snmp', 'Communauté SNMP', 'text', null, 'nullable|string|max:255', 'Réseau', 120, true, true, false);
        $this->createField($netId, 'est_manageable', 'Manageable', 'boolean', null, 'nullable|boolean', 'Réseau', 130, true, true, false);

        // CAMERA IP
        $camId = $categories['camera']->id;
        $this->createField($camId, 'type_camera', 'Type caméra', 'select', '["Dôme", "Bullet", "PTZ", "Boîtier"]', 'nullable|string', 'Spécifications', 10, true, true, true, 10);
        $this->createField($camId, 'resolution', 'Résolution (ex: 4K, 5MP)', 'text', null, 'nullable|string|max:255', 'Spécifications', 20, true, true, true, 20);
        $this->createField($camId, 'adresse_ip', 'Adresse IP', 'text', null, 'nullable|ip', 'Réseau', 30, true, true, true, 30);
        $this->createField($camId, 'adresse_mac', 'Adresse MAC', 'text', null, 'nullable|string|max:255', 'Réseau', 40, true, true, false);
        $this->createField($camId, 'emplacement', 'Emplacement physique', 'text', null, 'nullable|string|max:255', 'Réseau', 50, true, true, false);

        // INFRASTRUCTURE
        $infraId = $categories['infrastructure']->id;
        $this->createField($infraId, 'type_infra_id', 'Type d\'infrastructure', 'select', 'DICT:type_infrastructure', 'nullable|exists:parc_info_dictionnaire_valeurs,id', 'Spécifications', 10, true, true, true, 10);
        $this->createField($infraId, 'puissance_va', 'Puissance (VA)', 'number', null, 'nullable|integer|min:0', 'Spécifications', 20, true, true, false);
        $this->createField($infraId, 'autonomie_minutes', 'Autonomie (min)', 'number', null, 'nullable|integer|min:0', 'Spécifications', 30, true, true, false);
        $this->createField($infraId, 'date_dernier_remplacement_batterie', 'Remplacement batterie', 'date', null, 'nullable|date', 'Maintenance', 40, true, true, false);
        $this->createField($infraId, 'nb_prises_pdu', 'Nbr prises PDU', 'number', null, 'nullable|integer|min:0', 'Spécifications', 50, true, true, false);
        $this->createField($infraId, 'u_capacite_totale', 'Capacité U (ex: 42U)', 'number', null, 'nullable|integer|min:0', 'Spécifications', 60, true, true, true, 20);
        $this->createField($infraId, 'est_redondant', 'Alimentation redondante', 'boolean', null, 'nullable|boolean', 'Spécifications', 70, true, true, false);
    }

    /**
     * Create a single field config entry.
     */
    protected function createField(int $catId, string $code, string $libelle, string $type, ?string $source, ?string $validation, string $panel, int $ordre, bool $modal, bool $show, bool $liste, int $listeOrdre = 99): void
    {
        ChampConfig::updateOrCreate(
            ['categorie_id' => $catId, 'code' => $code],
            [
                'libelle' => $libelle,
                'type_champ' => $type,
                'source_options' => $source,
                'regles_validation' => $validation,
                'nom_panel' => $panel,
                'ordre_affichage' => $ordre,
                'afficher_dans_modal' => $modal,
                'afficher_dans_show' => $show,
                'afficher_dans_liste' => $liste,
                'ordre_colonne_liste' => $listeOrdre,
            ]
        );
    }

    /**
     * Migrate physical equipment data into dynamic JSONB column.
     */
    protected function migrateEquipmentData(array $categories, array $idMappings): void
    {
        $ctiMappers = [
            'ordinateur' => [
                'table' => 'parc_info_ordinateurs',
                'dict_keys' => ['ram_type_id' => 'type_ram', 'cpu_type_id' => 'type_cpu', 'disque_type_id' => 'type_disque', 'os_type_id' => 'type_os'],
            ],
            'imprimante' => [
                'table' => 'parc_info_imprimantes',
                'dict_keys' => ['type_imprimante_id' => 'type_imprimante'],
            ],
            'scanner' => [
                'table' => 'parc_info_scanners',
                'dict_keys' => [],
            ],
            'mobile' => [
                'table' => 'parc_info_mobiles',
                'dict_keys' => ['type_mobile_id' => 'type_mobile'],
            ],
            'serveur' => [
                'table' => 'parc_info_serveurs',
                'dict_keys' => ['ram_type_id' => 'type_ram', 'cpu_type_id' => 'type_cpu', 'disque_type_id' => 'type_disque', 'os_type_id' => 'type_os'],
            ],
            'serveur-virtuel' => [
                'table' => 'parc_info_serveurs_virtuels',
                'dict_keys' => ['ram_type_id' => 'type_ram', 'cpu_type_id' => 'type_cpu', 'disque_type_id' => 'type_disque', 'os_type_id' => 'type_os'],
            ],
            'telephone' => [
                'table' => 'parc_info_telephones',
                'dict_keys' => [],
            ],
            'reseau' => [
                'table' => 'parc_info_equipements_reseaux',
                'dict_keys' => ['type_reseau_id' => 'type_reseau'],
            ],
            'camera' => [
                'table' => 'parc_info_cameras_ip',
                'dict_keys' => [],
            ],
            'infrastructure' => [
                'table' => 'parc_info_infrastructures',
                'dict_keys' => ['type_infra_id' => 'type_infrastructure'],
            ],
        ];

        foreach ($ctiMappers as $code => $map) {
            if (! Schema::hasTable($map['table'])) {
                $this->warn("Table {$map['table']} does not exist, skipping...");

                continue;
            }

            $cat = $categories[$code];
            $oldEquipments = DB::table($map['table'])->get();

            $this->info("Migrating {$oldEquipments->count()} records for category {$code}...");

            foreach ($oldEquipments as $oldRecord) {
                // Convert values to array, excluding PK
                $values = (array) $oldRecord;
                $equipId = $values['equipement_id'];
                unset($values['equipement_id']);

                // Resolve new dictionnaire value IDs
                foreach ($map['dict_keys'] as $column => $dictCode) {
                    if (isset($values[$column]) && $values[$column] !== null) {
                        $oldValId = $values[$column];
                        if (isset($idMappings[$dictCode][$oldValId])) {
                            $values[$column] = $idMappings[$dictCode][$oldValId];
                        } else {
                            $values[$column] = null;
                        }
                    }
                }

                // Update the parent equipment row
                Equipement::where('id', $equipId)->update([
                    'categorie_id' => $cat->id,
                    'champs_valeurs' => $values,
                ]);
            }
        }

        // Migrate local_id from active assignments to equipements local_id
        $this->info('Migrating physical localizations (local_id) from active assignments...');
        $activeAssignments = DB::table('parc_info_affectation_equipements')
            ->where('statut', true)
            ->whereNotNull('local_id')
            ->get();

        foreach ($activeAssignments as $assignment) {
            Equipement::where('id', $assignment->equipement_id)->update([
                'local_id' => $assignment->local_id,
            ]);
        }
    }
}
