<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 — journal en insertion seule (S1), source de vérité.
     * Rattachement par 4 FK exclusives (exactement une), OU aucune si
     * mouvement_origine_id est renseigné (contre-mouvement autoporté).
     * article_id XOR equipement_id ; quantite > 0 (= 1 si unité) ;
     * motif requis pour les ajustements (inventaire ou contre-mouvement).
     * Pas d'updated_at : une ligne ne se modifie jamais.
     */
    public function up(): void
    {
        Schema::create('stock_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entree_id')->nullable()->constrained('stock_entrees')->restrictOnDelete();
            $table->foreignId('sortie_id')->nullable()->constrained('stock_sorties')->restrictOnDelete();
            $table->foreignId('transfert_id')->nullable()->constrained('stock_transferts')->restrictOnDelete();
            $table->foreignId('inventaire_id')->nullable()->constrained('stock_inventaires')->restrictOnDelete();
            $table->foreignId('mouvement_origine_id')->nullable()->constrained('stock_mouvements')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->restrictOnDelete();
            $table->enum('type', ['ENTREE', 'SORTIE', 'TRANSFERT_ENTREE', 'TRANSFERT_SORTIE', 'AJUSTEMENT']);
            $table->smallInteger('sens');
            $table->foreignId('article_id')->nullable()->constrained('catalogue_articles')->restrictOnDelete();
            $table->foreignId('equipement_id')->nullable()->constrained('parc_info_equipements')->restrictOnDelete();
            $table->decimal('quantite', 12, 2);
            $table->decimal('cout_unitaire', 14, 2)->nullable();
            $table->foreignId('affectation_equipement_id')->nullable()->constrained('parc_info_affectation_equipements')->nullOnDelete();
            $table->text('motif')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at'); // pas d'updated_at : journal immuable

            $table->index(['magasin_id', 'created_at']);
            $table->index('article_id');
            $table->index('equipement_id');
            $table->index('entree_id');
            $table->index('sortie_id');
            $table->index('transfert_id');
            $table->index('inventaire_id');
        });

        SchemaChecks::ajouter('stock_mouvements', [
            'chk_mouvements_document_exclusif' => '('
                .'(CASE WHEN entree_id IS NULL THEN 0 ELSE 1 END)'
                .' + (CASE WHEN sortie_id IS NULL THEN 0 ELSE 1 END)'
                .' + (CASE WHEN transfert_id IS NULL THEN 0 ELSE 1 END)'
                .' + (CASE WHEN inventaire_id IS NULL THEN 0 ELSE 1 END) = 1'
                .' AND mouvement_origine_id IS NULL'
                .') OR ('
                .'entree_id IS NULL AND sortie_id IS NULL AND transfert_id IS NULL AND inventaire_id IS NULL'
                .' AND mouvement_origine_id IS NOT NULL'
                .')',
            'chk_mouvements_article_xor_equipement' => '(article_id IS NOT NULL AND equipement_id IS NULL) OR (article_id IS NULL AND equipement_id IS NOT NULL)',
            'chk_mouvements_quantite_positive' => 'quantite > 0',
            'chk_mouvements_quantite_unitaire' => 'equipement_id IS NULL OR quantite = 1',
            'chk_mouvements_sens' => 'sens IN (-1, 1)',
            'chk_mouvements_motif_ajustement' => "type <> 'AJUSTEMENT' OR motif IS NOT NULL",
        ]);

        // FK différée de stock_lignes_inventaire.mouvement_id (la table des
        // lignes précède celle des mouvements dans l'ordre du SFD).
        // PostgreSQL uniquement : SQLite ne sait pas l'ajouter a posteriori,
        // l'intégrité y est applicative.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                ALTER TABLE stock_lignes_inventaire
                ADD CONSTRAINT fk_lignes_inventaire_mouvement
                FOREIGN KEY (mouvement_id) REFERENCES stock_mouvements (id) ON DELETE SET NULL
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql' && Schema::hasTable('stock_lignes_inventaire')) {
            DB::statement('ALTER TABLE stock_lignes_inventaire DROP CONSTRAINT IF EXISTS fk_lignes_inventaire_mouvement');
        }

        Schema::dropIfExists('stock_mouvements');
    }
};
