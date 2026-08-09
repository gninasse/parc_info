#!/usr/bin/env python3
"""
D-19 — revue anti-code-mort des modules Achat et Stock.

Ce que cherche ce script, c'est ce qui coûte sans rien rendre : un fichier
JavaScript que plus aucune vue ne charge, une vue que plus aucun contrôleur ne
rend, un service que personne n'appelle, un écouteur branché sur un événement
qui n'est plus émis.

Le code mort n'est pas seulement inutile : il MENT. On le lit en croyant
comprendre le fonctionnement, on le maintient, on le corrige — pour rien.

Le script est volontairement prudent : il signale des candidats, il ne
supprime rien. Une référence dynamique (nom construit à l'exécution) lui
échappe forcément, d'où la vérification à la main avant tout retrait.
"""

import re
import sys
from pathlib import Path

RACINE = Path(__file__).resolve().parents[1]


def contenu_du_module(module):
    """Tout le texte du module et des vues qui pourraient le référencer."""
    morceaux = []
    for motif in ('*.php', '*.blade.php', '*.js', '*.json'):
        for chemin in (RACINE / f"Modules/{module}").rglob(motif):
            morceaux.append(chemin.read_text(errors='ignore'))
    for chemin in (RACINE / 'public/js/modules').rglob('*.js'):
        morceaux.append(chemin.read_text(errors='ignore'))
    return '\n'.join(morceaux)


def auditer(module):
    print(f"\n── Module {module} ──")
    texte = contenu_du_module(module)
    anomalies = []

    # 1. JavaScript jamais chargé par une vue
    racine_js = RACINE / f"public/js/modules/{module.lower()}"
    if racine_js.exists():
        orphelins = []
        for fichier in racine_js.rglob('*.js'):
            relatif = str(fichier.relative_to(RACINE / 'public/js/modules')).replace('\\', '/')
            nom = fichier.name
            # Chargé par <script src>, ou importé par un autre module JS
            if relatif not in texte and f"/{nom}" not in texte and f"'{nom}'" not in texte:
                orphelins.append(relatif)
        if orphelins:
            anomalies += orphelins
            print(f"  ⚠ {len(orphelins)} fichier(s) JS jamais référencé(s) :")
            for o in orphelins:
                print(f"      {o}")
        else:
            print("  ✓ tous les fichiers JS sont chargés ou importés")

    # 2. Vues jamais rendues
    racine_vues = RACINE / f"Modules/{module}/resources/views"
    if racine_vues.exists():
        orphelines = []
        for fichier in racine_vues.rglob('*.blade.php'):
            relatif = fichier.relative_to(racine_vues).as_posix().replace('.blade.php', '')
            point = relatif.replace('/', '.')
            nom = fichier.stem.replace('.blade', '')

            if fichier.name.startswith('_') or '/layouts/' in fichier.as_posix() or '/partials/' in fichier.as_posix():
                continue  # inclus par @include, souvent dynamiquement

            if point not in texte and nom not in texte:
                orphelines.append(relatif)
        if orphelines:
            anomalies += orphelines
            print(f"  ⚠ {len(orphelines)} vue(s) jamais rendue(s) :")
            for o in orphelines:
                print(f"      {o}")
        else:
            print("  ✓ toutes les vues sont rendues ou incluses")

    # 3. Services jamais instanciés
    racine_services = RACINE / f"Modules/{module}/app/Services"
    if racine_services.exists():
        inutilises = []
        for fichier in racine_services.glob('*.php'):
            classe = fichier.stem
            # On exclut la déclaration elle-même du décompte
            occurrences = len(re.findall(rf"\b{classe}\b", texte))
            if occurrences <= 1:
                inutilises.append(classe)
        if inutilises:
            anomalies += inutilises
            print(f"  ⚠ {len(inutilises)} service(s) jamais utilisé(s) : {', '.join(inutilises)}")
        else:
            print(f"  ✓ les {len(list(racine_services.glob('*.php')))} services sont tous utilisés")

    # 4. Écouteurs branchés sur un événement jamais émis
    racine_ecouteurs = RACINE / f"Modules/{module}/app/Listeners"
    if racine_ecouteurs.exists():
        orphelins = [f.stem for f in racine_ecouteurs.glob('*.php')
                     if len(re.findall(rf"\b{f.stem}\b", texte)) <= 1]
        if orphelins:
            anomalies += orphelins
            print(f"  ⚠ {len(orphelins)} écouteur(s) non enregistré(s) : {', '.join(orphelins)}")
        else:
            print("  ✓ les écouteurs sont tous enregistrés")

    return anomalies


total = []
for module in ('Achat', 'Stock'):
    total += auditer(module)

print()
if total:
    print(f"{len(total)} candidat(s) au retrait — À VÉRIFIER À LA MAIN avant suppression")
    print("(une référence construite dynamiquement échappe à ce script).")
else:
    print("Aucun code mort détecté.")

sys.exit(0)
