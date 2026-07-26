<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compteurs de numérotation des documents du module.
 *
 * ENF-FIA-02 — Support du verrou pessimiste utilisé par le trait
 * GeneratesDocumentNumbers pour garantir l'unicité sous accès concurrents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);          // bon_commande | bordereau_livraison | code_inventaire
            $table->unsignedSmallInteger('annee');
            $table->unsignedInteger('compteur')->default(0);
            $table->timestamps();

            $table->unique(['type', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_sequences');
    }
};
