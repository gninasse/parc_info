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
        Schema::table('parc_info_equipements', function (Blueprint $table) {
            $table->foreignId('categorie_id')->nullable()->constrained('parc_info_categories_equipements')->nullOnDelete();
            $table->jsonb('champs_valeurs')->nullable();
            $table->foreignId('local_id')->nullable()->constrained('organisation_locaux')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parc_info_equipements', function (Blueprint $table) {
            $table->dropForeign(['categorie_id']);
            $table->dropForeign(['local_id']);

            $table->dropColumn(['categorie_id', 'champs_valeurs', 'local_id']);
        });
    }
};
