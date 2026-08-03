<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 : théorique figé à l'ouverture, physique saisi au comptage,
     * pointage unitaire PRESENT/ABSENT/TROUVE. mouvement_id (ajustement
     * généré) est une FK vers stock_mouvements, table créée après celle-ci
     * (ordre du SFD) : la contrainte est ajoutée par la migration
     * stock_mouvements sur PostgreSQL ; sur SQLite l'intégrité est
     * applicative (ADD CONSTRAINT impossible a posteriori).
     */
    public function up(): void
    {
        Schema::create('stock_lignes_inventaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaire_id')->constrained('stock_inventaires')->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained('catalogue_articles')->restrictOnDelete();
            $table->foreignId('equipement_id')->nullable()->constrained('parc_info_equipements')->restrictOnDelete();
            $table->decimal('quantite_theorique', 12, 2)->default(0);
            $table->decimal('quantite_physique', 12, 2)->nullable();
            $table->enum('pointage', ['PRESENT', 'ABSENT', 'TROUVE'])->nullable();
            $table->unsignedBigInteger('mouvement_id')->nullable()->index();
            $table->timestamps();
        });

        SchemaChecks::ajouter('stock_lignes_inventaire', [
            'chk_lignes_inventaire_article_xor_equipement' => '(article_id IS NOT NULL AND equipement_id IS NULL) OR (article_id IS NULL AND equipement_id IS NOT NULL)',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lignes_inventaire');
    }
};
