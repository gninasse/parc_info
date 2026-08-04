<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Préférences du module par utilisateur (demande Ibrahim 04/08/2026) :
     * magasin par défaut, pré-sélectionné dans les entrées, sorties et
     * transferts. Table portée par le module — la table users du Core n'est
     * pas modifiée.
     */
    public function up(): void
    {
        Schema::create('stock_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('magasin_defaut_id')->nullable()->constrained('stock_magasins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_preferences');
    }
};
