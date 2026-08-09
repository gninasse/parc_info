<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Re-routage des référentiels ParcInfo vers le module Catalogue (SFD §9.2).
 *
 * - parc_info_affectations_consommables : nouvelle colonne article_id, remplie
 *   par la commande catalogue:migrate-parcinfo (correspondance par code).
 * - parc_info_licences : le FK fournisseur_id vers parc_info_fournisseurs est
 *   retiré ; après remappage des ids par la commande, celle-ci pose le FK vers
 *   catalogue_fournisseurs (PostgreSQL).
 *
 * Datée APRÈS les migrations du module Catalogue : les tables catalogue_*
 * doivent exister pour le FK article_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parc_info_affectations_consommables', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->after('consommable_id')
                ->constrained('catalogue_articles')->onDelete('restrict');
        });

        Schema::table('parc_info_licences', function (Blueprint $table) {
            $table->dropForeign(['fournisseur_id']);
        });
    }

    public function down(): void
    {
        Schema::table('parc_info_affectations_consommables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
        });

        Schema::table('parc_info_licences', function (Blueprint $table) {
            $table->foreign('fournisseur_id')->references('id')->on('parc_info_fournisseurs')->onDelete('restrict');
        });
    }
};
