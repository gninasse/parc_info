<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * P0-A — la 5e nature d'article : `prestation` (PRQ-02, décision A5 du
     * CDC Achat, C9/C10 du SFD Catalogue).
     *
     * Une prestation est un SERVICE commandé (maintenance, installation,
     * formation…) : elle se commande dans Achat, se solde par un constat de
     * service fait (M-04), et ne touche JAMAIS le Stock — même mécanisme
     * d'immatérialité que la licence (est_stockable = false, dérivé par le
     * modèle).
     *
     * Sur PostgreSQL, l'« enum » Laravel est un varchar + CHECK : on recrée
     * la contrainte avec la nouvelle valeur. Sur SQLite (suites de tests),
     * les CHECK d'une table ne se modifient pas sans la recréer — et le
     * piège documenté du projet (Modules/Achat/README.md) interdit de
     * laisser Laravel recréer une table : la garde est déjà DOUBLÉE par la
     * validation applicative du modèle (validerCoherenceNature), testée sur
     * les deux drivers. On ne touche donc pas la DDL SQLite.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE catalogue_articles DROP CONSTRAINT IF EXISTS catalogue_articles_nature_check');
        DB::statement("
            ALTER TABLE catalogue_articles ADD CONSTRAINT catalogue_articles_nature_check
            CHECK (nature::text = ANY (ARRAY['consommable'::character varying, 'piece'::character varying, 'equipement'::character varying, 'licence'::character varying, 'prestation'::character varying]::text[]))
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Irréversible si des prestations existent : on refuse plutôt que de
        // casser la contrainte sous des lignes qui la violeraient.
        $prestations = DB::table('catalogue_articles')->where('nature', 'prestation')->count();

        if ($prestations > 0) {
            throw new RuntimeException(
                "{$prestations} article(s) de nature « prestation » existent : supprimez-les avant de revenir en arrière."
            );
        }

        DB::statement('ALTER TABLE catalogue_articles DROP CONSTRAINT IF EXISTS catalogue_articles_nature_check');
        DB::statement("
            ALTER TABLE catalogue_articles ADD CONSTRAINT catalogue_articles_nature_check
            CHECK (nature::text = ANY (ARRAY['consommable'::character varying, 'piece'::character varying, 'equipement'::character varying, 'licence'::character varying]::text[]))
        ");
    }
};
