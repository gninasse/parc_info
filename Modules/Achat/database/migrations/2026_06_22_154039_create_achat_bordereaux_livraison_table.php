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
        Schema::create('achat_bordereaux_livraison', function (Blueprint $table) {
            $table->id();
            $table->string('numero_livraison', 50)->unique();
            $table->foreignId('bon_de_commande_id')->constrained('achat_bons_commande');
            $table->date('date_livraison');
            $table->string('ref_bordereau_physique', 100)->unique();
            $table->enum('statut', ['brouillon', 'wizard', 'valide'])->default('brouillon');
            $table->text('commentaire')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('numero_livraison');
            $table->index('ref_bordereau_physique');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achat_bordereaux_livraison');
    }
};
