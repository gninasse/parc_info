<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saisies temporaires de l'assistant d'intégration (RG-WZ-07).
 * Purgées après une validation réussie (RG-WZ-08).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_wizard_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bordereau_livraison_id')
                ->constrained('achat_bordereaux_livraison')
                ->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles');

            // Une entrée par unité livrée :
            //   équipement → { numero_serie, code_inventaire, champs_valeurs{} }
            //   licence    → { cle_licence, date_activation, date_expiration }
            $table->json('unites_data');

            // EF-INT-18 : valeurs appliquées à toutes les unités de l'article
            $table->json('attributs_communs')->nullable();

            $table->boolean('completed')->default(false);

            $table->timestamps();

            $table->unique(['bordereau_livraison_id', 'article_id'], 'unique_wizard_article');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_wizard_data');
    }
};
