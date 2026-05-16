<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logiciel_id')->constrained('parc_info_logiciels')->onDelete('cascade');
            $table->string('cle_licence')->nullable();
            $table->string('numero_contrat')->nullable()->unique();
            $table->foreignId('contrat_maintenance_id')->nullable()->constrained('parc_info_contrats_maintenances')->onDelete('set null');
            
            $table->enum('type_activation', ['volume', 'concurrent', 'subscription', 'free'])->default('volume');
            $table->enum('modele_licencing', ['device', 'user', 'concurrent', 'named'])->default('device');
            
            $table->unsignedInteger('nombre_postes_accordes')->default(1);
            $table->unsignedInteger('nombre_postes_utilises')->default(0);
            
            $table->date('date_acquisition');
            $table->date('date_activation')->nullable();
            $table->date('date_expiration');
            $table->date('date_renouvellement_prochain')->nullable();
            
            $table->decimal('cout_unitaire', 10, 2)->nullable();
            $table->decimal('cout_total', 12, 2)->nullable();
            $table->string('devise', 3)->default('EUR');
            
            $table->foreignId('fournisseur_id')->constrained('parc_info_fournisseurs')->onDelete('restrict');
            $table->foreignId('contact_support_id')->nullable()->constrained('parc_info_contacts')->onDelete('set null');
            
            $table->enum('statut', ['actif', 'expire', 'en_renouvellement', 'suspendu'])->default('actif');
            $table->string('conditions_utilisation')->nullable();
            
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['logiciel_id', 'date_expiration']);
            $table->index(['statut', 'actif']);
            $table->index(['fournisseur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_licences');
    }
};
