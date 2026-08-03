<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SFD §6.2 : motif typé de config (+ texte requis si « Autre »),
     * remise_reelle_le pour le motif « Urgence hors ouverture »,
     * bénéficiaire obligatoire (type + 6 FK set null + libellé dénormalisé),
     * « Remis à » (nom libre + employé Grh optionnel).
     */
    public function up(): void
    {
        Schema::create('stock_sorties', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable()->unique();
            $table->date('date_document');
            $table->enum('statut', ['BROUILLON', 'POINTAGE', 'VALIDE', 'ANNULE'])->default('BROUILLON');
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->string('motif_type'); // stock.motifs_sortie (config v1)
            $table->text('motif_texte')->nullable(); // requis si « Autre » (garde applicative)
            $table->dateTime('remise_reelle_le')->nullable(); // motif « Urgence hors ouverture »

            // Bénéficiaire obligatoire (6 types — D5)
            $table->string('beneficiaire_type');
            $table->foreignId('beneficiaire_direction_id')->nullable()->constrained('organisation_directions')->nullOnDelete();
            $table->foreignId('beneficiaire_service_id')->nullable()->constrained('organisation_services')->nullOnDelete();
            $table->foreignId('beneficiaire_unite_id')->nullable()->constrained('organisation_unites')->nullOnDelete();
            $table->foreignId('beneficiaire_poste_id')->nullable()->constrained('organisation_postes_travail')->nullOnDelete();
            $table->foreignId('beneficiaire_local_id')->nullable()->constrained('organisation_locaux')->nullOnDelete();
            $table->foreignId('beneficiaire_employe_id')->nullable()->constrained('grh_dossiers_employes')->nullOnDelete();
            $table->string('beneficiaire_libelle')->nullable(); // dénormalisé à la validation

            $table->string('remis_a_nom')->nullable(); // requis à la validation si équipements
            $table->foreignId('remis_a_employe_id')->nullable()->constrained('grh_dossiers_employes')->nullOnDelete();

            $table->text('observation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('valide_le')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sorties');
    }
};
