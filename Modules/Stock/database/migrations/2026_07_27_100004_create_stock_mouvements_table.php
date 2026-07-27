<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table unique pour F3 + F4 + F5 + F6 (décision d'architecture).
        Schema::create('stock_mouvements', function (Blueprint $table) {
            $table->id();
            $table->string('numero_mouvement', 20)->unique();
            $table->enum('type_mouvement', [
                'ENTREE', 'SORTIE',
                'TRANSFERT_ENTRANT', 'TRANSFERT_SORTANT',
                'REGULARISATION_PLUS', 'REGULARISATION_MOINS',
                'INVENTAIRE_PLUS', 'INVENTAIRE_MOINS',
            ]);
            $table->foreignId('article_id')->constrained('achat_articles')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->decimal('cout_unitaire', 12, 4)->nullable();
            $table->enum('type_origine', ['MANUEL', 'BL', 'TRANSFERT', 'INVENTAIRE'])->default('MANUEL');
            $table->unsignedBigInteger('origine_id')->nullable();
            $table->text('reference_document')->nullable();
            $table->text('motif')->nullable();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_id', 'magasin_id']);
            $table->index('type_mouvement');
            $table->index(['type_origine', 'origine_id']);
            $table->index('created_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            // RG-F3-07 : quantité strictement positive.
            DB::statement('ALTER TABLE stock_mouvements ADD CONSTRAINT chk_mouvement_quantite_positive CHECK (quantite > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_mouvements');
    }
};
