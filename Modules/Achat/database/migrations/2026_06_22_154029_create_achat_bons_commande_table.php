<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('achat_bons_commande', function (Blueprint $table) {
            $table->id();
            $table->string('numero_commande', 50)->unique();
            $table->foreignId('fournisseur_id')->constrained('parc_info_fournisseurs');
            $table->date('date_commande');
            $table->enum('statut', ['brouillon', 'valide', 'partiel', 'livre', 'annule'])
                ->default('brouillon');
            $table->decimal('montant_total', 14, 2)->default(0);
            $table->text('commentaire')->nullable();

            // Validation audit
            $table->foreignId('valide_par')->nullable()->constrained('users');
            $table->timestamp('date_validation')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('numero_commande');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achat_bons_commande');
    }
};
