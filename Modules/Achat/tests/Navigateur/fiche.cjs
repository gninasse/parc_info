// Vérification navigateur de la fiche A-04 (bon de commande).
//
// On charge le HTML réellement rendu par Blade pour trois états (PARTIEL,
// BROUILLON, SOUMIS) et on contrôle ce que l'utilisateur voit : bandeau,
// barre d'actions issue de la grille serveur (doctrine §0.3), onglets,
// colonnes de livraison, chronologie. Le JS de la fiche est ensuite exécuté
// pour prouver que les commandes se branchent sans erreur.

const fs = require('fs');
const path = require('path');
const { JSDOM } = require(process.env.JSDOM_PATH || 'jsdom');

const DOSSIER = process.env.ACHAT_ARTEFACTS || require('os').tmpdir() + '/verif_achat';
const RACINE = path.resolve(__dirname, '../../../..');

let echecs = 0;
const verifier = (nom, condition, detail = '') => {
    if (condition) {
        console.log(`  ✓ ${nom}${detail ? ' — ' + detail : ''}`);
    } else {
        echecs++;
        console.log(`  ✗ ${nom}${detail ? ' — ' + detail : ''}`);
    }
};

const existe = (fichier) => fs.existsSync(`${DOSSIER}/${fichier}`);
const monter = (fichier) => new JSDOM(fs.readFileSync(`${DOSSIER}/${fichier}`, 'utf8')).window.document;

// ── Fiche d'un bon PARTIEL : l'état le plus riche ──────────────────────────

if (existe('fiche-partiel.html')) {
    console.log('── Fiche A-04 d\'un bon PARTIEL ──');
    const doc = monter('fiche-partiel.html');

    verifier('le bandeau porte la pilule Partiel',
        [...doc.querySelectorAll('.badge')].some((b) => b.textContent.trim() === 'Partiel'));

    verifier('le montant TTC est en grand et qualifié',
        (doc.querySelector('.fs-3.fw-bold')?.textContent ?? '').includes('FCFA TTC'));

    verifier('l\'onglet Lignes montre les colonnes de livraison',
        doc.body.textContent.includes('Qté livrée') && doc.body.textContent.includes('Reste'));

    verifier('chaque ligne engagée porte sa barre de progression',
        doc.querySelectorAll('#onglet-lignes .progress[role="progressbar"]').length > 0);

    verifier('la barre d\'actions offre le PDF (modale iframe)',
        doc.getElementById('action-pdf') !== null);

    verifier('la clôture du reliquat est offerte (M-03)',
        doc.getElementById('action-cloturer') !== null);

    verifier('l\'annulation n\'existe pas sur un bon réceptionné (M-07)',
        doc.getElementById('action-annuler') === null);

    verifier('Modifier est grisé avec son diagnostic (§0.3)',
        [...doc.querySelectorAll('#barre-actions button[disabled]')]
            .some((b) => (b.getAttribute('title') ?? '').includes('annulation ou la clôture')));

    verifier('la modale PDF est présente', doc.getElementById('pdfModal') !== null);

    verifier('l\'onglet Chronologie existe avec son compteur',
        doc.getElementById('onglet-chronologie') !== null
        && /Chronologie/.test(doc.body.textContent));

    verifier('la chronologie raconte la validation',
        doc.getElementById('onglet-chronologie').textContent.includes('Validé — numéro'));

    verifier('la chronologie raconte la réception intégrée',
        doc.getElementById('onglet-chronologie').textContent.includes('Réception'));
} else {
    verifier('artefact fiche-partiel.html disponible', false);
}

// ── Fiche d'un BROUILLON : rien à livrer, rien d'affiché ───────────────────

if (existe('fiche-brouillon.html')) {
    console.log('\n── Fiche A-04 d\'un BROUILLON ──');
    const doc = monter('fiche-brouillon.html');

    verifier('le numéro affiché est « Brouillon #n » en italique',
        doc.querySelector('h5.fst-italic') !== null);

    verifier('les colonnes de livraison sont absentes avant validation',
        !doc.getElementById('onglet-lignes').textContent.includes('Qté livrée'));

    verifier('Modifier est offert (lien actif)',
        [...doc.querySelectorAll('#barre-actions a')].some((a) => a.textContent.includes('Modifier')));

    verifier('le PDF n\'est pas proposé sur un brouillon',
        doc.getElementById('action-pdf') === null);

    verifier('la chronologie commence par la création',
        doc.getElementById('onglet-chronologie').textContent.includes('Brouillon créé'));
}

// ── Fiche d'un bon SOUMIS : le visa (l'utilisateur des artefacts l'a) ──────

if (existe('fiche-soumis.html')) {
    console.log('\n── Fiche A-04 d\'un bon SOUMIS ──');
    const doc = monter('fiche-soumis.html');

    verifier('la pilule Soumis est en jaune (verrouillé, en officialisation)',
        [...doc.querySelectorAll('.badge')].some((b) =>
            b.textContent.trim() === 'Soumis' && b.className.includes('bg-warning')));

    verifier('Valider est offert au validateur',
        doc.getElementById('action-valider') !== null);

    verifier('Renvoyer est offert au validateur',
        doc.getElementById('action-renvoyer') !== null);

    verifier('la chronologie raconte la soumission',
        doc.getElementById('onglet-chronologie').textContent.includes('Soumis au visa'));
}

// ── Le JS de la fiche s'exécute sans erreur et branche les commandes ───────

if (existe('fiche-partiel.html')) {
    console.log('\n── Exécution du JS de la fiche ──');

    const html = fs.readFileSync(`${DOSSIER}/fiche-partiel.html`, 'utf8');
    const erreurs = [];

    const dom = new JSDOM(html, {
        runScripts: 'dangerously',
        url: 'https://parc_info.local/achat/bons-commande/1',
        beforeParse(window) {
            const clics = {};
            const delegues = {};
            // Double minimal de jQuery : la fiche (et les scripts inline du
            // layout) ne se servent que de $(sel), .length, .data, .on, .attr.
            const fabriquer = (selecteur) => {
                // $(document).on('click', '.js-x', …) : délégation. Le double
                // la retient à part, sinon BR-03 (bordereaux de réception)
                // passerait pour non branché.
                if (selecteur === window.document || selecteur === window) {
                    return {
                        length: 1,
                        on: (evt, cible, gestionnaire) => {
                            if (typeof cible === 'string') delegues[cible] = gestionnaire;
                            return fabriquer(selecteur);
                        },
                        ready: (fn) => { fn(); return fabriquer(selecteur); },
                        data: () => undefined,
                        attr: () => undefined,
                        text: () => '',
                        each: () => fabriquer(selecteur),
                        prop: () => fabriquer(selecteur),
                    };
                }

                const elements = typeof selecteur === 'function'
                    ? []
                    : (typeof selecteur === 'string' && !selecteur.trim().startsWith('<')
                        ? Array.from(window.document.querySelectorAll(selecteur))
                        : []);
                const objet = {
                    length: elements.length,
                    data: (nom) => {
                        const brut = elements[0]?.getAttribute(`data-${nom}`);
                        try { return JSON.parse(brut); } catch { return brut; }
                    },
                    on: (evt, gestionnaire) => {
                        if (elements.length > 0) clics[selecteur] = gestionnaire;
                        return objet;
                    },
                    attr: (nom) => elements[0]?.getAttribute(nom) ?? 'jeton-test',
                    text: () => elements.map((e) => e.textContent).join(''),
                    val: () => elements[0]?.value,
                    prop: () => objet,
                    each: (fn) => { elements.forEach((e, i) => fn(i, e)); return objet; },
                    ready: (fn) => { fn(); return objet; },
                };
                return objet;
            };
            const $ = (sel) => (typeof sel === 'function' ? (window.__pret = sel) : fabriquer(sel));
            $.ajax = () => ({ done: () => ({ fail: () => ({ always: () => {} }) }), fail: () => {}, always: () => {} });
            const promesse = { done: () => promesse, fail: () => promesse, always: () => promesse, then: () => ({ catch: () => ({ then: () => {} }) }) };
            $.getJSON = () => promesse;
            $.ajaxSetup = () => {};
            window.$ = $;
            window.jQuery = $;
            window.Swal = { fire: () => ({ then: () => {} }) };
            window.bootstrap = { Tooltip: function () {}, Modal: function () { this.show = () => {}; this.hide = () => {}; } };
            window.Ziggy = { routes: {}, url: 'https://parc_info.local', port: null, defaults: {} };
            window.OverlayScrollbarsGlobal = { OverlayScrollbars: () => {} };
            window.route = () => '';
            window.__clics = clics;
            window.__delegues = delegues;
            window.onerror = (m) => erreurs.push(String(m));
        },
    });

    const envelopper = (source) => '(function(){\n'
        + source
            .replace(/^import .*$/gm, '')
            .replace(/^export (class|const|function)/gm, '$1')
            .replace(/^export .*$/gm, '')
        + '\n})();';

    const modalPdf = fs.readFileSync(`${RACINE}/public/js/modules/achat/shared/modal-pdf.js`, 'utf8');
    const actions = fs.readFileSync(`${RACINE}/public/js/modules/achat/shared/actions-bc.js`, 'utf8');
    const vue = fs.readFileSync(`${RACINE}/public/js/modules/achat/bons-commande/show.js`, 'utf8');

    const script = dom.window.document.createElement('script');
    script.textContent = [
        '(function(){\n' + modalPdf.replace(/^export const/m, 'const').replace(/^import .*$/gm, '') + '\nwindow.__ModalPdf = ModalPdf;})();',
        '(function(){\n' + actions.replace(/^export const/m, 'const').replace(/^import .*$/gm, '') + '\nwindow.__ActionsBc = ActionsBc;})();',
        '(function(){const ModalPdf = window.__ModalPdf; const ActionsBc = window.__ActionsBc;\n'
            + vue.replace(/^import .*$/gm, '')
            + '\n})();',
        'if (window.__pret) window.__pret();',
    ].join('\n');
    dom.window.document.body.appendChild(script);

    verifier('le JS de la fiche s\'exécute sans erreur', erreurs.length === 0, erreurs.join(' | '));
    verifier('les données du bon sont lues depuis la barre',
        dom.window.__clics && Object.keys(dom.window.__clics).length > 0,
        Object.keys(dom.window.__clics ?? {}).join(', '));

    // BR-03 : le bordereau d'une livraison s'ouvre dans la MODALE iframe,
    // jamais dans un onglet — même exigence que le PDF du bon.
    verifier('le bordereau de réception est branché sur la modale (BR-03)',
        typeof dom.window.__delegues?.['.js-bordereau'] === 'function',
        Object.keys(dom.window.__delegues ?? {}).join(', '));
}

console.log('');
if (echecs > 0) {
    console.error(`${echecs} contrôle(s) de la fiche en échec`);
    process.exit(1);
}
console.log('Fiche A-04 : tous les contrôles passent');
