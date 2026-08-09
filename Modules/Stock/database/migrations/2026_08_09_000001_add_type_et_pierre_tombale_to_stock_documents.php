<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BR-01 — le BORDEREAU DU FOURNISSEUR sur le bon d'entrée.
     *
     * Les pièces jointes existaient déjà (stock_documents), mais toutes
     * égales : rien ne distinguait le BL papier du livreur d'une photo de
     * colis. Or le BL est LA pièce entrante de la réception — celle qu'on
     * cherche six mois plus tard quand le fournisseur conteste.
     *
     * Deux ajouts :
     *
     *   - `type` : bl_fournisseur | photo_livraison | autre. Défaut « autre »,
     *     pour que les pièces déjà déposées restent valides ;
     *   - la PIERRE TOMBALE (même doctrine qu'Achat A16) : après validation
     *     du bon, supprimer une pièce efface le fichier mais CONSERVE la
     *     ligne, motivée et signée. Une pièce gênante ne disparaît pas
     *     silencieusement d'un document engagé.
     */
    public function up(): void
    {
        if (! Schema::hasTable('stock_documents')) {
            return;
        }

        /*
         * ⚠ Piège documenté (Modules/Achat/README.md) : sur SQLite, ajouter
         * une colonne AVEC contrainte fait RECRÉER la table par Laravel, ce
         * qui perd silencieusement les CHECK existants. On ajoute donc la FK
         * uniquement sur PostgreSQL ; l'intégrité est de toute façon assurée
         * par l'application, et les suites tournent sur les deux pilotes.
         */
        $surSqlite = DB::getDriverName() === 'sqlite';

        Schema::table('stock_documents', function (Blueprint $table) use ($surSqlite) {
            $table->string('type')->default('autre')->after('documentable_id');

            // Pierre tombale
            $table->boolean('est_supprime')->default(false);
            $table->dateTime('supprime_le')->nullable();
            $table->text('motif_suppression')->nullable();

            if ($surSqlite) {
                $table->unsignedBigInteger('supprime_par')->nullable();
            } else {
                $table->foreignId('supprime_par')->nullable()
                    ->constrained('users')->nullOnDelete();
            }

            $table->index(['documentable_type', 'documentable_id', 'type'], 'idx_documents_type');
        });

        // `chemin` devient nullable : une pierre tombale n'a plus de fichier.
        if (! $surSqlite) {
            DB::statement('ALTER TABLE stock_documents ALTER COLUMN chemin DROP NOT NULL');
            DB::statement('ALTER TABLE stock_documents ALTER COLUMN mime DROP NOT NULL');
            DB::statement('ALTER TABLE stock_documents ALTER COLUMN taille DROP NOT NULL');

            DB::statement('
                ALTER TABLE stock_documents ADD CONSTRAINT chk_documents_pierre_tombale
                CHECK (
                    (est_supprime = false AND motif_suppression IS NULL AND supprime_le IS NULL)
                    OR (est_supprime = true AND motif_suppression IS NOT NULL AND supprime_le IS NOT NULL)
                )
            ');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stock_documents', 'type')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_documents DROP CONSTRAINT IF EXISTS chk_documents_pierre_tombale');
        }

        Schema::table('stock_documents', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropConstrainedForeignId('supprime_par');
            } else {
                $table->dropColumn('supprime_par');
            }

            $table->dropIndex('idx_documents_type');
            $table->dropColumn(['type', 'est_supprime', 'supprime_le', 'motif_suppression']);
        });
    }
};
