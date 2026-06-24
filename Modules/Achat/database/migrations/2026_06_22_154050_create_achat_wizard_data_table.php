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
        Schema::create('achat_wizard_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bordereau_livraison_id')
                ->constrained('achat_bordereaux_livraison')
                ->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles');
            $table->json('unites_data'); // Tableau des unités saisies (RAM, stockage, n_serie, code_inv, etc.)
            $table->json('attributs_communs')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achat_wizard_data');
    }
};
