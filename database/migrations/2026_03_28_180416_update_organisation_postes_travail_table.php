<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organisation_postes_travail', function (Blueprint $table) {
            $table->string('niveau_rattachement', 50)->nullable()->after('description')->comment('direction, service, unite');

            // On enlève l'ancienne contrainte et on renomme
            $table->dropForeign(['agent_id']);
            $table->renameColumn('agent_id', 'dossier_employe_id');

            // Nouvelle contrainte vers grh_dossiers_employes
            $table->foreign('dossier_employe_id')->references('id')->on('grh_dossiers_employes')->onDelete('set null');

            // Modification des colonnes existantes pour les rendre nullable si nécessaire (ex: unite_id)
            $table->unsignedBigInteger('direction_id')->nullable()->change();
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisation_postes_travail', function (Blueprint $table) {
            $table->dropColumn('niveau_rattachement');

            $table->dropForeign(['dossier_employe_id']);
            $table->renameColumn('dossier_employe_id', 'agent_id');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');

            $table->unsignedBigInteger('direction_id')->nullable(false)->change();
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });
    }
};
