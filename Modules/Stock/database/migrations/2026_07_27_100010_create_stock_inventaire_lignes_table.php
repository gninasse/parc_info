<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_inventaire_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaire_id')->constrained('stock_inventaires')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles')->restrictOnDelete();
            // RG-F6-02 : quantité théorique figée à l'ouverture de l'inventaire.
            $table->unsignedInteger('quantite_theorique');
            $table->unsignedInteger('quantite_reelle')->nullable();
            $table->integer('ecart')->nullable();
            $table->decimal('cout_unitaire_reference', 12, 4)->nullable();
            $table->foreignId('mouvement_id')->nullable()->constrained('stock_mouvements')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inventaire_id', 'article_id'], 'unique_article_par_inventaire');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_inventaire_lignes');
    }
};
