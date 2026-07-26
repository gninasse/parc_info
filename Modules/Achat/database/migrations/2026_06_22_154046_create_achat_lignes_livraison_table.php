<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_lignes_livraison', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bordereau_livraison_id')
                ->constrained('achat_bordereaux_livraison')
                ->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles');

            $table->unsignedInteger('quantite_livree');

            // EF-BL-18 : traçabilité des unités refusées à la réception
            $table->unsignedInteger('quantite_refusee')->default(0);
            $table->text('motif_refus')->nullable();

            $table->timestamps();

            $table->index('bordereau_livraison_id');
            $table->unique(['bordereau_livraison_id', 'article_id'], 'unique_article_par_bl');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE achat_lignes_livraison ADD CONSTRAINT chk_ll_quantite_positive CHECK (quantite_livree > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_lignes_livraison');
    }
};
