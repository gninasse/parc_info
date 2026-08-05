<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SFD §6.2 — rattachement d'un équipement ParcInfo à son bon de commande
     * de régularisation (A15/M-09). `equipement_id` est UNIQUE : un
     * équipement n'a qu'une commande d'origine. La FK équipement est
     * `cascade` (le lien meurt avec la fiche, la chronologie garde la trace) ;
     * la FK bon est `restrict` (le BC de régularisation ne peut pas
     * s'évaporer sous ses rattachements).
     */
    public function up(): void
    {
        Schema::create('achat_regularisation_rattachements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_commande_id')->constrained('achat_bons_commande')->restrictOnDelete();
            $table->foreignId('equipement_id')->unique()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('bon_commande_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_regularisation_rattachements');
    }
};
