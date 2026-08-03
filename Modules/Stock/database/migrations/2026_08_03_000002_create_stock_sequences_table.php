<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Support de la numérotation S4 (attribuée à la validation, sous verrou,
     * par table et par année) — pattern catalogue_sequences.
     */
    public function up(): void
    {
        Schema::create('stock_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefixe'); // ENT / SOR / TRF / INV
            $table->unsignedSmallInteger('annee');
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['prefixe', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sequences');
    }
};
