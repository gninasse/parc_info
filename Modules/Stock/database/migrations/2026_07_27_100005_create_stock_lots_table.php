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
            $table->foreignId('article_id')->constrained('achat_articles')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->unsignedInteger('quantite_initiale');
            $table->unsignedInteger('quantite_restante');
            $table->decimal('cout_unitaire', 12, 4);
            $table->date('date_entree');
            $table->foreignId('mouvement_id')->nullable()->constrained('stock_mouvements')->nullOnDelete();
            $table->timestamps();

            // RG-F2-06 : consommation FIFO dans l'ordre date_entree ASC.
            $table->index(['article_id', 'magasin_id', 'date_entree']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            // RG-F2-04 : la quantité restante ne dépasse jamais la quantité initiale.
            DB::statement('ALTER TABLE stock_lots ADD CONSTRAINT chk_lot_restante_bornee CHECK (quantite_restante <= quantite_initiale)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
