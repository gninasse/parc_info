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
            // RG-F5-07 : TRF-YYYY-XXXX via stock_sequences.
            $table->string('numero_transfert', 20)->unique();
            $table->foreignId('magasin_source_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->foreignId('magasin_destination_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->enum('statut', ['EN_ATTENTE', 'VALIDE', 'REJETE', 'ANNULE'])->default('EN_ATTENTE');
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
            // RG-F5-01 : un transfert relie deux magasins distincts.
            DB::statement('ALTER TABLE stock_transferts ADD CONSTRAINT chk_transfert_magasins_distincts CHECK (magasin_source_id <> magasin_destination_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transferts');
    }
};
