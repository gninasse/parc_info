<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. TABLES DE RÉFÉRENCE ────────────────────────────────────────────

        Schema::create('parc_info_marques', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique();
            $table->timestamps();
        });

        Schema::create('parc_info_types_rams', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // DDR4, DDR5
            $table->timestamps();
        });

        Schema::create('parc_info_types_cpus', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // Intel Core i5, AMD Ryzen 7
            $table->timestamps();
        });

        Schema::create('parc_info_types_disques', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // SSD NVMe, HDD, SSD SATA
            $table->timestamps();
        });

        Schema::create('parc_info_types_os', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // Windows 11 Pro, Ubuntu 22.04
            $table->timestamps();
        });

        Schema::create('parc_info_types_imprimantes', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // LASER, JET_ENCRE, MATRICIELLE
            $table->timestamps();
        });

        Schema::create('parc_info_types_reseaux', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // Switch, Routeur, Point d'accès, Firewall
            $table->timestamps();
        });

        Schema::create('parc_info_types_mobiles', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // Tablette, Smartphone, Lecteur code-barre
            $table->timestamps();
        });

        Schema::create('parc_info_types_infrastructures', function (Blueprint $table) {
            $table->id();
            $table->text('libelle')->unique(); // Onduleur, PDU, Rack, Panneau de brassage
            $table->timestamps();
        });

        // ── 2. ÉQUIPEMENTS (TABLE PARENTE) ────────────────────────────────────

        Schema::create('parc_info_equipements', function (Blueprint $table) {
            $table->id();
            $table->text('code_inventaire')->unique();
            $table->text('numero_serie')->unique();
            $table->foreignId('marque_id')->nullable()->constrained('parc_info_marques')->nullOnDelete();
            $table->text('modele');
            $table->date('date_acquisition');
            $table->date('date_mise_en_service')->nullable();
            $table->decimal('valeur_achat', 12, 2)->nullable();
            $table->integer('duree_vie_probable')->nullable()->comment('en années');
            $table->date('date_fin_garantie')->nullable();
            $table->text('statut')->comment('en_stock, en_service, en_reparation, perdu, reforme');
            $table->text('etat')->comment('bon, passable, mauvais, avarie');
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        // ── 3. AFFECTATIONS ───────────────────────────────────────────────────

        Schema::create('parc_info_affectation_equipements', function (Blueprint $table) {
            $table->id();
            $table->text('code')->unique();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->foreignId('equipement_id')->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->boolean('statut')->default(false);
            $table->text('type_affectation')->nullable()->comment('TEMPORAIRE, PERMANENTE');
            $table->text('type_cible')->nullable()->comment('EMPLOYE, POSTE, LOCAL');
            $table->foreignId('dossier_employe_id')->nullable()->constrained('grh_dossiers_employes')->nullOnDelete();
            $table->foreignId('poste_travail_id')->nullable()->constrained('organisation_postes_travail')->nullOnDelete();
            $table->foreignId('local_id')->nullable()->constrained('organisation_locaux')->nullOnDelete();
            $table->text('niveau_rattachement')->nullable()->comment('DIRECTION, SERVICE, UNITE');
            $table->foreignId('direction_id')->nullable()->constrained('organisation_directions')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('organisation_services')->nullOnDelete();
            $table->foreignId('unite_id')->nullable()->constrained('organisation_unites')->nullOnDelete();
            $table->timestamps();
        });

        // ── 4. SPÉCIALISATIONS ────────────────────────────────────────────────

        Schema::create('parc_info_ordinateurs', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->text('type_pc')->comment('Portable, Fixe, Workstation');
            $table->foreignId('ram_type_id')->nullable()->constrained('parc_info_types_rams')->nullOnDelete();
            $table->integer('ram_capacite_go')->nullable();
            $table->foreignId('cpu_type_id')->nullable()->constrained('parc_info_types_cpus')->nullOnDelete();
            $table->text('processeur_model')->nullable();
            $table->foreignId('disque_type_id')->nullable()->constrained('parc_info_types_disques')->nullOnDelete();
            $table->integer('stockage_capacite_go')->nullable();
            $table->foreignId('os_type_id')->nullable()->constrained('parc_info_types_os')->nullOnDelete();
            // Licences
            $table->text('licence_windows_type')->nullable()->comment('OEM, CLE, AUCUNE');
            $table->text('licence_windows_cle')->nullable();
            $table->text('licence_office_type')->nullable()->comment('CLE, AUCUNE');
            $table->text('licence_office_cle')->nullable();
            // Sécurité & BIOS
            $table->boolean('support_tpm2')->default(false);
            $table->boolean('support_secure_boot')->default(false);
            $table->text('bios_version')->nullable();
            $table->text('uefi_version')->nullable();
            // Réseau
            $table->text('nom_hote')->nullable();
            $table->text('domaine_workgroup')->nullable();
            $table->text('adresse_mac_wifi')->nullable();
            $table->text('adresse_mac_ethernet')->nullable();
            $table->integer('cycle_batterie')->nullable()->comment('Pour les portables');
        });

        Schema::create('parc_info_mobiles', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->foreignId('type_mobile_id')->nullable()->constrained('parc_info_types_mobiles')->nullOnDelete();
            $table->text('imei_1')->nullable()->unique();
            $table->text('imei_2')->nullable()->unique();
            $table->text('num_tel_associe')->nullable();
            $table->text('version_os')->nullable();
            $table->text('statut_mdm')->nullable()->comment('Enrôlé, Non enrôlé');
            $table->integer('capacite_batterie_mah')->nullable();
            $table->text('etat_ecran')->nullable();
            $table->boolean('a_coque_protection')->default(true);
        });

        Schema::create('parc_info_imprimantes', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->foreignId('type_imprimante_id')->nullable()->constrained('parc_info_types_imprimantes')->nullOnDelete();
            $table->boolean('est_couleur')->default(false);
            $table->boolean('est_multifonction')->default(false);
            $table->text('fonctions')->nullable()->comment('Scan, Print, Copy, Fax');
            $table->ipAddress('adresse_ip')->nullable();
            $table->text('snmp_community')->nullable();
        });

        Schema::create('parc_info_serveurs', function (Blueprint $table) {
            // PK + FK vers equipements — déclarées séparément pour que PostgreSQL
            // reconnaisse equipement_id comme unique avant l'auto-référence
            $table->unsignedBigInteger('equipement_id')->primary();
            $table->foreign('equipement_id')->references('id')->on('parc_info_equipements')->cascadeOnDelete();
            $table->text('type_serveur')->comment('Physique, Virtuel');
            $table->text('role_serveur')->nullable()->comment('Application, Base de données, Fichiers, Web, AD/DC');
            $table->foreignId('ram_type_id')->nullable()->constrained('parc_info_types_rams')->nullOnDelete();
            $table->integer('ram_capacite_go')->nullable();
            $table->foreignId('cpu_type_id')->nullable()->constrained('parc_info_types_cpus')->nullOnDelete();
            $table->integer('nb_processeurs')->nullable();
            $table->integer('nb_coeurs_total')->nullable();
            $table->foreignId('disque_type_id')->nullable()->constrained('parc_info_types_disques')->nullOnDelete();
            $table->integer('stockage_capacite_go')->nullable();
            $table->foreignId('os_type_id')->nullable()->constrained('parc_info_types_os')->nullOnDelete();
            $table->text('nom_hote')->nullable();
            $table->text('domaine')->nullable();
            $table->ipAddress('adresse_ip')->nullable();
            $table->text('adresse_mac')->nullable();
            $table->text('hyperviseur')->nullable()->comment('VMware ESXi, Hyper-V, Proxmox');
            $table->unsignedBigInteger('serveur_hote_id')->nullable();
            $table->integer('u_position_depart')->nullable();
            $table->integer('u_position_fin')->nullable();
        });

        // Auto-référence ajoutée après création de la table pour que la PK
        // soit déjà enregistrée comme contrainte unique par PostgreSQL
        Schema::table('parc_info_serveurs', function (Blueprint $table) {
            $table->foreign('serveur_hote_id')->references('equipement_id')->on('parc_info_serveurs')->nullOnDelete();
        });

        Schema::create('parc_info_equipements_reseaux', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->foreignId('type_reseau_id')->nullable()->constrained('parc_info_types_reseaux')->nullOnDelete();
            $table->integer('nb_ports')->nullable();
            $table->integer('vitesse_max_mbps')->nullable();
            $table->boolean('est_poe')->default(false);
            $table->text('version_firmware')->nullable();
            $table->integer('u_position_depart')->nullable();
            $table->integer('u_position_fin')->nullable();
            $table->integer('vlan_management')->nullable();
            $table->ipAddress('adresse_ip')->nullable();
            $table->ipAddress('masque_sous_reseau')->nullable();
            $table->ipAddress('passerelle')->nullable();
            $table->text('communaute_snmp')->nullable();
            $table->boolean('est_manageable')->default(true);
        });

        Schema::create('parc_info_telephones', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->boolean('est_ip')->default(true);
            $table->text('extension')->nullable()->unique();
            $table->text('protocole')->nullable()->comment('SIP, H.323, SCCP');
            $table->text('adresse_mac_ethernet')->nullable()->unique();
            $table->ipAddress('adresse_ip')->nullable();
            $table->integer('modele_expansion_count')->default(0);
        });

        Schema::create('parc_info_infrastructures', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->foreignId('type_infra_id')->nullable()->constrained('parc_info_types_infrastructures')->nullOnDelete();
            $table->integer('puissance_va')->nullable()->comment('Pour les onduleurs');
            $table->integer('autonomie_minutes')->nullable();
            $table->date('date_dernier_remplacement_batterie')->nullable();
            $table->integer('nb_prises_pdu')->nullable();
            $table->integer('u_capacite_totale')->nullable()->comment('Pour les racks, ex: 42U');
            $table->boolean('est_redondant')->default(false);
        });

        Schema::create('parc_info_scanners', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->integer('resolution_dpi_max')->nullable();
            $table->text('format_max')->nullable()->comment('A4, A3');
            $table->boolean('est_recto_verso')->default(false);
            $table->boolean('a_chargeur_auto')->default(false);
            $table->text('type_capteur')->nullable()->comment('CIS, CCD');
        });

        // ── 5. HISTORIQUE ─────────────────────────────────────────────────────

        Schema::create('parc_info_historique_changements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->timestamp('date_changement')->useCurrent();
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->text('type_changement')->comment('STATUT, ETAT, AFFECTATION, TECHNIQUE');
            $table->text('ancien_statut')->nullable();
            $table->text('nouveau_statut')->nullable();
            $table->text('ancien_etat')->nullable();
            $table->text('nouvel_etat')->nullable();
            $table->text('motif');
            $table->text('reference_document')->nullable()->comment('PV, BMD, BST');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_historique_changements');
        Schema::dropIfExists('parc_info_scanners');
        Schema::dropIfExists('parc_info_infrastructures');
        Schema::dropIfExists('parc_info_telephones');
        Schema::dropIfExists('parc_info_equipements_reseaux');
        // Supprimer la FK auto-référente avant de dropper la table
        Schema::table('parc_info_serveurs', function (Blueprint $table) {
            $table->dropForeign(['serveur_hote_id']);
        });
        Schema::dropIfExists('parc_info_serveurs');
        Schema::dropIfExists('parc_info_imprimantes');
        Schema::dropIfExists('parc_info_mobiles');
        Schema::dropIfExists('parc_info_ordinateurs');
        Schema::dropIfExists('parc_info_affectation_equipements');
        Schema::dropIfExists('parc_info_equipements');
        Schema::dropIfExists('parc_info_types_infrastructures');
        Schema::dropIfExists('parc_info_types_mobiles');
        Schema::dropIfExists('parc_info_types_reseaux');
        Schema::dropIfExists('parc_info_types_imprimantes');
        Schema::dropIfExists('parc_info_types_os');
        Schema::dropIfExists('parc_info_types_disques');
        Schema::dropIfExists('parc_info_types_cpus');
        Schema::dropIfExists('parc_info_types_rams');
        Schema::dropIfExists('parc_info_marques');
    }
};
