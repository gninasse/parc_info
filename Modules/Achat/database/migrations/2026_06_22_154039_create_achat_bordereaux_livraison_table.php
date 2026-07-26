<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_bordereaux_livraison', function (Blueprint $table) {
            $table->id();
            $table->string('numero_livraison', 50)->unique();

            // RGC-09 / EF-BL-14 : le rattachement est définitif après création
            $table->foreignId('bon_de_commande_id')->constrained('achat_bons_commande');

            $table->date('date_livraison');
            $table->string('ref_bordereau_physique', 100)->unique();
            $table->enum('statut', ['brouillon', 'wizard', 'valide'])->default('brouillon');
            $table->text('commentaire')->nullable();

            // Validation / intégration (RGC-03)
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_livraison');
            $table->index('ref_bordereau_physique');
            $table->index('statut');
            $table->index('bon_de_commande_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_bordereaux_livraison');
    }
};
