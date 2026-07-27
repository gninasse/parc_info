<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Projection par (article, magasin) tenue à jour dans la même
        // transaction que chaque mouvement ; les lots FIFO font foi (RG-F2-05).
        Schema::create('stock_articles_magasin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('achat_articles')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->unsignedInteger('quantite_actuelle')->default(0);
            $table->decimal('valeur_stock_fifo', 14, 2)->default(0);
            $table->timestamp('derniere_entree_at')->nullable();
            $table->timestamp('derniere_sortie_at')->nullable();
            $table->timestamps();

            // RG-F2-01 : un article n'est initialisé qu'une fois par magasin.
            $table->unique(['article_id', 'magasin_id'], 'unique_article_par_magasin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_articles_magasin');
    }
};
