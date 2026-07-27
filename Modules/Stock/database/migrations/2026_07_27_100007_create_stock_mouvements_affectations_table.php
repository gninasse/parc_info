<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trace de l'affectation ParcInfo créée lors d'une sortie (F4).
        Schema::create('stock_mouvements_affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mouvement_id')->unique()->constrained('stock_mouvements')->cascadeOnDelete();
            $table->enum('type_affectation_parcinfo', ['EQUIPEMENT', 'CONSOMMABLE', 'LICENCE']);
            $table->unsignedBigInteger('affectation_id');
            $table->enum('type_cible', ['EMPLOYE', 'SERVICE', 'DIRECTION', 'UNITE', 'POSTE']);
            $table->unsignedBigInteger('cible_id');
            $table->timestamps();

            $table->index(['type_affectation_parcinfo', 'affectation_id'], 'idx_affectation_parcinfo');
            $table->index(['type_cible', 'cible_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_mouvements_affectations');
    }
};
