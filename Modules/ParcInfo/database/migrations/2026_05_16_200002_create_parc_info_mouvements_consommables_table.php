<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_mouvements_consommables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consommable_id')->constrained('parc_info_consommables')->onDelete('cascade');
            $table->enum('type_mouvement', [
                'Achat',
                'Consommation',
                'Retour',
                'Ajustement',
                'Maintenance',
            ]);
            $table->unsignedInteger('quantite');
            $table->decimal('prix_unitaire', 10, 2)->nullable();
            $table->dateTime('date_mouvement');
            $table->string('reference_commande')->nullable();

            $table->foreignId('utilisateur_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('equipement_id')->nullable()->constrained('parc_info_equipements')->onDelete('set null');
            $table->foreignId('employe_id')->nullable()->constrained('grh_dossiers_employes')->onDelete('set null');

            $table->string('raison')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['consommable_id', 'date_mouvement']);
            $table->index(['type_mouvement']);
            $table->index(['utilisateur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_mouvements_consommables');
    }
};
