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
            // RG-F6-10 : INV-YYYY-XXXX via stock_sequences.
            $table->string('numero_inventaire', 20)->unique();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->date('date_inventaire');
            $table->enum('statut', ['EN_COURS', 'CLOTURE', 'ANNULE'])->default('EN_COURS');
            $table->unsignedInteger('nombre_articles')->default(0);
            $table->unsignedInteger('nombre_ecarts')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_cloture')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // RG-F6-01 : un seul inventaire EN_COURS par magasin (contrôle service).
            $table->index(['magasin_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_inventaires');
    }
};
