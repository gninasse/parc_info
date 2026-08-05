// Vérification navigateur de l'écran A-02 (liste des bons de commande).
//
// Le principe : on charge le HTML réellement rendu par Blade et la charge JSON
// réellement servie par `.data`, puis on exécute le VRAI JavaScript de la vue
// dans un DOM. On contrôle ainsi ce que l'utilisateur voit — colonnes, pilules,
// montants qualifiés, pictogramme de régularisation, boutons d'action — et ce
// qu'il déclenche — filtres, recherche, pied de tableau, état vide.
//
// Les tests PHPUnit s'arrêtent à la charge JSON ; ceux-ci vont jusqu'au pixel.

const fs = require('fs');
const path = require('path');
const { JSDOM, VirtualConsole } = require(process.env.JSDOM_PATH || 'jsdom');

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

const lireJson = (fichier) => JSON.parse(fs.readFileSync(`${DOSSIER}/${fichier}`, 'utf8'));

/**
 * Monte la page dans un DOM et y exécute le module JS de la vue.
 *
 * Bootstrap Table n'est pas chargé (jsdom ne récupère pas les <script src>) :
 * on le remplace par un double qui enregistre les options reçues et rejoue les
 * événements. Cela permet d'appeler les VRAIS formatters de la vue et de
 * vérifier le HTML qu'ils produisent, sans réimplémenter la bibliothèque.
 */
async function monter(charge) {
    const html = fs.readFileSync(`${DOSSIER}/liste.html`, 'utf8');
    const virtualConsole = new VirtualConsole();
    const erreursJs = [];
    virtualConsole.on('jsdomError', (e) => erreursJs.push(e.message));
    virtualConsole.on('error', (e) => erreursJs.push(String(e)));

    const etat = { options: {}, evenements: {}, rafraichissements: 0 };

    const dom = new JSDOM(html, {
        runScripts: 'dangerously',
        pretendToBeVisual: true,
        url: 'https://parc_info.local/achat/bons-commande',
        virtualConsole,
        beforeParse(window) {
            // Double de jQuery : juste ce dont la vue se sert réellement.
            const fabriquer = (selecteur) => {
                const elements = typeof selecteur === 'string' && selecteur.trim().startsWith('<')
                    ? [window.document.createElement('span')]
                    : (typeof selecteur === 'string'
                        ? Array.from(window.document.querySelectorAll(selecteur))
                        : (selecteur ? [selecteur] : []));

                const objet = {
                    elements,
                    length: elements.length,
                    text(valeur) {
                        if (valeur === undefined) return elements.map((e) => e.textContent).join('');
                        elements.forEach((e) => { e.textContent = valeur; });
                        return objet;
                    },
                    html: () => elements.map((e) => e.innerHTML).join(''),
                    attr: (nom) => (elements[0] ? elements[0].getAttribute(nom) : 'jeton-test'),
                    val(valeur) {
                        if (valeur === undefined) return elements[0] ? elements[0].value : undefined;
                        elements.forEach((e) => { e.value = valeur; });
                        return objet;
                    },
                    is: (etatCss) => etatCss === ':checked' && elements.some((e) => e.checked),
                    prop(nom, valeur) {
                        elements.forEach((e) => { e[nom] = valeur; });
                        return objet;
                    },
                    map: (fn) => ({ get: () => elements.map((e, i) => fn(i, e)) }),
                    toggleClass(classe, condition) {
                        elements.forEach((e) => e.classList.toggle(classe, Boolean(condition)));
                        return objet;
                    },
                    addClass(classe) {
                        elements.forEach((e) => e.classList.add(classe));
                        return objet;
                    },
                    each(fn) {
                        elements.forEach((e, i) => fn(i, e));
                        return objet;
                    },
                    /**
                     * Deux formes, comme jQuery : `.on(evt, gestionnaire)` et
                     * la forme DÉLÉGUÉE `.on(evt, selecteur, gestionnaire)`,
                     * que la liste utilise pour ses boutons de commande —
                     * ceux-ci sont recréés à chaque rendu du tableau, donc
                     * seul un écouteur délégué peut les atteindre.
                     */
                    on(evenements, arg2, arg3) {
                        const delegue = typeof arg2 === 'string';
                        const gestionnaire = delegue ? arg3 : arg2;

                        (etat.evenements[evenements] ??= []).push(gestionnaire);

                        evenements.split(' ').forEach((nomComplet) => {
                            const nom = nomComplet.split('.')[0];

                            elements.forEach((element) => {
                                element.addEventListener(nom, (evenement) => {
                                    if (!delegue) {
                                        gestionnaire.call(element, evenement);

                                        return;
                                    }

                                    const cible = evenement.target.closest(arg2);

                                    if (cible && element.contains(cible)) {
                                        gestionnaire.call(cible, evenement);
                                    }
                                });
                            });
                        });

                        return objet;
                    },
                    bootstrapTable(action, options) {
                        if (action === 'refreshOptions') Object.assign(etat.options, options);
                        if (action === 'refresh') etat.rafraichissements++;
                        return objet;
                    },
                };

                return objet;
            };

            fabriquer.ready = (fn) => fn();
            const jq = (arg) => (typeof arg === 'function' ? arg() : fabriquer(arg));
            Object.assign(jq, fabriquer);
            jq.ajaxSetup = () => {};

            window.$ = window.jQuery = jq;
            window.Ziggy = { routes: {}, url: 'https://parc_info.local', port: null, defaults: {} };
            window.OverlayScrollbarsGlobal = { OverlayScrollbars: () => {} };
            window.route = () => '';
            window.bootstrap = { Tooltip: function () {} };
            window.__etat = etat;
        },
    });

    // Le module de la vue est un ES module servi par <script type="module"> :
    // jsdom ne le charge pas. On l'injecte tel quel, en neutralisant son seul
    // import (les formatters du Catalogue, réintroduits juste après).
    //
    // Chaque fichier est enveloppé dans sa propre fonction : ce sont deux
    // modules distincts dans le navigateur, chacun avec son `echapper` privé.
    // Les concaténer à plat les ferait entrer en collision — un artefact du
    // harnais, pas un défaut de l'application.
    const envelopper = (source) => '(function(){\n'
        + source
            .replace(/^import .*$/gm, '')
            .replace(/^export (class|const|function)/gm, '$1')
            .replace(/^export .*$/gm, '')
        + '\n})();';

    const catalogue = fs.readFileSync(`${RACINE}/public/js/modules/catalogue/formatters.js`, 'utf8');
    const modalPdf = fs.readFileSync(`${RACINE}/public/js/modules/achat/shared/modal-pdf.js`, 'utf8');
    const vue = fs.readFileSync(`${RACINE}/public/js/modules/achat/bons-commande/index.js`, 'utf8');

    const script = dom.window.document.createElement('script');
    script.textContent = [
        envelopper(catalogue),
        // ModalPdf est importé par la vue : on l'expose comme le ferait le
        // graphe de modules du navigateur.
        '(function(){\n'
            + modalPdf.replace(/^export const/m, 'const').replace(/^import .*$/gm, '')
            + '\nwindow.__ModalPdf = ModalPdf;})();',
        '(function(){const ModalPdf = window.__ModalPdf;\n'
            + vue.replace(/^import .*$/gm, '').replace(/^export .*$/gm, '')
            + '\n})();',
    ].join('\n');
    dom.window.document.body.appendChild(script);

    // Rejoue le chargement des données, comme le ferait Bootstrap Table.
    (etat.evenements['load-success.bs.table'] || []).forEach((gestionnaire) => {
        gestionnaire({}, charge);
    });

    return { dom, window: dom.window, doc: dom.window.document, etat, erreursJs };
}

/** Rend une ligne complète en appelant les vrais formatters de la vue. */
const rendreLigne = (window, ligne) => ({
    numero: window.bcNumeroFormatter(ligne.numero_affiche, ligne),
    montant: window.bcMontantTtcFormatter(ligne.montant_ttc, ligne),
    progression: window.bcProgressionFormatter(ligne.progression, ligne),
    statut: window.bcStatutFormatter(ligne.statut, ligne),
});

(async () => {
    const charge = lireJson('liste.json');
    const { window, doc, etat, erreursJs } = await monter(charge);

    console.log('\n── Chargement de la page ──');
    verifier('aucune erreur JavaScript', erreursJs.length === 0, erreursJs.join(' | '));
    verifier('la charge contient des bons', charge.rows.length > 0, `${charge.total} bon(s)`);

    console.log('\n── Colonnes du tableau (SPEC_UX A-02) ──');
    const entetes = Array.from(doc.querySelectorAll('#bons-commande-table thead th'))
        .map((th) => th.textContent.trim());
    ['Numéro', 'Fournisseur', 'Date', 'Service demandeur', 'Lignes', 'Montant TTC', 'Livraison', 'Statut', 'Créé par']
        .forEach((colonne) => verifier(`colonne « ${colonne} »`, entetes.includes(colonne)));
    verifier('la sélection se fait par radio (pattern du projet)',
        doc.querySelector('#bons-commande-table th[data-radio="true"]') !== null);
    verifier('le tableau est en click-to-select simple',
        doc.querySelector('#bons-commande-table').getAttribute('data-click-to-select') === 'true'
        && doc.querySelector('#bons-commande-table').getAttribute('data-single-select') === 'true');

    verifier(
        'pagination serveur',
        doc.querySelector('#bons-commande-table').getAttribute('data-side-pagination') === 'server'
    );

    console.log('\n── Filtres ──');
    ['filter-recherche', 'filter-fournisseur', 'filter-du', 'filter-au', 'filter-regularisations', 'filter-mes-brouillons']
        .forEach((id) => verifier(`filtre #${id}`, doc.getElementById(id) !== null));

    const pilules = Array.from(doc.querySelectorAll('#filter-statut input[name="statut"]'))
        .map((input) => input.value);
    verifier(
        'les 7 pilules de statut sont proposées',
        ['BROUILLON', 'SOUMIS', 'VALIDE', 'PARTIEL', 'LIVRE', 'CLOTURE', 'ANNULE'].every((s) => pilules.includes(s)),
        pilules.join(', ')
    );
    verifier('les pilules sont multi-sélection (cases à cocher)',
        Array.from(doc.querySelectorAll('#filter-statut input')).every((i) => i.type === 'checkbox'));

    verifier('les filtres sont transmis au serveur', typeof etat.options.queryParams === 'function');
    if (typeof etat.options.queryParams === 'function') {
        const parametres = etat.options.queryParams({});
        ['statut', 'fournisseur_id', 'du', 'au', 'regularisations', 'mes_brouillons', 'search']
            .forEach((cle) => verifier(`paramètre « ${cle} » envoyé`, cle in parametres));
    }

    console.log('\n── Pied de tableau (compteur du filtre courant) ──');
    const compteur = doc.getElementById('pied-compteur').textContent;
    const total = doc.getElementById('pied-total').textContent;
    verifier('le compteur annonce le nombre de bons', /\d+\s*bons?/.test(compteur), compteur);
    verifier('le total est qualifié « FCFA TTC » (SPEC_UX §0.4)', /FCFA TTC/.test(total), total);

    const sommeAttendue = charge.rows.reduce((s, l) => s + l.montant_ttc, 0);
    verifier(
        'le total affiché est celui calculé par le serveur',
        Math.abs(charge.montant_ttc_affiche - sommeAttendue) < 1,
        `serveur ${charge.montant_ttc_affiche} / lignes ${sommeAttendue}`
    );

    console.log('\n── Rendu des lignes ──');
    const rendues = charge.rows.map((l) => ({ ligne: l, html: rendreLigne(window, l) }));

    verifier(
        'chaque montant est qualifié TTC',
        rendues.every(({ html }) => html.montant.includes('FCFA TTC'))
    );
    verifier(
        'chaque statut porte son libellé en toutes lettres (jamais la couleur seule)',
        rendues.every(({ ligne, html }) => html.statut.includes(ligne.statut_label))
    );

    const brouillons = rendues.filter(({ ligne }) => ligne.numero_affiche.startsWith('Brouillon'));
    if (brouillons.length > 0) {
        verifier(
            'un brouillon s\'affiche en italique gris',
            brouillons.every(({ html }) => html.numero.includes('<em') && html.numero.includes('text-muted'))
        );
    }

    const regularisations = rendues.filter(({ ligne }) => ligne.est_regularisation);
    if (regularisations.length > 0) {
        verifier(
            'un bon de régularisation porte son pictogramme',
            regularisations.every(({ html }) => html.numero.includes('pictogramme-regularisation'))
        );
    }
    verifier(
        'un bon ordinaire ne porte PAS le pictogramme de régularisation',
        rendues.filter(({ ligne }) => !ligne.est_regularisation)
            .every(({ html }) => !html.numero.includes('pictogramme-regularisation'))
    );

    const avantEngagement = rendues.filter(({ ligne }) =>
        ['BROUILLON', 'SOUMIS'].includes(ligne.statut));
    if (avantEngagement.length > 0) {
        verifier(
            'aucune barre de livraison avant engagement',
            avantEngagement.every(({ html }) => !html.progression.includes('progress-bar'))
        );
    }

    const engages = rendues.filter(({ ligne }) => ligne.progression !== null);
    if (engages.length > 0) {
        verifier(
            'la barre de livraison est accessible (role + aria-valuenow)',
            engages.every(({ html }) =>
                html.progression.includes('role="progressbar"') && html.progression.includes('aria-valuenow'))
        );
    }

    console.log('\n── Toolbar et drapeaux (pattern du projet, SPEC_UX §0.3) ──');
    verifier('la toolbar existe et est câblée au tableau',
        doc.getElementById('toolbar') !== null
        && doc.querySelector('#bons-commande-table').getAttribute('data-toolbar') === '#toolbar');

    ['btn-add', 'btn-show', 'btn-edit', 'btn-delete', 'btn-imprimer']
        .forEach((id) => verifier(`bouton #${id} présent (utilisateur habilité)`,
            doc.getElementById(id) !== null));

    verifier('les boutons d\'action naissent désactivés (rien n\'est sélectionné)',
        ['btn-show', 'btn-edit', 'btn-delete', 'btn-imprimer']
            .every((id) => doc.getElementById(id)?.hasAttribute('disabled')));

    verifier('chaque ligne émet ses drapeaux peut_*',
        charge.rows.every((l) => typeof l.peut_voir === 'boolean'
            && typeof l.peut_modifier === 'boolean'
            && typeof l.peut_imprimer === 'boolean'));

    const valides = charge.rows.filter((l) => ['VALIDE', 'PARTIEL', 'LIVRE', 'CLOTURE'].includes(l.statut));
    if (valides.length > 0) {
        verifier('un bon engagé porte l\'URL de son PDF (modale iframe)',
            valides.every((l) => !l.peut_imprimer || typeof l.url_pdf === 'string'));
    }

    // Le diagnostic n'accompagne que les statuts où la grille émet encore le
    // bouton grisé (VALIDE, PARTIEL) ; sur LIVRE/CLOTURE l'action a disparu.
    const grisables = charge.rows.filter((l) => ['VALIDE', 'PARTIEL'].includes(l.statut));
    if (grisables.length > 0) {
        verifier('un bon engagé grise Modifier avec son diagnostic',
            grisables.every((l) => !l.peut_modifier && String(l.diagnostic_modification ?? '').length > 0));
    }

    const annules = charge.rows.filter((l) => l.statut === 'ANNULE');
    if (annules.length > 0) {
        verifier('un bon annulé n\'offre pas de PDF',
            annules.every((l) => !l.peut_imprimer && l.url_pdf === null));
    }

    console.log('\n── Modale PDF (impression en iframe, jamais un onglet) ──');
    verifier('la modale PDF est présente sur la liste', doc.getElementById('pdfModal') !== null);
    verifier('elle porte une iframe d\'aperçu', doc.getElementById('pdf-iframe') !== null);
    ['pdf-imprimer', 'pdf-telecharger', 'pdf-onglet']
        .forEach((id) => verifier(`commande #${id}`, doc.getElementById(id) !== null));

    console.log('\n── Échappement (aucune injection depuis les données) ──');
    verifier(
        'le numéro est échappé par le formatter',
        window.bcNumeroFormatter('<script>alert(1)</script>', { est_regularisation: false }).includes('&lt;script&gt;')
    );
    verifier(
        'le statut est échappé par le formatter',
        window.bcStatutFormatter('X', {
            statut: 'X', statut_couleur: 'secondary', statut_label: '<img src=x onerror=alert(1)>',
        }).includes('&lt;img')
    );

    console.log('\n── Pied de tableau sur un filtre (SPEC_UX A-02) ──');
    const filtree = lireJson('liste-valide.json');
    (etat.evenements['load-success.bs.table'] || []).forEach((g) => g({}, filtree));
    verifier(
        'le total suit le filtre appliqué',
        doc.getElementById('pied-total').textContent.includes(
            Math.round(filtree.montant_ttc_affiche).toLocaleString('fr-FR')
        ),
        doc.getElementById('pied-total').textContent
    );

    console.log('\n── État vide EV-02 ──');
    const vide = lireJson('liste-vide.json');
    (etat.evenements['load-success.bs.table'] || []).forEach((g) => g({}, vide));
    verifier('l\'état vide apparaît quand le filtre ne ramène rien',
        !doc.getElementById('etat-vide').classList.contains('d-none'));
    verifier('l\'état vide propose de réinitialiser les filtres',
        doc.getElementById('btn-reinitialiser') !== null);

    console.log(`\n${echecs === 0 ? '✅ Tous les contrôles passent' : `❌ ${echecs} contrôle(s) en échec`}`);
    process.exit(echecs === 0 ? 0 : 1);
})();
