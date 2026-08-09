#!/usr/bin/env python3
"""
D-19 — revue des textes livrés face au catalogue gelé de SPEC_UX §15.

Les messages d'une application ne sont pas de la décoration : ce sont eux
que l'utilisateur lit quand il est bloqué, et c'est sur eux que la MOA s'est
prononcée. Un message reformulé « pour faire mieux » au fil du code défait
une décision prise ailleurs.

La comparaison est volontairement TOLÉRANTE sur la forme (apostrophes
typographiques, guillemets, espaces, ponctuation) et STRICTE sur les mots :
c'est le vocabulaire qui a été validé, pas la casse des apostrophes.

Les placeholders ({N}, {date}…) et les exemples chiffrés de la spec sont
ignorés : on cherche le fragment stable de la phrase.
"""

import re
import subprocess
import sys
import unicodedata
from pathlib import Path

RACINE = Path(__file__).resolve().parents[1]


def normaliser(texte):
    """Ramène un texte à ses mots, en minuscules et sans ponctuation."""
    texte = unicodedata.normalize('NFKD', texte)
    texte = ''.join(c for c in texte if not unicodedata.combining(c))
    texte = texte.lower()
    texte = re.sub(r"[’'`]", ' ', texte)
    texte = re.sub(r'[^a-z0-9]+', ' ', texte)
    return ' '.join(texte.split())


def corpus():
    """Tous les textes que l'application peut afficher."""
    morceaux = []
    for base, motifs in (
        ('Modules/Achat', ('*.php', '*.blade.php')),
        ('Modules/Stock', ('*.php', '*.blade.php')),
        ('public/js/modules', ('*.js',)),
    ):
        for motif in motifs:
            for chemin in (RACINE / base).rglob(motif):
                morceaux.append(chemin.read_text(errors='ignore'))
    return normaliser('\n'.join(morceaux))


def textes_geles():
    spec = (RACINE / 'SPEC_UX_Achat.md').read_text()
    section = spec[spec.index('### 15.1'):spec.index('## 16.')]
    return [t.strip() for t in re.findall(r'«\s*([^»]{25,200})\s*»', section)]


TEXTE_APPLICATION = corpus()

introuvables = []
examines = 0

for texte in textes_geles():
    # Le fragment stable : avant tout placeholder, exemple ou bouton.
    fragment = re.split(r'[{\[(]', texte)[0]
    mots = normaliser(fragment).split()

    # Les exemples chiffrés de la spec (« 650 000 FCFA », « 27/07/2026 »)
    # ne figurent pas tels quels dans le code : on les retire.
    mots = [m for m in mots if not m.isdigit()]

    if len(mots) < 4:
        continue

    examines += 1

    # On cherche les 6 premiers mots significatifs : assez pour identifier
    # la phrase, assez court pour tolérer une fin variable.
    aiguille = ' '.join(mots[:6])

    if aiguille not in TEXTE_APPLICATION:
        introuvables.append((aiguille, texte[:70]))

print(f"{examines} textes gelés (SPEC_UX §15) confrontés au code")

if introuvables:
    print(f"\n{len(introuvables)} écart(s) — le code dit autre chose que la spec :\n")
    for aiguille, original in introuvables:
        print(f"  · spec : « {original}… »")
        print(f"    recherché : « {aiguille} »")
    print("\nÀ trancher : corriger le code, ou amender la spec si le texte livré est meilleur.")
else:
    print("✓ tous les textes gelés se retrouvent dans le code.")

sys.exit(0)
