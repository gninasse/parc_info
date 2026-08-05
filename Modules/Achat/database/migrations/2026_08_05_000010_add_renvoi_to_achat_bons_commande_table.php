<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traçabilité du renvoi en brouillon (SFD §7.1, UX2-07).
     *
     * Le motif est déjà journalisé, mais l'encart jaune de l'étape ① doit
     * pouvoir l'afficher SANS relire l'historique : il faudrait sinon
     * interroger le journal à chaque réouverture, le filtrer, et espérer que
     * la rétention ne l'ait pas purgé. Ces trois colonnes sont la forme
     * courante de l'information ; le journal en reste la trace intégrale.
     *
     * Elles sont remises à zéro à chaque nouvelle soumission : l'encart ne
     * décrit que le DERNIER renvoi, il ne ressurgit pas après correction.
     */
    public function up(): void
    {
        /*
         * ⚠ SQLite ne sait pas ajouter une clé étrangère à une table
         * existante : Laravel la RECRÉE alors intégralement, ce qui perd
         * silencieusement les CHECK posés à la création (chk_bc_numero_si_engage,
         * chk_bc_montants_positifs). La base reste fonctionnelle, mais son
         * filet de sécurité a disparu sans le moindre message — découvert
         * parce que SchemaInvariantsTest a cessé de passer.
         *
         * On ajoute donc les colonnes SANS contrainte sur SQLite, ce qui
         * autorise un vrai `ALTER TABLE ADD COLUMN` : la table n'est pas
         * touchée, ses CHECK survivent. L'intégrité référentielle y est de
         * toute façon assurée par l'application, et les suites tournent
         * également sur PostgreSQL, où la vraie contrainte est posée.
         */
        $surSqlite = DB::getDriverName() === 'sqlite';

        Schema::table('achat_bons_commande', function (Blueprint $table) use ($surSqlite) {
            $table->text('renvoi_motif')->nullable();

            if ($surSqlite) {
                $table->unsignedBigInteger('renvoi_par')->nullable();
            } else {
                $table->foreignId('renvoi_par')->nullable()
                    ->constrained('users')->nullOnDelete();
            }

            $table->dateTime('renvoi_le')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('achat_bons_commande', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                // La contrainte doit tomber avant sa colonne (PostgreSQL).
                $table->dropConstrainedForeignId('renvoi_par');
                $table->dropColumn(['renvoi_motif', 'renvoi_le']);

                return;
            }

            $table->dropColumn(['renvoi_motif', 'renvoi_par', 'renvoi_le']);
        });
    }
};
