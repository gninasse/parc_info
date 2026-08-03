<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 : niveau maintenu sous lockForUpdate (D9), CHECK >= 0 en
     * double filet ; seuil local nullable (cascade : local →
     * catalogue_articles.seuil_defaut → aucun).
     */
    public function up(): void
    {
        Schema::create('stock_niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->foreignId('article_id')->constrained('catalogue_articles')->restrictOnDelete();
            $table->decimal('quantite', 12, 2)->default(0);
            $table->decimal('seuil', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['magasin_id', 'article_id']);
        });

        SchemaChecks::ajouter('stock_niveaux', [
            'chk_niveaux_quantite_positive' => 'quantite >= 0',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_niveaux');
    }
};
