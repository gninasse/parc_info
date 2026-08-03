<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SFD §6.2 : périmètre « tout le magasin » ou « sélection d'articles »
     * (UX MD-INV-OUVERTURE) ; motif global saisi à la validation.
     */
    public function up(): void
    {
        Schema::create('stock_inventaires', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable()->unique();
            $table->date('date_document');
            $table->enum('statut', ['EN_COURS', 'VALIDE', 'ANNULE'])->default('EN_COURS');
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->enum('perimetre', ['magasin', 'selection']);
            $table->text('motif_global')->nullable();
            $table->text('observation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('valide_le')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_inventaires');
    }
};
