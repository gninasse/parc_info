<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SFD §6.2 : état courant du rattachement (l'historique est dans les
     * mouvements) — une unité est rattachée à au plus un magasin.
     */
    public function up(): void
    {
        Schema::create('stock_equipements_magasins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->unique()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->dateTime('date_rattachement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_equipements_magasins');
    }
};
