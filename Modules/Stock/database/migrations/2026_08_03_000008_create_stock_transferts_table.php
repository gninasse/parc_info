<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 : magasins source et cible distincts (CHECK),
     * « Transporté par » optionnel (nom libre + employé Grh).
     */
    public function up(): void
    {
        Schema::create('stock_transferts', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable()->unique();
            $table->date('date_document');
            $table->enum('statut', ['BROUILLON', 'POINTAGE', 'VALIDE', 'ANNULE'])->default('BROUILLON');
            $table->foreignId('magasin_source_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->foreignId('magasin_cible_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->string('transporte_par_nom')->nullable();
            $table->foreignId('transporte_par_employe_id')->nullable()->constrained('grh_dossiers_employes')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('valide_le')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_document']);
        });

        SchemaChecks::ajouter('stock_transferts', [
            'chk_transferts_magasins_distincts' => 'magasin_source_id <> magasin_cible_id',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transferts');
    }
};
