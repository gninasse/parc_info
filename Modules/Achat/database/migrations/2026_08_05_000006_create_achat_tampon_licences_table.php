<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SFD §6.2 — tampon du wizard de licences : la saisie est sauvegardée clé
     * par clé pour survivre à une coupure réseau (IA-8). PURGÉ à la
     * finalisation (les données vivent alors dans ParcInfo) et vidé à
     * l'abandon : ce n'est jamais une seconde source de vérité.
     */
    public function up(): void
    {
        Schema::create('achat_tampon_licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('achat_receptions_licences')->cascadeOnDelete();
            $table->string('cle');
            $table->date('date_activation')->nullable();
            $table->date('date_expiration')->nullable();
            $table->timestamps();

            // Unicité par session ; l'unicité globale contre parc_info_licences
            // est applicative (saisie ET finalisation).
            $table->unique(['reception_id', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_tampon_licences');
    }
};
