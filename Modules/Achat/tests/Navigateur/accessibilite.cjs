#!/usr/bin/env node
// D-19 — accessibilité des parcours critiques (SPEC_UX §18).
//
// Une bonne part de §18 se vérifie sur le HTML rendu : les attributs ARIA, la
// double information couleur + libellé, l'ordre de tabulation. Le reste (ce
// qu'un lecteur d'écran annonce VRAIMENT, le confort au clavier) dépend du
// navigateur et de l'outil d'assistance : il se contrôle à la main, et le
// simuler ici donnerait une fausse assurance.
//
// Ce harnais couvre donc ce qui est mécanisable, et le dit franchement pour
// le reste — voir TESTS_Achat.md §6.

const fs = require('fs');
const { JSDOM } = require(process.env.JSDOM_PATH || 'jsdom');

const DOSSIER = process.env.ACHAT_ARTEFACTS || require('os').tmpdir() + '/verif_achat';

let echecs = 0;
const verifier = (nom, condition, detail = '') => {
    if (condition) {
        console.log(`  ✓ ${nom}${detail ? ' — ' + detail : ''}`);
    } else {
        echecs++;
        console.log(`  ✗ ${nom}${detail ? ' — ' + detail : ''}`);
    }
};

const existe = (f) => fs.existsSync(`${DOSSIER}/${f}`);
const monter = (f) => new JSDOM(fs.readFileSync(`${DOSSIER}/${f}`, 'utf8')).window.document;

// ── La fiche d'un bon (A-04) ───────────────────────────────────────────────

if (existe('fiche-partiel.html')) {
    console.log('\n── Accessibilité de la fiche A-04 ──');
    const doc = monter('fiche-partiel.html');

    // §0.2 : jamais d'information par la couleur seule.
    const pilules = Array.from(doc.querySelectorAll('.badge, .pilule'));
    const muettes = pilules.filter((p) => p.textContent.trim() === '' && !p.getAttribute('aria-label'));
    verifier("aucune pilule ne porte l'information par la seule couleur",
        muettes.length === 0, `${pilules.length} pilules examinées`);

    // Les onglets : un lecteur d'écran doit savoir où il est.
    const onglets = Array.from(doc.querySelectorAll('[role="tab"]'));
    verifier('les onglets sont annoncés (role="tab")', onglets.length > 0, `${onglets.length} onglets`);
    verifier('chaque onglet déclare son état de sélection',
        onglets.every((o) => o.hasAttribute('aria-selected')));
    verifier('chaque onglet désigne son panneau',
        onglets.every((o) => o.hasAttribute('aria-controls')));

    const panneaux = Array.from(doc.querySelectorAll('[role="tabpanel"]'));
    verifier('chaque panneau existe pour son onglet',
        onglets.every((o) => panneaux.some((p) => p.id === o.getAttribute('aria-controls'))));

    // §18.2 : un bouton grisé doit dire POURQUOI, y compris au lecteur d'écran.
    const grises = Array.from(doc.querySelectorAll('[aria-disabled="true"], .disabled'));
    const sansDiagnostic = grises.filter((b) => {
        const titre = b.getAttribute('title') || b.getAttribute('data-bs-title') || '';
        const decrit = b.getAttribute('aria-describedby') || b.getAttribute('aria-label') || '';
        // Le diagnostic peut être porté par le parent (cas d'un bouton dans
        // un conteneur infobullé, motif Bootstrap courant).
        const parent = b.parentElement;
        const titreParent = parent ? (parent.getAttribute('title') || parent.getAttribute('data-bs-title') || '') : '';
        return !titre && !decrit && !titreParent;
    });
    verifier('tout élément grisé porte son diagnostic',
        sansDiagnostic.length === 0,
        sansDiagnostic.length ? sansDiagnostic.map((b) => b.textContent.trim().slice(0, 30)).join(' | ')
                              : `${grises.length} éléments grisés`);

    // Les images informatives ont un texte de remplacement.
    const images = Array.from(doc.querySelectorAll('img'));
    verifier('chaque image porte un texte de remplacement',
        images.every((i) => i.hasAttribute('alt')), `${images.length} images`);

    // Les champs de formulaire sont étiquetés.
    const champs = Array.from(doc.querySelectorAll('input:not([type=hidden]), select, textarea'));
    const orphelins = champs.filter((c) => {
        if (c.getAttribute('aria-label') || c.getAttribute('aria-labelledby')) return false;
        if (c.id && doc.querySelector(`label[for="${c.id}"]`)) return false;
        if (c.closest('label')) return false;
        if (c.getAttribute('placeholder')) return false; // dégradé, mais annoncé
        return true;
    });
    verifier('chaque champ de saisie est étiqueté',
        orphelins.length === 0,
        orphelins.length ? orphelins.map((c) => c.id || c.name || '?').join(', ') : `${champs.length} champs`);

    // Les tableaux de données ont des en-têtes.
    const tableaux = Array.from(doc.querySelectorAll('table'));
    verifier('chaque tableau porte des en-têtes de colonne',
        tableaux.every((t) => t.querySelectorAll('th').length > 0), `${tableaux.length} tableaux`);
}

// ── La liste des bons (A-02) ───────────────────────────────────────────────

if (existe('liste.html')) {
    console.log('\n── Accessibilité de la liste A-02 ──');
    const doc = monter('liste.html');

    const boutons = Array.from(doc.querySelectorAll('button'));
    const anonymes = boutons.filter((b) => {
        const texte = b.textContent.replace(/\s+/g, ' ').trim();
        return texte === ''
            && !b.getAttribute('aria-label')
            && !b.getAttribute('title')
            && !b.getAttribute('data-bs-title');
    });
    verifier("aucun bouton n'est muet (icône sans libellé ni aria-label)",
        anonymes.length === 0,
        anonymes.length ? anonymes.map((b) => b.className.slice(0, 40)).join(' | ') : `${boutons.length} boutons`);

    // Un lien qui n'ouvre rien désoriente autant qu'une erreur.
    const liens = Array.from(doc.querySelectorAll('a[href]'));
    const morts = liens.filter((a) => {
        const href = a.getAttribute('href');
        return href === '#' && !a.hasAttribute('data-bs-toggle') && !a.hasAttribute('role');
    });
    verifier('aucun lien mort (href="#" sans rôle)',
        morts.length === 0,
        morts.length ? morts.map((a) => a.textContent.trim().slice(0, 25)).join(' | ') : `${liens.length} liens`);

    const champs = Array.from(doc.querySelectorAll('input:not([type=hidden]), select'));
    const orphelins = champs.filter((c) => {
        if (c.getAttribute('aria-label') || c.getAttribute('aria-labelledby')) return false;
        if (c.id && doc.querySelector(`label[for="${c.id}"]`)) return false;
        if (c.closest('label') || c.getAttribute('placeholder')) return false;
        return true;
    });
    verifier('chaque filtre est étiqueté',
        orphelins.length === 0,
        orphelins.length ? orphelins.map((c) => c.id || c.name || '?').join(', ') : `${champs.length} champs`);
}

// ── La saisie d'un bon (A-03) — parcours critique ──────────────────────────

if (existe('form-create.html')) {
    console.log('\n── Accessibilité de la saisie A-03 ──');
    const doc = monter('form-create.html');

    const champs = Array.from(doc.querySelectorAll('input:not([type=hidden]), select, textarea'));
    const orphelins = champs.filter((c) => {
        if (c.getAttribute('aria-label') || c.getAttribute('aria-labelledby')) return false;
        if (c.id && doc.querySelector(`label[for="${c.id}"]`)) return false;
        if (c.closest('label') || c.getAttribute('placeholder')) return false;
        return true;
    });
    verifier('chaque champ de saisie est étiqueté',
        orphelins.length === 0,
        orphelins.length ? orphelins.map((c) => c.id || c.name || '?').join(', ') : `${champs.length} champs`);

    const obligatoires = Array.from(doc.querySelectorAll('[required]'));
    verifier("les champs obligatoires sont annoncés (required)",
        obligatoires.length > 0, `${obligatoires.length} champs obligatoires`);

    const boutons = Array.from(doc.querySelectorAll('button'));
    const anonymes = boutons.filter((b) => b.textContent.trim() === ''
        && !b.getAttribute('aria-label') && !b.getAttribute('title') && !b.getAttribute('data-bs-title'));
    verifier("aucun bouton muet dans le formulaire",
        anonymes.length === 0,
        anonymes.length ? anonymes.map((b) => b.className.slice(0, 40)).join(' | ') : `${boutons.length} boutons`);
}

console.log(`
Contrôlé à la main (non mécanisable — SPEC_UX §18.1) :
  · ordre de tabulation de A-03 et du wizard A-05
  · « Entrée » = clé suivante au wizard (le geste douchette)
  · focus initial des dialogues sur l'action la moins destructrice
  · retour du focus à l'élément déclencheur à la fermeture d'une modale`);

console.log(
    echecs === 0
        ? '\nAccessibilité : tous les contrôles mécanisables passent'
        : `\n${echecs} contrôle(s) d'accessibilité en échec`
);

process.exit(echecs === 0 ? 0 : 1);
