<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_articles_magasin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('achat_articles')->cascadeOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->integer('quantite_actuelle')->default(0);
            $table->decimal('valeur_stock_fifo', 14, 2)->default(0.00);
            $table->timestamp('derniere_entree_at')->nullable();
            $table->timestamp('derniere_sortie_at')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'magasin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_articles_magasin');
    }
};
