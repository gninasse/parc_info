/**
 * Contrôle du dimensionnement du graphique du tableau de bord Achat (A-01).
 *
 * CE QUE CE SCRIPT PRÉVIENT
 *
 * Chart.js en `responsive: true` + `maintainAspectRatio: false` dimensionne
 * le canvas d'après la hauteur de son PARENT. Si ce parent n'a pas de hauteur
 * propre, il prend celle de son contenu — c'est-à-dire celle du canvas. Le
 * canvas grandit, le parent grandit, le canvas grandit encore : la boucle ne
 * converge jamais, le processeur sature et l'onglet fige. C'est exactement ce
 * qui a été constaté en usage réel sur le tableau de bord.
 *
 * Le défaut est INVISIBLE aux tests serveur (la réponse HTTP est correcte) et
 * aux tests de rendu jsdom classiques (pas de moteur de mise en page). D'où ce
 * contrôle structurel sur le HTML réellement produit.
 *
 * Deux parties :
 *   1. la démonstration du mécanisme (le modèle diverge / converge) ;
 *   2. le contrôle de la PAGE RÉELLE : tout canvas doit être enveloppé dans
 *      un conteneur dimensionné, et ce conteneur doit avoir une hauteur.
 *
 * Usage : node Modules/Achat/tests/Navigateur/gel-graphique.cjs [fichier.html]
 *   Sans argument, le fichier attendu est $ACHAT_ARTEFACTS/dashboard.html
 *   (produit par artefacts.php).
 */

'use strict';

const fs = require('fs');
const path = require('path');

let echecs = 0;

const ok = (message) => console.log(`  ✓ ${message}`);
const ko = (message) => {
    console.log(`  ⨯ ${message}`);
    echecs += 1;
};

// ── 1. Le mécanisme : pourquoi un conteneur est indispensable ─────────────

console.log('\n── Mécanisme de la boucle de redimensionnement ──');

function simuler({ hauteurParentFixee, tours }) {
    let hauteurCanvas = 150;

    for (let i = 0; i < tours; i += 1) {
        // Sans hauteur imposée, le parent épouse son contenu : le canvas.
        const hauteurParent = hauteurParentFixee ?? hauteurCanvas;
        // Le +2 modélise les marges internes de la carte : il suffit à amorcer
        // la divergence, et c'est bien ce qui se produit dans un vrai moteur.
        hauteurCanvas = hauteurParent + 2;
    }

    return hauteurCanvas;
}

const sansConteneur = simuler({ hauteurParentFixee: null, tours: 200 });
const avecConteneur = simuler({ hauteurParentFixee: 300, tours: 200 });

if (sansConteneur > 400) {
    ok(`sans conteneur, la hauteur diverge : 150px → ${sansConteneur}px en 200 tours`);
} else {
    ko(`la divergence n'est pas reproduite (${sansConteneur}px) : le modèle est faux`);
}

if (avecConteneur === 302) {
    ok(`avec un conteneur de 300px, la hauteur se stabilise à ${avecConteneur}px`);
} else {
    ko(`le conteneur ne stabilise pas la hauteur (${avecConteneur}px)`);
}

// ── 2. La page réelle ─────────────────────────────────────────────────────

console.log('\n── Structure de la page réelle (A-01) ──');

const artefacts = process.env.ACHAT_ARTEFACTS || '';
const fichier = process.argv[2] || path.join(artefacts, 'dashboard.html');

if (!fichier || !fs.existsSync(fichier)) {
    ko(`page introuvable : ${fichier || '(chemin vide)'} — lancer artefacts.php d'abord`);
    console.log(`\n${echecs} contrôle(s) en échec`);
    process.exit(1);
}

const html = fs.readFileSync(fichier, 'utf8');

// 2.1 — Chaque canvas est-il enveloppé dans une zone dimensionnée ?
const canvas = [...html.matchAll(/<canvas\b[^>]*>/g)];

if (canvas.length === 0) {
    // Le tableau de bord peut légitimement afficher l'encart « module vide »
    // (EV-01) au lieu du graphique : ce n'est pas un échec.
    ok('aucun canvas dans cette page (encart « module vide » ou graphique absent)');
} else {
    ok(`${canvas.length} canvas trouvé(s)`);

    canvas.forEach((element) => {
        const balise = element[0];
        const identifiant = (balise.match(/id="([^"]+)"/) || [])[1] || '(sans id)';

        // Le conteneur doit précéder immédiatement le canvas.
        const avant = html.slice(Math.max(0, element.index - 300), element.index);

        if (/class="[^"]*zone-graphique[^"]*"[^>]*>\s*$/.test(avant)) {
            ok(`« ${identifiant} » est enveloppé dans .zone-graphique`);
        } else {
            ko(`« ${identifiant} » n'est PAS dans .zone-graphique : risque de gel de l'onglet`);
        }

        // 2.2 — L'attribut height en dur trompe : la hauteur vient de la CSS.
        if (/\bheight="/.test(balise)) {
            ko(`« ${identifiant} » porte un attribut height= : il est ignoré et induit en erreur`);
        } else {
            ok(`« ${identifiant} » ne porte pas d'attribut height trompeur`);
        }
    });

    // 2.3 — La règle CSS existe-t-elle, et impose-t-elle une hauteur ?
    const regle = html.match(/\.zone-graphique\s*\{([^}]*)\}/);

    if (!regle) {
        ko('la règle CSS .zone-graphique est absente : le conteneur n\'a aucune hauteur');
    } else {
        const corps = regle[1];

        if (/height\s*:\s*\d+/.test(corps)) {
            ok(`.zone-graphique impose une hauteur (${corps.match(/height\s*:\s*[^;]+/)[0].trim()})`);
        } else {
            ko('.zone-graphique n\'impose pas de hauteur chiffrée : la boucle reste possible');
        }

        // `position: relative` est nécessaire : le canvas de Chart.js est
        // positionné en absolu et se rapporterait sinon à un ancêtre plus haut.
        if (/position\s*:\s*relative/.test(corps)) {
            ok('.zone-graphique est en position: relative');
        } else {
            ko('.zone-graphique n\'est pas en position: relative : la hauteur peut être ignorée');
        }
    }
}

console.log(
    echecs === 0
        ? '\nDimensionnement du graphique : conforme'
        : `\n${echecs} contrôle(s) en échec`
);

process.exit(echecs === 0 ? 0 : 1);
