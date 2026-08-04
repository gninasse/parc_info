<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Demande Ibrahim (04/08/2026) : l'état de l'unité (référentiel ParcInfo
     * bon/passable/mauvais/avarie) se saisit au wizard de référencement,
     * rangée par rangée, et est hérité par la fiche créée à la validation
     * (au lieu du « bon » systématique).
     */
    public function up(): void
    {
        Schema::table('stock_tampon_equipements', function (Blueprint $table) {
            $table->string('etat')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stock_tampon_equipements', function (Blueprint $table) {
            $table->dropColumn('etat');
        });
    }
};
