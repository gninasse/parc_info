<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * Journal des intégrations de réception (API_Inter_Modules §5.1).
     *
     * L'idempotence exige de savoir ce qui a DÉJÀ été intégré : sans cette
     * trace, un rejeu de la notification incrémenterait deux fois les
     * quantités livrées (IA-5). La clé naturelle est l'`entree_id` du bon
     * d'entrée Stock — unique, donc un même bon ne peut être intégré qu'une
     * fois, la base le garantissant autant que l'applicatif.
     *
     * La contre-passation est tracée de la même façon, par `mouvement_id` :
     * les deux sens du flux sont rejouables sans dommage.
     *
     * `entree_id` n'est PAS une FK : le module Achat ne doit pas dépendre du
     * schéma de Stock (c'est Achat qui requiert Stock, l'inverse créerait un
     * cycle). L'intégrité est portée par le service, qui reçoit l'entrée.
     */
    public function up(): void
    {
        Schema::create('achat_integrations_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_commande_id')->constrained('achat_bons_commande')->restrictOnDelete();

            $table->enum('sens', ['RECEPTION', 'CONTRE_PASSATION']);

            // Clé naturelle d'idempotence : entree_id pour une réception,
            // mouvement_id pour une contre-passation (exclusifs).
            $table->unsignedBigInteger('entree_id')->nullable();
            $table->unsignedBigInteger('mouvement_id')->nullable();

            $table->string('reference')->nullable(); // n° ENT-… au moment de l'intégration
            $table->json('detail'); // lignes et quantités intégrées (trace d'audit)

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sens', 'entree_id']);
            $table->unique(['sens', 'mouvement_id']);
            $table->index('bon_commande_id');
        });

        SchemaChecks::ajouter('achat_integrations_receptions', [
            // Exactement une clé naturelle selon le sens : une réception porte
            // une entrée, une contre-passation porte un mouvement.
            'chk_integrations_cle_selon_sens' => "(sens = 'RECEPTION' AND entree_id IS NOT NULL AND mouvement_id IS NULL)"
                ." OR (sens = 'CONTRE_PASSATION' AND mouvement_id IS NOT NULL AND entree_id IS NULL)",
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_integrations_receptions');
    }
};
