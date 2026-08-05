#!/usr/bin/env bash
# Vérifie que les migrations du module Achat se posent ET se retirent
# proprement, sur PostgreSQL, dans un schéma isolé.
#
# Un `migrate` qui passe ne prouve rien sur le `rollback` : c'est au retrait
# que se révèlent les dépendances (une FK d'un autre module empêchant un DROP,
# un index orphelin, un CHECK qui survit). Ce script joue donc le cycle complet
# deux fois, pour prouver aussi qu'une réinstallation est possible.
#
# Le schéma de test est créé puis supprimé : vos données ne sont jamais
# touchées.
#
# Usage : Modules/Achat/tests/migrations.sh

set -uo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$RACINE"

SCHEMA="${ACHAT_TEST_SCHEMA:-achat_migrations}"

lire_env() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | tr -d '"'; }

export PGPASSWORD="$(lire_env DB_PASSWORD)"
HOTE="$(lire_env DB_HOST)"
PORT="$(lire_env DB_PORT)"
BASE="$(lire_env DB_DATABASE)"
UTILISATEUR="$(lire_env DB_USERNAME)"

psql_test() { psql -h "$HOTE" -p "$PORT" -U "$UTILISATEUR" -d "$BASE" -q "$@"; }

nettoyer() { psql_test -c "DROP SCHEMA IF EXISTS ${SCHEMA} CASCADE;" >/dev/null 2>&1 || true; }
trap nettoyer EXIT

echo "→ Schéma de test isolé : ${SCHEMA} (base ${BASE})"
psql_test -c "DROP SCHEMA IF EXISTS ${SCHEMA} CASCADE; CREATE SCHEMA ${SCHEMA};"

export DB_CONNECTION=pgsql
export DB_SCHEMA="$SCHEMA"
export APP_ENV=testing

compter_tables() {
    psql_test -tAc "SELECT count(*) FROM information_schema.tables
                    WHERE table_schema = '${SCHEMA}' AND table_name LIKE 'achat_%';"
}

echo
echo "→ Cycle 1 : migrate"
php artisan migrate --force --no-interaction >/dev/null || { echo "✗ migrate a échoué"; exit 1; }
APRES_MIGRATE="$(compter_tables | tr -d ' ')"
echo "  tables achat_* créées : ${APRES_MIGRATE}"

# Rollback CIBLÉ sur le module : `migrate:reset` démonterait toute
# l'application, et un défaut d'un autre module masquerait le résultat. On
# vérifie ici ce dont Achat est responsable, y compris le dénouement de la FK
# que le raccordement pose depuis Stock.
echo "→ Cycle 1 : rollback du module"
SORTIE_ROLLBACK="$(php artisan migrate:rollback --path=Modules/Achat/database/migrations --step=20 --force --no-interaction 2>&1)"

if echo "$SORTIE_ROLLBACK" | grep -qiE 'SQLSTATE|exception'; then
    echo "✗ Le rollback a produit une erreur SQL :"
    echo "$SORTIE_ROLLBACK" | grep -iE 'SQLSTATE|exception' | head -3
    exit 1
fi

APRES_ROLLBACK="$(compter_tables | tr -d ' ')"
echo "  tables achat_* restantes : ${APRES_ROLLBACK}"

if [ "$APRES_ROLLBACK" != "0" ]; then
    echo "✗ Le rollback laisse ${APRES_ROLLBACK} table(s) derrière lui"
    exit 1
fi

echo
echo "→ Cycle 2 : réinstallation (prouve qu'un module retiré se réinstalle)"
php artisan migrate --force --no-interaction >/dev/null || { echo "✗ la réinstallation a échoué"; exit 1; }
APRES_REINSTALL="$(compter_tables | tr -d ' ')"
echo "  tables achat_* recréées : ${APRES_REINSTALL}"

if [ "$APRES_REINSTALL" != "$APRES_MIGRATE" ]; then
    echo "✗ Réinstallation incomplète (${APRES_REINSTALL} au lieu de ${APRES_MIGRATE})"
    exit 1
fi

echo
echo "✓ migrate / rollback / réinstallation propres sur PostgreSQL (${APRES_MIGRATE} tables)"
