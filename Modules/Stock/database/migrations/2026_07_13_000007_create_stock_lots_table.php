<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('achat_articles')->cascadeOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->integer('quantite_initiale');
            $table->integer('quantite_restante');
            $table->decimal('cout_unitaire', 12, 4);
            $table->date('date_entree');
            $table->foreignId('mouvement_id')->nullable()->constrained('stock_mouvements')->nullOnDelete();
            $table->timestamps();

            $table->index(['article_id', 'magasin_id', 'date_entree']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_lots ADD CONSTRAINT check_quantite_restante_non_negative CHECK (quantite_restante >= 0)');
            DB::statement('ALTER TABLE stock_lots ADD CONSTRAINT check_quantite_restante_le_initiale CHECK (quantite_restante <= quantite_initiale)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
