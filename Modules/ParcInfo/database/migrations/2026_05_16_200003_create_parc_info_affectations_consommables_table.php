<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_affectations_consommables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consommable_id')->constrained('parc_info_consommables')->onDelete('cascade');
            $table->foreignId('equipement_id')->constrained('parc_info_equipements')->onDelete('cascade');
            $table->unsignedInteger('quantite_fournie');
            $table->date('date_affectation');
            $table->date('date_remplacement_prochain_prevu')->nullable();
            $table->unsignedInteger('cycle_remplacement_jours')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['equipement_id']);
            $table->index(['date_remplacement_prochain_prevu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_affectations_consommables');
    }
};
