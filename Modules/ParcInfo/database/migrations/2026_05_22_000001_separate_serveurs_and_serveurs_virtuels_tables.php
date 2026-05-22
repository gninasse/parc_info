<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new table for virtual servers
        Schema::create('parc_info_serveurs_virtuels', function (Blueprint $table) {
            $table->unsignedBigInteger('equipement_id')->primary();
            $table->foreign('equipement_id')->references('id')->on('parc_info_equipements')->cascadeOnDelete();
            $table->text('role_serveur')->nullable();
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
            $table->text('hyperviseur')->nullable();
            $table->unsignedBigInteger('serveur_hote_id')->nullable();
            $table->foreign('serveur_hote_id')->references('equipement_id')->on('parc_info_serveurs')->nullOnDelete();
        });

        // 2. Transfer existing virtual server records to the new table
        $virtualServers = DB::table('parc_info_serveurs')->where('type_serveur', 'Virtuel')->get();
        foreach ($virtualServers as $vs) {
            DB::table('parc_info_serveurs_virtuels')->insert([
                'equipement_id' => $vs->equipement_id,
                'role_serveur' => $vs->role_serveur,
                'ram_type_id' => $vs->ram_type_id,
                'ram_capacite_go' => $vs->ram_capacite_go,
                'cpu_type_id' => $vs->cpu_type_id,
                'nb_processeurs' => $vs->nb_processeurs,
                'nb_coeurs_total' => $vs->nb_coeurs_total,
                'disque_type_id' => $vs->disque_type_id,
                'stockage_capacite_go' => $vs->stockage_capacite_go,
                'os_type_id' => $vs->os_type_id,
                'nom_hote' => $vs->nom_hote,
                'domaine' => $vs->domaine,
                'adresse_ip' => $vs->adresse_ip,
                'adresse_mac' => $vs->adresse_mac,
                'hyperviseur' => $vs->hyperviseur,
                'serveur_hote_id' => $vs->serveur_hote_id,
            ]);
        }

        // 3. Delete virtual server records from the old table
        DB::table('parc_info_serveurs')->where('type_serveur', 'Virtuel')->delete();

        // 4. Drop columns and foreign key from physical servers table
        Schema::table('parc_info_serveurs', function (Blueprint $table) {
            $table->dropForeign(['serveur_hote_id']);
            $table->dropColumn('serveur_hote_id');
            $table->dropColumn('type_serveur');
        });
    }

    public function down(): void
    {
        // 1. Restore type_serveur and serveur_hote_id to physical servers table
        Schema::table('parc_info_serveurs', function (Blueprint $table) {
            $table->text('type_serveur')->default('Physique');
            $table->unsignedBigInteger('serveur_hote_id')->nullable();
            $table->foreign('serveur_hote_id')->references('equipement_id')->on('parc_info_serveurs')->nullOnDelete();
        });

        // 2. Transfer virtual servers back
        $virtualServers = DB::table('parc_info_serveurs_virtuels')->get();
        foreach ($virtualServers as $vs) {
            DB::table('parc_info_serveurs')->insert([
                'equipement_id' => $vs->equipement_id,
                'type_serveur' => 'Virtuel',
                'role_serveur' => $vs->role_serveur,
                'ram_type_id' => $vs->ram_type_id,
                'ram_capacite_go' => $vs->ram_capacite_go,
                'cpu_type_id' => $vs->cpu_type_id,
                'nb_processeurs' => $vs->nb_processeurs,
                'nb_coeurs_total' => $vs->nb_coeurs_total,
                'disque_type_id' => $vs->disque_type_id,
                'stockage_capacite_go' => $vs->stockage_capacite_go,
                'os_type_id' => $vs->os_type_id,
                'nom_hote' => $vs->nom_hote,
                'domaine' => $vs->domaine,
                'adresse_ip' => $vs->adresse_ip,
                'adresse_mac' => $vs->adresse_mac,
                'hyperviseur' => $vs->hyperviseur,
                'serveur_hote_id' => $vs->serveur_hote_id,
            ]);
        }

        // 3. Drop the new table
        Schema::dropIfExists('parc_info_serveurs_virtuels');
    }
};
