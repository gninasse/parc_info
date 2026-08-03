<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 : toujours un article_id (quantitatif ou « modèle × N » —
     * les unités pointées vivent dans le tampon) ; emplacement optionnel
     * pour l'affectation D8.
     */
    public function up(): void
    {
        Schema::create('stock_lignes_sorties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sortie_id')->constrained('stock_sorties')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('catalogue_articles')->restrictOnDelete();
            $table->decimal('quantite', 12, 2);
            $table->foreignId('emplacement_local_id')->nullable()->constrained('organisation_locaux')->nullOnDelete();
            $table->timestamps();
        });

        SchemaChecks::ajouter('stock_lignes_sorties', [
            'chk_lignes_sorties_quantite_positive' => 'quantite > 0',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lignes_sorties');
    }
};
