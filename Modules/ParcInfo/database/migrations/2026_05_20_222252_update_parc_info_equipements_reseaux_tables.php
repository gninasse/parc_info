<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parc_info_equipements_reseaux', function (Blueprint $table) {
            $table->renameColumn('nb_ports', 'nombre_ports');
            $table->dropColumn('vitesse_max_mbps');
            $table->string('vitesse_port')->nullable();

            $table->renameColumn('est_poe', 'support_poe');
            $table->integer('poe_budget_watts')->nullable();

            $table->boolean('support_vlan')->default(false);
            $table->boolean('support_stp')->default(false);
            $table->boolean('support_lacp')->default(false);

            $table->renameColumn('est_manageable', 'support_snmp');

            $table->renameColumn('version_firmware', 'firmware_version');
            $table->renameColumn('adresse_ip', 'adresse_ip_management');

            $table->renameColumn('communaute_snmp', 'snmp_community');
            $table->string('snmp_version')->nullable();

            $table->dropColumn('vlan_management');
            $table->text('vlans_configures')->nullable();

            $table->string('modele_reference')->nullable();
            $table->integer('nombre_ports_uplink')->nullable();
            $table->boolean('support_redundance')->default(false);
            $table->string('location_detail')->nullable();

            $table->dropColumn(['u_position_depart', 'u_position_fin', 'masque_sous_reseau', 'passerelle']);
        });

        Schema::rename('parc_info_equipements_reseaux', 'parc_info_equipements_reseau');
        Schema::rename('parc_info_types_reseaux', 'parc_info_types_reseau');
    }

    public function down(): void
    {
        // Revert...
        Schema::rename('parc_info_equipements_reseau', 'parc_info_equipements_reseaux');
        Schema::rename('parc_info_types_reseau', 'parc_info_types_reseaux');
    }
};
