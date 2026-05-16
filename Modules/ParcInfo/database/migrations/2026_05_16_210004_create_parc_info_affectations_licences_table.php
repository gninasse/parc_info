<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_affectations_licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licence_id')->constrained('parc_info_licences')->onDelete('cascade');
            $table->foreignId('equipement_id')->nullable()->constrained('parc_info_equipements')->onDelete('set null');
            $table->foreignId('employe_id')->nullable()->constrained('grh_dossiers_employes')->onDelete('set null');
            
            $table->enum('type_affectation', ['device', 'user', 'concurrent'])->default('device');
            
            $table->date('date_affectation');
            $table->date('date_fin_affectation')->nullable();
            
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['licence_id', 'actif']);
            $table->index(['employe_id', 'date_fin_affectation']);
            $table->index(['equipement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_affectations_licences');
    }
};
