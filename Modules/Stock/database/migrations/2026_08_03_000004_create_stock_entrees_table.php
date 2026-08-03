<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SFD §6.2 : socle commun des en-têtes + nature livraison/retour,
     * fournisseur Catalogue, observation à motifs types, bénéficiaire
     * d'origine optionnel pour les retours (6 FK set null + type + libellé).
     */
    public function up(): void
    {
        Schema::create('stock_entrees', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable()->unique(); // attribué à la validation (S4)
            $table->date('date_document');
            $table->enum('statut', ['BROUILLON', 'REFERENCEMENT', 'VALIDE', 'ANNULE'])->default('BROUILLON');
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->enum('nature', ['livraison', 'retour'])->default('livraison');
            $table->foreignId('fournisseur_id')->nullable()->constrained('catalogue_fournisseurs')->restrictOnDelete();
            $table->string('reference_externe')->nullable();
            $table->string('observation_type')->nullable(); // motifs types de config (stock.motifs_observation_entree)
            $table->text('observation')->nullable();

            // Bénéficiaire d'origine (retours uniquement, optionnel)
            $table->string('beneficiaire_type')->nullable();
            $table->foreignId('beneficiaire_direction_id')->nullable()->constrained('organisation_directions')->nullOnDelete();
            $table->foreignId('beneficiaire_service_id')->nullable()->constrained('organisation_services')->nullOnDelete();
            $table->foreignId('beneficiaire_unite_id')->nullable()->constrained('organisation_unites')->nullOnDelete();
            $table->foreignId('beneficiaire_poste_id')->nullable()->constrained('organisation_postes_travail')->nullOnDelete();
            $table->foreignId('beneficiaire_local_id')->nullable()->constrained('organisation_locaux')->nullOnDelete();
            $table->foreignId('beneficiaire_employe_id')->nullable()->constrained('grh_dossiers_employes')->nullOnDelete();
            $table->string('beneficiaire_libelle')->nullable(); // dénormalisé à la validation

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('valide_le')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_entrees');
    }
};
