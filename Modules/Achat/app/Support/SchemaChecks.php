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
 * Les expressions doivent rester portables : aucune fonction propre à un SGBD
 * (SFD §1.7) — utiliser CASE WHEN.
 */
final class SchemaChecks
{
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
