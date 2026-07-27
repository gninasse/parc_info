<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_snapshot_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('stock_snapshots')->cascadeOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles')->restrictOnDelete();
            $table->unsignedInteger('quantite')->default(0);
            $table->decimal('valeur_fifo', 14, 2)->default(0);
            $table->decimal('cout_unitaire_moyen', 12, 4)->default(0);
            $table->timestamps();

            $table->unique(['snapshot_id', 'magasin_id', 'article_id'], 'unique_ligne_par_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_snapshot_lignes');
    }
};
