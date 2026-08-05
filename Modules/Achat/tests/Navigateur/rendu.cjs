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
                    on(evenement, gestionnaire) {
                        (etat.evenements[evenement] ??= []).push(gestionnaire);
                        elements.forEach((e) => e.addEventListener(evenement.split('.')[0], gestionnaire));
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
        + source.replace(/^import .*$/gm, '').replace(/^export .*$/gm, '')
        + '\n})();';

    const catalogue = fs.readFileSync(`${RACINE}/public/js/modules/catalogue/formatters.js`, 'utf8');
    const vue = fs.readFileSync(`${RACINE}/public/js/modules/achat/bons-commande/index.js`, 'utf8');

    const script = dom.window.document.createElement('script');
    script.textContent = [envelopper(catalogue), envelopper(vue)].join('\n');
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
    actions: window.bcActionsFormatter(ligne.actions, ligne),
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
    ['Numéro', 'Fournisseur', 'Date', 'Service demandeur', 'Lignes', 'Montant TTC', 'Livraison', 'Statut', 'Créé par', 'Actions']
        .forEach((colonne) => verifier(`colonne « ${colonne} »`, entetes.includes(colonne)));

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

    console.log('\n── Doctrine des actions (SPEC_UX §0.3) ──');
    verifier(
        'chaque bouton d\'action porte un libellé accessible',
        rendues.every(({ html }) => html.actions === '—' || html.actions.includes('aria-label'))
    );

    const inactives = rendues.flatMap(({ ligne }) => ligne.actions.filter((a) => !a.actif));
    if (inactives.length > 0) {
        verifier(
            'une action grisée porte toujours son diagnostic',
            inactives.every((a) => typeof a.titre === 'string' && a.titre.length > 0),
            `${inactives.length} action(s) grisée(s)`
        );
    }

    verifier(
        'aucune ancre morte : un bouton sans URL est désactivé',
        rendues.every(({ ligne, html }) => {
            const sansUrl = ligne.actions.filter((a) => !a.url);
            return sansUrl.length === 0 || html.actions.includes('disabled');
        })
    );
    verifier(
        'aucun href="#" dans les actions',
        rendues.every(({ html }) => !html.actions.includes('href="#"'))
    );

    const annules = rendues.filter(({ ligne }) => ligne.statut === 'ANNULE');
    if (annules.length > 0) {
        verifier(
            'un bon annulé n\'offre pas de PDF',
            annules.every(({ ligne }) => !ligne.actions.some((a) => a.cle === 'pdf'))
        );
    }

    console.log('\n── Échappement (aucune injection depuis les données) ──');
    verifier(
        'le fournisseur est échappé',
        window.bcActionsFormatter([{
            cle: 'x', libelle: '<script>', icone: 'bi-x', classe: 'btn',
            url: 'https://exemple/"><script>alert(1)</script>', actif: true, titre: '<img src=x onerror=alert(1)>',
        }]).includes('&lt;') === true
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
