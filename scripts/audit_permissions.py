#!/usr/bin/env python3
"""
D-19 — audit de la ceinture de sécurité des modules Achat et Stock.

Trois questions auxquelles un développeur doit pouvoir répondre en une commande :

  1. une route est-elle atteignable sans permission (au routeur OU au
     contrôleur) ? C'est la seule des trois qui est bloquante ;
  2. une permission est-elle seedée sans jamais servir (droit fantôme) ?
  3. une permission est-elle exigée par le code sans être seedée (403 pour
     tout le monde, y compris l'administrateur) ?
"""

import json
import re
import subprocess
import sys
from pathlib import Path

RACINE = Path(__file__).resolve().parents[1]

# Permissions seedées pour des écrans SPÉCIFIÉS mais pas encore livrés.
# Elles ne sont pas des oublis : elles attendent leur écran, et le rôle qui
# les porte n'ouvre donc rien aujourd'hui. Toute entrée ici doit renvoyer à
# une section de SFD — sans quoi c'est un droit fantôme, à supprimer.
ATTENDUES = {
    # SFD Stock §3.8 — inventaires et ajustements (hors périmètre livré)
    'stock.inventaires.index': 'SFD Stock §3.8',
    'stock.inventaires.store': 'SFD Stock §3.8',
    'stock.inventaires.saisie': 'SFD Stock §3.8',
    'stock.inventaires.valider': 'SFD Stock §3.8',
    'stock.inventaires.annuler': 'SFD Stock §3.8',
    # SFD Stock §3.9 — journal des mouvements consultable
    'stock.mouvements.index': 'SFD Stock §3.9',
    # API_Inter_Modules §3 — endpoints Stock non exposés (lecture directe
    # par Achat, écart consigné au README du module Achat)
    'stock.api.view': 'API_Inter_Modules §3.2',
}


def routes(prefixe):
    sortie = subprocess.run(
        ['php', 'artisan', 'route:list', '--path=' + prefixe, '--json'],
        capture_output=True, text=True, cwd=RACINE,
    ).stdout.strip().splitlines()[-1]
    return json.loads(sortie)


def permissions_du_controleur(action):
    """Les permissions déclarées dans le `middleware()` d'un contrôleur."""
    if '@' not in action:
        return set()

    classe = action.split('@')[0]
    chemin = RACINE / (classe.replace('Modules\\', 'Modules/')
                       .replace('\\Http\\Controllers\\', '/app/Http/Controllers/')
                       .replace('\\', '/') + '.php')

    if not chemin.exists():
        return set()

    source = chemin.read_text()
    return set(re.findall(r"permission:([\w.|\-]+)", source))


def auditer(module, prefixe):
    print(f"\n── Module {module} ──")

    liste = routes(prefixe)
    nues = []

    for route in liste:
        action = route.get('action') or ''

        if 'Closure' in action:
            continue

        au_routeur = set(re.findall(r"permission:([\w.|\-]+)", str(route.get('middleware') or '')))
        au_controleur = permissions_du_controleur(action)

        if not au_routeur and not au_controleur:
            nues.append((route['method'], route['uri'], action.split('\\')[-1]))

    print(f"  {len(liste)} routes")

    if nues:
        print(f"  ⚠ {len(nues)} route(s) SANS permission :")
        for methode, uri, cible in nues:
            print(f"      {methode[:14]:14} {uri}  →  {cible}")
    else:
        print("  ✓ toutes les routes exigent une permission")

    return nues


def permissions_seedees(module):
    fichier = RACINE / f"Modules/{module}/config/permissions.php"
    if not fichier.exists():
        return set()
    return set(re.findall(r"'([a-z_]+\.[\w.\-]+)'\s*=>", fichier.read_text()))


def permissions_exigees(module):
    """Toutes les permissions citées par le code du module."""
    trouvees = set()
    for chemin in (RACINE / f"Modules/{module}").rglob('*.php'):
        if '/tests/' in str(chemin):
            continue
        texte = chemin.read_text(errors='ignore')
        trouvees |= set(re.findall(r"permission:([\w.|\-]+)", texte))
        trouvees |= set(re.findall(r"->can\('([\w.\-]+)'\)", texte))
        trouvees |= set(re.findall(r"@can\('([\w.\-]+)'\)", texte))
    for chemin in (RACINE / f"Modules/{module}/resources/views").rglob('*.blade.php'):
        texte = chemin.read_text(errors='ignore')
        trouvees |= set(re.findall(r"@can\('([\w.\-]+)'\)", texte))
        trouvees |= set(re.findall(r"can\('([\w.\-]+)'\)", texte))

    eclatees = set()
    for p in trouvees:
        eclatees |= set(p.split('|'))
    return {p for p in eclatees if '.' in p}


anomalies = 0

for module, prefixe in (('Achat', 'achat'), ('Stock', 'stock')):
    nues = auditer(module, prefixe)
    anomalies += len(nues)

    seedees = permissions_seedees(module)
    exigees = permissions_exigees(module)
    prefixe_module = module.lower()

    fantomes = {p for p in seedees if p not in exigees and p not in ATTENDUES}
    en_attente = {p for p in seedees if p not in exigees and p in ATTENDUES}
    manquantes = {p for p in exigees if p.startswith(prefixe_module + '.') and p not in seedees}

    if fantomes:
        print(f"  ⚠ {len(fantomes)} permission(s) seedée(s) mais jamais exigée(s) : {', '.join(sorted(fantomes))}")
        anomalies += len(fantomes)
    else:
        print(f"  ✓ les {len(seedees)} permissions seedées servent toutes"
              + (f" ({len(en_attente)} en attente de leur écran)" if en_attente else ""))

    for permission in sorted(en_attente):
        print(f"      · {permission} — attend son écran ({ATTENDUES[permission]})")

    if manquantes:
        print(f"  ⚠ {len(manquantes)} permission(s) exigée(s) mais NON seedée(s) : {', '.join(sorted(manquantes))}")
        anomalies += len(manquantes)
    else:
        print("  ✓ toute permission exigée est seedée")

print()
print("Audit conforme." if anomalies == 0 else f"{anomalies} anomalie(s) à examiner.")
sys.exit(0 if anomalies == 0 else 1)
