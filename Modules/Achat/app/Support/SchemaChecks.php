<?php

namespace Modules\Achat\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute des contraintes CHECK nommées à une table qui vient d'être créée,
 * actives sur PostgreSQL ET SQLite (double filet : les gardes applicatives
 * sont doublées par la base — convention du module Stock).
 *
 * PostgreSQL accepte ALTER TABLE … ADD CONSTRAINT … CHECK. SQLite ne sait pas
 * ajouter un CHECK a posteriori : la table (encore vide, on est dans sa propre
 * migration) est recréée avec les CHECK intégrés au CREATE TABLE, puis ses
 * index sont rejoués à l'identique.
 *
 * ⚠ PIÈGE SQLITE À CONNAÎTRE POUR TOUTE MIGRATION ULTÉRIEURE
 * SQLite ne sait pas non plus ajouter une clé étrangère à une table
 * existante : Laravel la RECRÉE alors intégralement, et les CHECK posés ici
 * disparaissent SANS ERREUR. La base reste fonctionnelle, mais son filet de
 * sécurité s'est évaporé en silence.
 *
 * La parade retenue (migration 000010) : sur SQLite, ajouter les colonnes
 * SANS contrainte de clé étrangère, ce qui autorise un vrai
 * `ALTER TABLE ADD COLUMN` laissant la table intacte. La contrainte reste
 * posée sur PostgreSQL, où les suites tournent également.
 *
 * `SchemaInvariantsTest` monte la garde : si un CHECK disparaît, il échoue.
 *
 * Les expressions doivent rester portables : aucune fonction propre à un SGBD
 * (SFD §1.7) — utiliser CASE WHEN.
 */
final class SchemaChecks
{
    /**
     * CHECK de `achat_bons_commande`, définis UNE SEULE FOIS pour que la
     * migration de création et les tests d'invariants ne puissent pas
     * décrire deux règles différentes.
     *
     * @return array<string, string>
     */
    public static function bonsCommande(): array
    {
        return [
            // Un numéro ne s'attribue qu'à la validation : un brouillon ou un
            // bon soumis n'en porte JAMAIS, un bon engagé en porte TOUJOURS.
            // ANNULE est volontairement laissé libre (écart n°2 du README).
            'chk_bc_numero_si_engage' => "(numero IS NULL AND statut IN ('BROUILLON', 'SOUMIS'))"
                ." OR (numero IS NOT NULL AND statut IN ('VALIDE', 'PARTIEL', 'LIVRE', 'CLOTURE'))"
                ." OR statut = 'ANNULE'",
            'chk_bc_montants_positifs' => 'montant_ht >= 0 AND montant_tva >= 0 AND montant_ttc >= 0',
        ];
    }

    /**
     * @param  array<string, string>  $checks  nom de contrainte => expression SQL portable
     */
    public static function ajouter(string $table, array $checks): void
    {
        if ($checks === []) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => self::ajouterPostgres($table, $checks),
            'sqlite' => self::recreerSqliteAvecChecks($table, $checks),
            default => throw new \RuntimeException(
                'SGBD non supporté pour les CHECK du module Achat : '.DB::getDriverName()
            ),
        };
    }

    private static function ajouterPostgres(string $table, array $checks): void
    {
        foreach ($checks as $nom => $expression) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$nom} CHECK ({$expression})");
        }
    }

    private static function recreerSqliteAvecChecks(string $table, array $checks): void
    {
        $ddl = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
            [$table]
        )->sql;

        $clauses = [];
        foreach ($checks as $nom => $expression) {
            $clauses[] = "CONSTRAINT {$nom} CHECK ({$expression})";
        }

        $ddlAvecChecks = preg_replace('/\)\s*$/', ', '.implode(', ', $clauses).')', $ddl);

        // Les index (unique compris) sont des entrées séparées de
        // sqlite_master : ils disparaissent au DROP, on les rejoue.
        $index = DB::select(
            "SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND sql IS NOT NULL",
            [$table]
        );

        Schema::drop($table);
        DB::statement($ddlAvecChecks);

        foreach ($index as $entree) {
            DB::statement($entree->sql);
        }
    }
}
