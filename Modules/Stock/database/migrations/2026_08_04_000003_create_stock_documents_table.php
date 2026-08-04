<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pièces jointes des bons (demande Ibrahim 04/08/2026) : bon de
     * livraison scanné, photo du colis, courrier de réclamation…
     * Relation polymorphe vers stock_entrees / stock_sorties /
     * stock_transferts. Les fichiers vivent sur le disque privé, servis par
     * une route contrôlée — jamais d'accès direct par URL.
     */
    public function up(): void
    {
        Schema::create('stock_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // documentable_type + documentable_id
            $table->string('nom_original');
            $table->string('chemin');
            $table->string('mime');
            $table->unsignedBigInteger('taille');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_documents');
    }
};
