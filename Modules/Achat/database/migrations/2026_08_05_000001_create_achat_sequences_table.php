<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Séquences de numérotation (SFD §6.1) : un compteur par préfixe et par
     * année, incrémenté SOUS VERROU à la validation uniquement. Les brouillons
     * n'en consomment pas — aucun trou à la suppression d'un brouillon (IA-3).
     */
    public function up(): void
    {
        Schema::create('achat_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefixe');
            $table->unsignedSmallInteger('annee');
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['prefixe', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_sequences');
    }
};
