// Vérification navigateur de la saisie des ÉCARTS BL (BR-04).
//
// Le formulaire du bon d'entrée est rendu par Blade, puis son JavaScript réel
// est exécuté dans un DOM (jsdom). On contrôle ce que le magasinier voit et
// déclenche au comptoir : le tableau n'apparaît que sous son motif, une ligne
// s'ajoute avec le compté pré-rempli, la charge envoyée au serveur est
// correcte, et changer de motif n'envoie plus d'écarts.
//
// Ces contrôles complètent EcartsBlTest, qui s'arrête à la réponse HTTP.

const fs = require('fs');
const path = require('path');
const { JSDOM, VirtualConsole } = require(process.env.JSDOM_PATH || 'jsdom');

const DOSSIER = process.env.STOCK_ARTEFACTS || require('os').tmpdir() + '/verif_stock';
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

const FICHIER = `${DOSSIER}/entree-brouillon.html`;

if (!fs.existsSync(FICHIER)) {
    console.log(`  ✗ artefact entree-brouillon.html absent de ${DOSSIER}`);
    process.exit(1);
}

console.log('── Saisie des écarts BL (BR-04) ──');

const html = fs.readFileSync(FICHIER, 'utf8');

// ── Rendu serveur ──────────────────────────────────────────────────────────

const doc = new JSDOM(html).window.document;

verifier('le bloc des écarts existe',
    doc.getElementById('bloc-ecarts-bl') !== null);

verifier('il est MASQUÉ tant que le motif « Écart BL » n\'est pas choisi',
    doc.getElementById('bloc-ecarts-bl')?.classList.contains('d-none'));

verifier('le caractère facultatif est écrit à l\'écran',
    doc.getElementById('bloc-ecarts-bl')?.textContent.includes('facultatif'));

verifier('l\'écran dit que l\'écart ne modifie aucun compteur',
    doc.getElementById('ecarts-vide')?.textContent.includes('ne modifie aucun compteur'));

verifier('la pilule « Écart BL — réclamation » est proposée',
    Array.from(doc.querySelectorAll('.pilule-motif'))
        .some((b) => b.getAttribute('data-motif') === 'ecart_bl'));

// ── Exécution du JS réel ───────────────────────────────────────────────────

const erreurs = [];

/*
 * La page complète charge tout le socle du gabarit (Ziggy, plugins, scripts
 * de mise en page). Les exécuter ici n'apprendrait rien sur BR-04 et ferait
 * dérailler jsdom. On isole donc le FORMULAIRE : son balisage réel, les
 * données que le serveur lui passe, puis son propre JavaScript.
 */
const corps = html.slice(html.indexOf('<form'), html.lastIndexOf('</form>') + 7);

const donnees = (html.match(/window\.(ENTREE|LIGNES_INITIALES|MODE_COMMANDE|ACHAT_DISPONIBLE|MOTIFS_ECART|ECARTS_INITIAUX)\s*=\s*[^\n]*/g) || []).join('\n');

const console_virtuelle = new VirtualConsole();
console_virtuelle.on('jsdomError', (e) => erreurs.push(e.message + ' :: ' + (e.detail?.message ?? '')));

const dom = new JSDOM(`<!DOCTYPE html><html><body>${corps}</body></html>`, {
    virtualConsole: console_virtuelle,
    runScripts: 'dangerously',
    url: 'https://parc_info.local/stock/entrees/creer',
    beforeParse(window) {
        window.onerror = (m) => erreurs.push(String(m));
        window.route = (nom, id) => `/${nom}/${id ?? ''}`;
        window.Swal = { fire: () => ({ then: () => {} }) };
        window.bootstrap = {
            Popover: function () { this.show = () => {}; this.hide = () => {}; },
            Modal: function () { this.show = () => {}; this.hide = () => {}; },
            Tooltip: function () {},
        };
        window.bootstrap.Popover.getOrCreateInstance = () => ({ show() {}, hide() {} });
        window.bootstrap.Popover.getInstance = () => null;
        window.bootstrap.Modal.getOrCreateInstance = () => ({ show() {}, hide() {} });
    },
});

const { window } = dom;

// jQuery RÉEL, celui que l'application sert elle-même : le formulaire s'en
// sert intensivement (délégation, .map, .val, .trigger) et un double ne
// prouverait rien sur le comportement au comptoir.
const jquery = fs.readFileSync(`${RACINE}/public/plugins/jquery/jquery-3.7.1.min.js`, 'utf8');

const injecter = (source) => {
    const script = window.document.createElement('script');
    script.textContent = source;
    window.document.body.appendChild(script);
};

injecter(jquery);
window.$.fn.select2 = function () { return this; };

// Les données que le serveur passe à la vue (motifs, lignes, mode commande).
injecter(donnees);

// Les modules ES du formulaire : jsdom ne résout pas les imports, on les
// aplatit en retirant import/export.
const nettoyer = (source) => source
    .replace(/^import .*$/gm, '')
    .replace(/^export (class|const|function)/gm, '$1')
    .replace(/^export .*$/gm, '');

const lire = (chemin) => nettoyer(fs.readFileSync(`${RACINE}/public/js/modules/stock/${chemin}`, 'utf8'));

/*
 * Chaque source dans SA PROPRE portée, comme le ferait un module ES : ces
 * fichiers déclarent tous un utilitaire `echapper` local, et les concaténer
 * à plat produirait un « already declared » qui n'existe pas dans le
 * navigateur. Ce que le module doit exposer passe par `window`.
 */
const isoler = (source) => `(function(){\n${source}\n})();`;

// Les formateurs partagés du Catalogue (natureBadgeFormatter…), que la page
// charge normalement avant le formulaire.
injecter(isoler(nettoyer(fs.readFileSync(`${RACINE}/public/js/modules/catalogue/formatters.js`, 'utf8'))));

injecter(isoler(lire('shared/formatters.js')));

// Le formulaire et ses dépendances directes partagent une portée : il les
// consomme par leur nom, comme après résolution des imports.
injecter(isoler([
    lire('shared/erreurs-formulaire.js'),
    'const SelecteurArticle = function () { this.ouvrir = () => {}; };',
    'const SelecteurUnites = function () { this.ouvrir = () => {}; };',
    'const SelecteurCommande = function () { this.ouvrir = () => {}; };',
    'const validerEntree = () => {};',
    lire('entrees/form.js'),
].join('\n')));

const $ = window.$;

/*
 * jQuery diffère $(function(){}) d'un tick : sans cette attente, on
 * contrôlerait un formulaire dont les gestionnaires ne sont pas encore
 * branchés (et tout passerait pour cassé sans l'être).
 */
setTimeout(() => {
    verifier('le JS du formulaire s\'exécute sans erreur', erreurs.length === 0, erreurs.join(' | '));

    // Le motif ouvre le tableau
    $('.pilule-motif[data-motif="ecart_bl"]').trigger('click');

    verifier('choisir le motif OUVRE le tableau des écarts',
        !window.document.getElementById('bloc-ecarts-bl').classList.contains('d-none'));

    /*
     * L'artefact porte une ligne reçue : c'est elle qu'un écart peut viser.
     * On compte les lignes d'ARTICLE (celles qui portent une quantité), les
     * rattachements d'unités n'étant pas concernés par un écart de comptage.
     */
    const lignesPresentes = $('#table-lignes tbody .input-quantite').length;

    verifier('le bon porte bien une ligne reçue', lignesPresentes > 0,
        `${lignesPresentes} ligne(s) d'article`);

    $('#btn-ajouter-ecart').trigger('click');

    verifier('« Ajouter une ligne » crée une ligne d\'écart',
        $('#table-ecarts tbody tr').length === 1);

    verifier('l\'article proposé vient des lignes du bon',
        $('#table-ecarts tbody .ecart-article option').length === lignesPresentes);

    verifier('le compté est pré-rempli avec la quantité reçue',
        $('#table-ecarts tbody .ecart-comptee').val() === '8',
        `valeur : ${$('#table-ecarts tbody .ecart-comptee').val()}`);

    verifier('les trois motifs du serveur sont proposés',
        $('#table-ecarts tbody .ecart-motif option').length === 3);

    // La charge réellement envoyée au serveur (le formulaire enregistre sur
    // « submit », pas sur le clic : on déclenche donc l'événement réel).
    $('#table-ecarts tbody .ecart-annoncee').val('10');
    $('#table-ecarts tbody .ecart-comptee').val('8');
    $('#table-ecarts tbody .ecart-motif').val('manquant');

    let chargeCapturee = null;
    $.ajax = (options) => {
        chargeCapturee = JSON.parse(options.data);
        return { done() { return this; }, fail() { return this; }, always() { return this; } };
    };

    $('#entree-form').trigger('submit');

    verifier('la charge porte l\'écart saisi',
        chargeCapturee?.ecarts_bl?.length === 1
            && chargeCapturee.ecarts_bl[0].quantite_annoncee_bl === 10
            && chargeCapturee.ecarts_bl[0].quantite_comptee === 8
            && chargeCapturee.ecarts_bl[0].motif === 'manquant',
        JSON.stringify(chargeCapturee?.ecarts_bl));

    // Changer de motif : le serveur refuserait des écarts sous « conforme »,
    // l'écran ne doit donc plus les envoyer.
    $('.pilule-motif[data-motif="livraison_conforme"]').trigger('click');
    $('#entree-form').trigger('submit');

    verifier('changer de motif n\'envoie plus d\'écarts',
        Array.isArray(chargeCapturee?.ecarts_bl) && chargeCapturee.ecarts_bl.length === 0);

    verifier('le tableau se referme avec le motif',
        window.document.getElementById('bloc-ecarts-bl').classList.contains('d-none'));

    // Retirer la ligne
    $('.pilule-motif[data-motif="ecart_bl"]').trigger('click');
    $('#table-ecarts .btn-retirer-ecart').first().trigger('click');

    verifier('retirer une ligne la supprime et réaffiche l\'explication',
        $('#table-ecarts tbody tr').length === 0
            && !window.document.getElementById('ecarts-vide').classList.contains('d-none'));

    console.log(
        echecs === 0
            ? '\nÉcarts BL : tous les contrôles passent'
            : `\n${echecs} contrôle(s) des écarts BL en échec`
    );

    process.exit(echecs === 0 ? 0 : 1);
}, 50);
