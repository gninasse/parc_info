// Exécute le vrai JavaScript des pages Stock dans un DOM, alimenté par les
// vraies charges JSON servies par l'application, puis vérifie ce que
// l'utilisateur voit réellement : entêtes, cellules formatées, totaux,
// cartes de résumé, pagination, classements, KPI.
const fs = require('fs');
const { JSDOM, VirtualConsole } = require(process.env.JSDOM_PATH || 'jsdom');

const DOSSIER = process.env.STOCK_ARTEFACTS || require('os').tmpdir() + '/verif_stock';
let echecs = 0;
const verifier = (nom, condition, detail = '') => {
    if (condition) {
        console.log(`  ✓ ${nom}${detail ? ' — ' + detail : ''}`);
    } else {
        echecs++;
        console.log(`  ✗ ${nom}${detail ? ' — ' + detail : ''}`);
    }
};

// Chart.js n'est pas chargé dans jsdom (pas de canvas) : on l'instrumente pour
// vérifier que chaque graphique reçoit bien un type et des données.
const fabriquerChart = (registre) => {
    return class Chart {
        constructor(element, config) {
            registre.push({ id: element ? element.id : null, config });
            this.destroy = () => {};
        }
    };
};

async function monter(fichierHtml, fichierJson, urlAttendue) {
    const html = fs.readFileSync(`${DOSSIER}/${fichierHtml}`, 'utf8');
    const charge = JSON.parse(fs.readFileSync(`${DOSSIER}/${fichierJson}`, 'utf8'));

    const virtualConsole = new VirtualConsole();
    const erreursJs = [];
    virtualConsole.on('jsdomError', (e) => erreursJs.push(e.message));
    virtualConsole.on('error', (e) => erreursJs.push(String(e)));

    const dom = new JSDOM(html, {
        runScripts: 'dangerously',
        pretendToBeVisual: true,
        url: 'https://parc_info.local/',
        virtualConsole,
        beforeParse(window) {
            // Le layout commun charge jQuery, Ziggy et OverlayScrollbars via
            // <script src>, que jsdom ne récupère pas. On les stube pour que
            // toute erreur restante provienne bien des scripts de mes pages.
            const jq = () => ({ attr: () => 'jeton-test', ready: (f) => f() });
            jq.ajaxSetup = () => {};
            window.$ = window.jQuery = jq;
            window.Ziggy = { routes: {}, url: 'https://parc_info.local', port: null, defaults: {} };
            window.OverlayScrollbarsGlobal = { OverlayScrollbars: () => {} };
            window.route = () => '';

            const graphiques = [];
            window.__graphiques = graphiques;
            window.Chart = fabriquerChart(graphiques);
            window.__urlsAppelees = [];
            window.fetch = (url) => {
                window.__urlsAppelees.push(String(url));
                return Promise.resolve({ json: () => Promise.resolve(charge) });
            };
        },
    });

    // Laisse les promesses de fetch se résoudre
    await new Promise((r) => setTimeout(r, 60));
    return { dom, window: dom.window, doc: dom.window.document, charge, erreursJs };
}

(async () => {
    // ── 1. Écran d'un état : journal des mouvements (colonnes date + montants) ──
    console.log('\nÉcran d\'état — journal des mouvements');
    {
        const { window, doc, charge, erreursJs } = await monter('show-mouvements.html', 'data-mouvements.json');

        verifier('aucune erreur JavaScript', erreursJs.length === 0, erreursJs.join(' | '));
        verifier('appel de données émis vers la bonne route',
            window.__urlsAppelees.some((u) => u.includes('/stock/rapports/mouvements/data')),
            window.__urlsAppelees[0]);

        const entetes = [...doc.querySelectorAll('#rapport-table thead th')].map((t) => t.textContent.trim());
        verifier('entêtes rendues', entetes.length === charge.colonnes.length, entetes.join(' | '));

        const lignes = doc.querySelectorAll('#rapport-table tbody tr');
        verifier('lignes rendues', lignes.length > 0, `${lignes.length} ligne(s) pour ${charge.total} au total`);

        const cellules = [...lignes[0].querySelectorAll('td')].map((c) => c.textContent.trim());
        verifier('aucune cellule "Invalid Date" ou "NaN"',
            !cellules.some((c) => /Invalid Date|NaN/.test(c)), cellules.slice(0, 4).join(' | '));

        const iDate = charge.colonnes.findIndex((c) => c.type === 'datetime');
        verifier('colonne datetime formatée en français',
            /^\d{2}\/\d{2}\/\d{4}/.test(cellules[iDate]), cellules[iDate]);

        const iMontant = charge.colonnes.findIndex((c) => c.type === 'montant');
        verifier('colonne montant formatée en séparateurs de milliers',
            /^[\d\s\u00a0\u202f]+$|^—$/u.test(cellules[iMontant]), `« ${cellules[iMontant]} »`);

        const pied = [...doc.querySelectorAll('#rapport-table tfoot td')].map((c) => c.textContent.trim());
        verifier('ligne de totaux affichée avec le libellé TOTAL',
            pied.includes('TOTAL'), pied.filter(Boolean).join(' | '));

        const resume = doc.querySelectorAll('#zone-resume .carte-resume');
        verifier('cartes de résumé rendues', resume.length === charge.resume.length,
            `${resume.length} carte(s)`);

        const compteur = doc.getElementById('compteur-lignes').textContent;
        verifier('compteur de lignes renseigné', /\d/.test(compteur), compteur);

        const filtres = doc.querySelectorAll('#filtres-actifs .badge');
        verifier('filtres actifs affichés', filtres.length > 0,
            [...filtres].map((f) => f.textContent.trim()).join(' · '));

        // Pagination : 24 mouvements avec 25 par page => pas de pagination
        const pagination = doc.getElementById('pagination').children.length;
        verifier('pagination cohérente avec le volume',
            charge.total <= 25 ? pagination === 0 : pagination > 0, `${pagination} bouton(s)`);
    }

    // ── 2. Un état sans données ne doit pas casser ni afficher de faux totaux ──
    console.log('\nÉcran d\'état — valorisation et rotation');
    for (const code of ['valorisation', 'rotation']) {
        const { doc, charge, erreursJs } = await monter(`show-${code}.html`, `data-${code}.json`);
        const lignes = doc.querySelectorAll('#rapport-table tbody tr');
        const texte = doc.querySelector('#rapport-table tbody').textContent;
        verifier(`${code} : rendu sans erreur`, erreursJs.length === 0, erreursJs.join(' | '));
        verifier(`${code} : ${charge.total} ligne(s) attendue(s)`,
            charge.total === 0 ? texte.includes('Aucune donnée') : lignes.length === Math.min(charge.total, 25),
            `${lignes.length} rendue(s)`);
        verifier(`${code} : pas de NaN dans le tableau`, !/NaN|Invalid Date/.test(texte));
    }

    // ── 3. Le tableau de bord statistique ──
    console.log('\nTableau de bord statistique');
    {
        const { window, doc, charge, erreursJs } = await monter('statistiques.html', 'statistiques.json');
        const d = charge.data;

        verifier('aucune erreur JavaScript', erreursJs.length === 0, erreursJs.join(' | '));
        verifier('le contenu est démasqué après chargement',
            !doc.getElementById('stats-contenu').classList.contains('d-none'));
        verifier('le loader est masqué',
            doc.getElementById('stats-chargement').classList.contains('d-none'));

        const kpiValeur = doc.getElementById('kpi-valeur').textContent;
        verifier('KPI valeur du stock renseigné et formaté',
            kpiValeur.includes('FCFA') && /\d/.test(kpiValeur), kpiValeur);

        const kpis = ['kpi-references', 'kpi-equipements', 'kpi-dispo', 'kpi-conso', 'kpi-rotation', 'kpi-couverture', 'kpi-alertes'];
        const vides = kpis.filter((id) => doc.getElementById(id).textContent.trim() === '—' && id !== 'kpi-rotation' && id !== 'kpi-couverture');
        verifier('tous les KPI sont hydratés', vides.length === 0, vides.join(', ') || 'aucun vide');
        verifier('aucun KPI en NaN',
            !kpis.some((id) => /NaN|undefined/.test(doc.getElementById(id).textContent)));

        const graphiques = window.__graphiques.map((g) => g.id);
        const attendus = ['graphique-flux', 'graphique-sante', 'graphique-magasins',
            'graphique-natures', 'graphique-categories', 'graphique-motifs'];
        const manquants = attendus.filter((id) => !graphiques.includes(id));
        verifier('les 6 graphiques sont instanciés', manquants.length === 0,
            manquants.length ? 'manquants: ' + manquants.join(', ') : graphiques.join(', '));

        const flux = window.__graphiques.find((g) => g.id === 'graphique-flux');
        verifier('le graphique de flux couvre toute la fenêtre',
            flux.config.data.labels.length === d.serie_mensuelle.length,
            `${flux.config.data.labels.length} mois : ${flux.config.data.labels.join(', ')}`);
        verifier('le graphique de flux a 3 séries (entrées, sorties, solde)',
            flux.config.data.datasets.length === 3,
            flux.config.data.datasets.map((s) => s.label).join(' / '));

        const magasins = window.__graphiques.find((g) => g.id === 'graphique-magasins');
        verifier('valeur par magasin alimentée depuis les données',
            magasins.config.data.datasets[0].data.length === d.valeur_par_magasin.length
            && magasins.config.data.datasets[0].data.every((v) => typeof v === 'number'),
            magasins.config.data.labels.join(', '));

        const articles = doc.querySelectorAll('#liste-articles li');
        verifier('classement des articles rendu',
            articles.length === Math.max(1, d.top_articles_sortis.length),
            `${articles.length} entrée(s)`);
        verifier('les barres de proportion ont une largeur',
            [...doc.querySelectorAll('#liste-articles .barre-part span')]
                .every((s) => /width: \d+%/.test(s.getAttribute('style'))));

        const qualite = doc.querySelectorAll('#liste-qualite li');
        verifier('bloc qualité de la saisie rendu (6 indicateurs)', qualite.length === 6,
            `${qualite.length} indicateur(s)`);
        verifier('qualité de la saisie sans NaN',
            !/NaN|undefined/.test(doc.getElementById('liste-qualite').textContent));

        const periode = doc.getElementById('periode-analysee').textContent;
        verifier('période analysée affichée',
            /\d{2}\/\d{2}\/\d{4}/.test(periode), periode.trim());

        // Bascule valeur/quantité : doit reconstruire le graphique de flux
        const avant = window.__graphiques.length;
        const radio = doc.getElementById('serie-quantite');
        radio.checked = true;
        radio.dispatchEvent(new window.Event('change', { bubbles: true }));
        await new Promise((r) => setTimeout(r, 20));
        verifier('la bascule valeur/quantité redessine le graphique',
            window.__graphiques.length === avant + 1,
            `${window.__graphiques.length - avant} redessin(s)`);
    }

    console.log(`\n${echecs === 0 ? 'TOUT EST VERT' : echecs + ' VÉRIFICATION(S) EN ÉCHEC'}`);
    process.exit(echecs === 0 ? 0 : 1);
})();
