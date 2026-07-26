<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_mouvements', function (Blueprint $table) {
            $table->id();
            $table->string('type_mouvement'); // ENTREE, SORTIE, etc. We will use string to avoid enum issues
            $table->foreignId('article_id')->constrained('achat_articles')->cascadeOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->integer('quantite');
            $table->decimal('cout_unitaire', 12, 4)->nullable();
            $table->string('type_origine')->default('MANUEL'); // MANUEL, BL, etc.
            $table->unsignedBigInteger('origine_id')->nullable();
            $table->string('reference_document')->nullable();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_mouvements');
    }
};
