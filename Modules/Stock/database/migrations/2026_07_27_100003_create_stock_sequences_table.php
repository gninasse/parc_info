<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compteurs annualisés verrouillés (lockForUpdate) par
        // GeneratesDocumentNumbers : BE, BS, TRF, INV, SNAP…
        Schema::create('stock_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->unsignedSmallInteger('annee');
            $table->unsignedInteger('compteur')->default(0);
            $table->timestamps();

            $table->unique(['type', 'annee'], 'unique_sequence_type_annee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sequences');
    }
};
