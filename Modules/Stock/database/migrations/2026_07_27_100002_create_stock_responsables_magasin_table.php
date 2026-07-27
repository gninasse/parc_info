<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Information métier uniquement (responsable principal / adjoint) :
        // la matrice de droits par magasin de la spec a été abandonnée au
        // profit de spatie/laravel-permission seul.
        Schema::create('stock_responsables_magasin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->foreignId('employe_id')->constrained('grh_dossiers_employes')->cascadeOnDelete();
            $table->enum('role', ['principal', 'adjoint'])->default('adjoint');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->unique(['magasin_id', 'employe_id'], 'unique_responsable_par_magasin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_responsables_magasin');
    }
};
