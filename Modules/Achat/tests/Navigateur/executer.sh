#!/usr/bin/env bash
# Vérification côté navigateur de l'écran A-02 (liste des bons de commande).
#
# On rend la page réelle, on sert les vraies charges JSON de l'application,
# puis on exécute le JavaScript réel de la vue dans un DOM (jsdom) pour
# contrôler ce que l'utilisateur voit — colonnes, pilules, montants qualifiés,
# pictogramme de régularisation, boutons d'action — et ce qu'il déclenche —
# filtres, recherche, pied de tableau, état vide.
#
# Ces contrôles complètent les tests PHPUnit, qui s'arrêtent à la charge JSON.
#
# Usage : Modules/Achat/tests/Navigateur/executer.sh
# Prérequis : un utilisateur disposant de « achat.bons_commande.index » en base.

set -euo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"
ICI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

export ACHAT_ARTEFACTS="${ACHAT_ARTEFACTS:-$(mktemp -d)/verif_achat}"

# jsdom n'est pas une dépendance du projet : on l'installe hors de l'arbre
# applicatif pour ne rien ajouter au package.json de production. Le cache est
# partagé avec la vérification du module Stock.
if [ -z "${JSDOM_PATH:-}" ]; then
    CACHE="${TMPDIR:-/tmp}/jsdom-stock"
    if [ ! -d "$CACHE/node_modules/jsdom" ]; then
        echo "→ Installation de jsdom dans $CACHE"
        mkdir -p "$CACHE"
        [ -f "$CACHE/package.json" ] || echo '{"name":"jsdom-achat","private":true,"version":"1.0.0"}' > "$CACHE/package.json"
        (cd "$CACHE" && npm install jsdom --silent --no-fund --no-audit)
    fi
    export JSDOM_PATH="$CACHE/node_modules/jsdom"
fi

cd "$RACINE"

echo "→ Production des artefacts (HTML rendu + charges JSON réelles)"
php artisan tinker --execute="require '$ICI/artefacts.php';"

echo
echo "→ Vérification du rendu de l'écran A-02"
node "$ICI/rendu.cjs"

echo
echo "→ Vérification des écrans de saisie A-03 et M-01"
node "$ICI/saisie.cjs"

echo
echo "→ Vérification du circuit BROUILLON ⇄ SOUMIS"
node "$ICI/circuit.cjs"

echo
echo "→ Vérification de la fiche A-04"
node "$ICI/fiche.cjs"
