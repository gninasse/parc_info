<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transferts', function (Blueprint $table) {
            $table->id();
            $table->string('numero_transfert')->unique();
            $table->foreignId('magasin_source_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->foreignId('magasin_destination_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles')->cascadeOnDelete();
            $table->integer('quantite');
            $table->string('statut')->default('EN_ATTENTE'); // EN_ATTENTE, VALIDE, REJETE, ANNULE
            $table->text('motif_creation');
            $table->text('motif_rejet')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->foreignId('mouvement_sortant_id')->nullable()->constrained('stock_mouvements')->nullOnDelete();
            $table->foreignId('mouvement_entrant_id')->nullable()->constrained('stock_mouvements')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_transferts ADD CONSTRAINT check_magasins_differents CHECK (magasin_source_id != magasin_destination_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transferts');
    }
};
