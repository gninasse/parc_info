<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complément du re-routage SFD Catalogue §2.2 : article_id étant désormais la
 * FK de référence des affectations, consommable_id devient une colonne legacy
 * nullable (les affectations créées après migration ne pointent plus
 * parc_info_consommables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parc_info_affectations_consommables', function (Blueprint $table) {
            $table->unsignedBigInteger('consommable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('parc_info_affectations_consommables', function (Blueprint $table) {
            $table->unsignedBigInteger('consommable_id')->nullable(false)->change();
        });
    }
};
