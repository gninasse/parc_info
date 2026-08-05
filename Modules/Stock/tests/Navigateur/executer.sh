#!/usr/bin/env bash
# Vérification côté navigateur des écrans Statistiques et États du module Stock.
#
# Le principe : on rend les pages et on sert les vraies charges JSON de
# l'application, puis on exécute le JavaScript réel des vues dans un DOM
# (jsdom) pour contrôler ce que l'utilisateur voit — colonnes, formatage,
# totaux, KPI, graphiques — et ce qu'il déclenche — pagination, filtres,
# liens d'export.
#
# Ces contrôles complètent les tests PHPUnit, qui s'arrêtent à la réponse HTTP.
#
# Usage : Modules/Stock/tests/Navigateur/executer.sh
# Prérequis : un utilisateur disposant de « stock.rapports.view » en base.

set -euo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"
ICI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

export STOCK_ARTEFACTS="${STOCK_ARTEFACTS:-$(mktemp -d)/verif_stock}"

# jsdom n'est pas une dépendance du projet : on l'installe hors de l'arbre
# applicatif pour ne rien ajouter au package.json de production.
if [ -z "${JSDOM_PATH:-}" ]; then
    CACHE="${TMPDIR:-/tmp}/jsdom-stock"
    if [ ! -d "$CACHE/node_modules/jsdom" ]; then
        echo "→ Installation de jsdom dans $CACHE"
        mkdir -p "$CACHE"
        # Sans package.json local, npm remonte l'arborescence et n'installe
        # rien ici ; on ancre donc explicitement une racine de paquet.
        [ -f "$CACHE/package.json" ] || echo '{"name":"jsdom-stock","private":true,"version":"1.0.0"}' > "$CACHE/package.json"
        (cd "$CACHE" && npm install jsdom --silent --no-fund --no-audit)
    fi
    export JSDOM_PATH="$CACHE/node_modules/jsdom"
fi

cd "$RACINE"

echo "→ Production des artefacts (HTML rendu + charges JSON réelles)"
php artisan tinker --execute="require '$ICI/artefacts.php';"

echo
echo "→ Vérification du rendu"
node "$ICI/rendu.cjs"

echo
echo "→ Vérification des interactions"
node "$ICI/interactions.cjs"
