<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lignes_transferts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfert_id')->constrained('stock_transferts')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('catalogue_articles')->restrictOnDelete();
            $table->decimal('quantite', 12, 2);
            $table->timestamps();
        });

        SchemaChecks::ajouter('stock_lignes_transferts', [
            'chk_lignes_transferts_quantite_positive' => 'quantite > 0',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lignes_transferts');
    }
};
