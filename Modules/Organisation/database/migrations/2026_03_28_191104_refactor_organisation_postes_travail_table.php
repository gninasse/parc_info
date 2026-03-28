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
            $table->string('niveau_rattachement')->after('libelle')->nullable()->comment('direction, service, unite');

            // Gestion de agent_id -> dossier_employe_id
            $table->dropForeign(['agent_id']);
            $table->dropIndex(['agent_id']);
            $table->renameColumn('agent_id', 'dossier_employe_id');

            // Hiérarchie localisation
            $table->foreignId('batiment_id')->nullable()->after('unite_id')->constrained('organisation_batiments')->onDelete('set null');
            $table->foreignId('etage_id')->nullable()->after('batiment_id')->constrained('organisation_etages')->onDelete('set null');

            // Rendre direction_id et service_id nullables
            $table->unsignedBigInteger('direction_id')->nullable()->change();
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });

        Schema::table('organisation_postes_travail', function (Blueprint $table) {
            $table->foreign('dossier_employe_id')->references('id')->on('grh_dossiers_employes')->onDelete('set null');
            $table->index(['dossier_employe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisation_postes_travail', function (Blueprint $table) {
            $table->dropForeign(['dossier_employe_id']);
            $table->dropIndex(['dossier_employe_id']);

            $table->dropForeign(['batiment_id']);
            $table->dropForeign(['etage_id']);
            $table->dropColumn(['niveau_rattachement', 'batiment_id', 'etage_id']);

            $table->renameColumn('dossier_employe_id', 'agent_id');

            $table->unsignedBigInteger('direction_id')->nullable(false)->change();
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });

        Schema::table('organisation_postes_travail', function (Blueprint $table) {
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['agent_id']);
        });
    }
};
