<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compteurs de génération des codes (CAT-, FOUR-xx, EQP-, CONS-, PIE-, LIC-).
     * Une ligne par préfixe, incrémentée sous lockForUpdate : pas de collision
     * possible en concurrence, contrairement à un MAX() sur la table cible.
     */
    public function up(): void
    {
        Schema::create('catalogue_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20)->unique();
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_sequences');
    }
};
