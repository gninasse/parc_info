<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 : article_id XOR equipement_id (CHECK) — un article de nature
     * « equipement » est une ligne « modèle × N » ; equipement_id sert au
     * rattachement d'une unité existante non rattachée.
     */
    public function up(): void
    {
        Schema::create('stock_lignes_entrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entree_id')->constrained('stock_entrees')->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained('catalogue_articles')->restrictOnDelete();
            $table->foreignId('equipement_id')->nullable()->constrained('parc_info_equipements')->restrictOnDelete();
            $table->decimal('quantite', 12, 2);
            $table->decimal('cout_unitaire', 14, 2)->nullable();
            $table->timestamps();
        });

        SchemaChecks::ajouter('stock_lignes_entrees', [
            'chk_lignes_entrees_article_xor_equipement' => '(article_id IS NOT NULL AND equipement_id IS NULL) OR (article_id IS NULL AND equipement_id IS NOT NULL)',
            'chk_lignes_entrees_quantite_positive' => 'quantite > 0',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lignes_entrees');
    }
};
