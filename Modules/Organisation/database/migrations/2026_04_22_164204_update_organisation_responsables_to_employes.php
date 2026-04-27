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
        // 1. Modification pour les Directions (responsable_id)
        Schema::table('organisation_directions', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
            $table->foreign('responsable_id')->references('id')->on('grh_dossiers_employes')->onDelete('set null');
        });

        // 2. Modification pour les Services (chef_service_id)
        Schema::table('organisation_services', function (Blueprint $table) {
            $table->dropForeign(['chef_service_id']);
            $table->foreign('chef_service_id')->references('id')->on('grh_dossiers_employes')->onDelete('set null');
        });

        // 3. Modification pour les Unités (major_id)
        Schema::table('organisation_unites', function (Blueprint $table) {
            $table->dropForeign(['major_id']);
            $table->foreign('major_id')->references('id')->on('grh_dossiers_employes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisation_directions', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
            $table->foreign('responsable_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('organisation_services', function (Blueprint $table) {
            $table->dropForeign(['chef_service_id']);
            $table->foreign('chef_service_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('organisation_unites', function (Blueprint $table) {
            $table->dropForeign(['major_id']);
            $table->foreign('major_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
