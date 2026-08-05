// Vérifie les comportements interactifs qui n'apparaissent pas au premier
// rendu : pagination réelle, application des filtres, réinitialisation,
// changement de taille de page, liens d'export, et état vide.
const fs = require('fs');
const { JSDOM, VirtualConsole } = require(process.env.JSDOM_PATH || 'jsdom');

const DOSSIER = process.env.STOCK_ARTEFACTS || require('os').tmpdir() + '/verif_stock';
let echecs = 0;
const verifier = (nom, condition, detail = '') => {
    if (condition) console.log(`  ✓ ${nom}${detail ? ' — ' + detail : ''}`);
    else { echecs++; console.log(`  ✗ ${nom}${detail ? ' — ' + detail : ''}`); }
};


const html = fs.readFileSync(`${DOSSIER}/show-mouvements.html`, 'utf8');
const base = JSON.parse(fs.readFileSync(`${DOSSIER}/data-mouvements.json`, 'utf8'));

// Sert une charge dont le total est gonflé pour forcer la pagination,
// en enregistrant chaque requête pour vérifier offset/limit et filtres.
function monter(fabriquerCharge) {
    const virtualConsole = new VirtualConsole();
    const erreursJs = [];
    virtualConsole.on('jsdomError', (e) => erreursJs.push(e.message));

    const requetes = [];
    const dom = new JSDOM(html, {
        runScripts: 'dangerously',
        pretendToBeVisual: true,
        url: 'https://parc_info.local/',
        virtualConsole,
        beforeParse(window) {
            const jq = () => ({ attr: () => 'jeton', ready: (f) => f() });
            jq.ajaxSetup = () => {};
            window.$ = window.jQuery = jq;
            window.Ziggy = { routes: {}, url: 'https://parc_info.local', port: null, defaults: {} };
            window.OverlayScrollbarsGlobal = { OverlayScrollbars: () => {} };
            window.Chart = class { constructor() { this.destroy = () => {}; } };
            window.__navigations = [];
            // window.location est non redéfinissable dans jsdom : on capte la
            // navigation via son setter href, ce que fait le code d'export.
            const vraieLocation = window.location;
            try {
                Object.defineProperty(vraieLocation, 'href', {
                    configurable: true,
                    set: (v) => window.__navigations.push(String(v)),
                    get: () => 'https://parc_info.local/',
                });
            } catch (e) { /* ignoré : vérifié autrement */ }
            window.fetch = (url) => {
                requetes.push(String(url));
                return Promise.resolve({ json: () => Promise.resolve(fabriquerCharge(String(url))) });
            };
        },
    });

    return { dom, window: dom.window, doc: dom.window.document, requetes, erreursJs };
}

const attendre = (ms = 40) => new Promise((r) => setTimeout(r, ms));

(async () => {
    // ── Pagination réelle : 240 lignes, 25 par page ──
    console.log('\nPagination');
    {
        const gonfle = (url) => {
            const offset = parseInt(new URL(url).searchParams.get('offset') || '0', 10);
            return { ...base, total: 240, rows: base.rows.slice(0, 25).map((r, i) => ({ ...r, document: `DOC-${offset + i}` })) };
        };
        const { window, doc, requetes } = monter(gonfle);
        await attendre();

        const boutons = doc.querySelectorAll('#pagination li');
        verifier('la pagination apparaît au-delà d\'une page', boutons.length > 0, `${boutons.length} bouton(s)`);
        verifier('le compteur reflète le total serveur',
            doc.getElementById('compteur-lignes').textContent.replace(/\s/g, '').includes('240'),
            doc.getElementById('compteur-lignes').textContent);
        verifier('le bouton « précédent » est désactivé en page 1',
            boutons[0].classList.contains('disabled'));

        // Aller en page 3
        const lien3 = [...doc.querySelectorAll('#pagination a')].find((a) => a.dataset.page === '3');
        lien3.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
        await attendre();

        const derniere = requetes[requetes.length - 1];
        verifier('le changement de page demande le bon offset',
            derniere.includes('offset=50') && derniere.includes('limit=25'), derniere.split('?')[1]);
        verifier('la page active suit la navigation',
            doc.querySelector('#pagination li.active a')?.dataset.page === '3');
        verifier('les lignes affichées proviennent bien de la page demandée',
            doc.querySelector('#rapport-table tbody tr td:nth-child(3)').textContent.trim() === 'DOC-50',
            doc.querySelector('#rapport-table tbody tr td:nth-child(3)').textContent.trim());
    }

    // ── Filtres, taille de page, export ──
    console.log('\nContrôles de l\'écran');
    {
        const { window, doc, requetes } = monter(() => base);
        await attendre();
        const avant = requetes.length;

        doc.getElementById('filtre-magasin').value = '2';
        doc.getElementById('filtre-type').value = 'SORTIE';
        doc.getElementById('filtre-date-debut').value = '2026-01-01';
        doc.getElementById('btn-appliquer').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
        await attendre();

        const requete = requetes[requetes.length - 1];
        verifier('« Appliquer » relance une requête', requetes.length > avant);
        verifier('les filtres saisis sont transmis au serveur',
            requete.includes('magasin_id=2') && requete.includes('type=SORTIE') && requete.includes('date_debut=2026-01-01'),
            requete.split('?')[1]);

        doc.getElementById('btn-reinitialiser').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
        await attendre();
        const apresReset = requetes[requetes.length - 1];
        verifier('« Réinitialiser » vide les filtres',
            !apresReset.includes('magasin_id') && !apresReset.includes('type=SORTIE'),
            apresReset.split('?')[1]);

        const taille = doc.getElementById('taille-page');
        taille.value = '100';
        taille.dispatchEvent(new window.Event('change', { bubbles: true }));
        await attendre();
        verifier('le changement de taille de page est transmis',
            requetes[requetes.length - 1].includes('limit=100'),
            requetes[requetes.length - 1].split('?')[1]);

        // Export : les liens sont de vrais href, synchronisés sur les filtres
        const magasin = doc.getElementById('filtre-magasin');
        const optionReelle = magasin.querySelector('option[value]:not([value=""])').value;
        magasin.value = optionReelle;
        magasin.dispatchEvent(new window.Event('change', { bubbles: true }));
        await attendre();

        const lienPdf = [...doc.querySelectorAll('.export-lien')].find((a) => a.dataset.format === 'pdf');
        verifier('le lien d\'export PDF reprend filtres et format',
            lienPdf.href.includes('/export?') && lienPdf.href.includes('format=pdf')
            && lienPdf.href.includes('magasin_id=' + optionReelle), lienPdf.getAttribute('href'));

        const lienCsv = [...doc.querySelectorAll('.export-lien')].find((a) => a.dataset.format === 'csv');
        verifier('chaque format a son propre lien',
            lienCsv.href.includes('format=csv') && lienCsv.href !== lienPdf.href,
            lienCsv.getAttribute('href').split('?')[1]);

        verifier('les liens d\'export sont navigables (vrai href, pas de #)',
            !lienPdf.getAttribute('href').startsWith('#'));
    }

    // ── État vide ──
    console.log('\nÉtat sans données');
    {
        const { doc } = monter(() => ({ ...base, total: 0, rows: [], resume: [], totaux: {} }));
        await attendre();
        const corps = doc.querySelector('#rapport-table tbody').textContent;
        verifier('message d\'état vide affiché', corps.includes('Aucune donnée'), corps.trim().slice(0, 60));
        verifier('aucune ligne de totaux trompeuse',
            doc.querySelector('#rapport-table tfoot').children.length === 0);
        verifier('compteur à zéro', doc.getElementById('compteur-lignes').textContent.includes('0'));
    }

    console.log(`\n${echecs === 0 ? 'TOUT EST VERT' : echecs + ' VÉRIFICATION(S) EN ÉCHEC'}`);
    process.exit(echecs === 0 ? 0 : 1);
})();
