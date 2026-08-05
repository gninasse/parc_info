#!/usr/bin/env bash
# Rejoue la suite du module Achat sur PostgreSQL (convention 6 / SFD §1.7).
#
# Les migrations et les contraintes CHECK ne se comportent pas de la même
# façon sur SQLite et PostgreSQL : réussir sur l'un ne prouve rien pour
# l'autre. Cette suite s'exécute donc réellement contre PostgreSQL, dans un
# SCHÉMA ISOLÉ, ce qui laisse le schéma « public » et ses données intacts.
#
# Usage : Modules/Achat/tests/postgres.sh [options phpunit]

set -uo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
ICI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$RACINE"

SCHEMA="${ACHAT_TEST_SCHEMA:-achat_portabilite}"

lire_env() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | tr -d '"'; }

export PGPASSWORD="$(lire_env DB_PASSWORD)"
HOTE="$(lire_env DB_HOST)"
PORT="$(lire_env DB_PORT)"
BASE="$(lire_env DB_DATABASE)"
UTILISATEUR="$(lire_env DB_USERNAME)"

psql_test() { psql -h "$HOTE" -p "$PORT" -U "$UTILISATEUR" -d "$BASE" -q "$@"; }

nettoyer() {
    psql_test -c "DROP SCHEMA IF EXISTS ${SCHEMA} CASCADE;" >/dev/null 2>&1 || true
}
trap nettoyer EXIT

echo "→ Schéma de test isolé : ${SCHEMA} (base ${BASE})"
psql_test -c "DROP SCHEMA IF EXISTS ${SCHEMA} CASCADE; CREATE SCHEMA ${SCHEMA};"

echo "→ Exécution de la suite Achat sur PostgreSQL"
DB_SCHEMA="$SCHEMA" \
    ./vendor/bin/phpunit --configuration "${ICI}/phpunit.pgsql.xml" "$@"

CODE=$?

if [ $CODE -eq 0 ]; then
    echo "✓ Portabilité PostgreSQL vérifiée"
else
    echo "✗ Échec sur PostgreSQL (code ${CODE})"
fi

exit $CODE
