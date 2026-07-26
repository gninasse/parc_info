<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_bons_commande', function (Blueprint $table) {
            $table->id();
            $table->string('numero_commande', 50)->unique();
            $table->foreignId('fournisseur_id')->constrained('parc_info_fournisseurs');
            $table->date('date_commande');
            $table->enum('statut', ['brouillon', 'valide', 'partiel', 'livre', 'annule', 'cloture'])
                ->default('brouillon');

            // ENF-FIA-04 : les trois montants sont persistés et recalculés par le
            // service. Aucun écran ne recalcule un montant pour son propre compte.
            $table->decimal('montant_ht', 14, 2)->default(0);
            $table->decimal('montant_tva', 14, 2)->default(0);
            $table->decimal('montant_ttc', 14, 2)->default(0);

            $table->text('commentaire')->nullable();

            // Validation (RG-BC-06)
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();

            // Annulation (RG-BC-05) — le motif est exigé
            $table->foreignId('annule_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_annulation')->nullable();
            $table->text('motif_annulation')->nullable();

            // Clôture de reliquat (EF-BC-18)
            $table->foreignId('cloture_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_cloture')->nullable();
            $table->text('motif_cloture')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_commande');
            $table->index('statut');
            $table->index('date_commande');
            $table->index(['statut', 'date_commande']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_bons_commande');
    }
};
