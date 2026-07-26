<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_inventaires', function (Blueprint $table) {
            $table->id();
            $table->string('numero_inventaire')->unique();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->date('date_inventaire');
            $table->string('statut')->default('EN_COURS'); // EN_COURS, CLOTURE, ANNULE
            $table->integer('nombre_articles')->default(0);
            $table->integer('nombre_ecarts')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_cloture')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['magasin_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_inventaires');
    }
};
