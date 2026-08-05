// Vérification navigateur du circuit BROUILLON ⇄ SOUMIS.
//
// Trois écrans y sont contrôlés sur le HTML réellement rendu :
//   - le récapitulatif de l'étape ②, dans ses DEUX états (prêt / bloqué) ;
//   - le formulaire d'un brouillon renvoyé, pour l'encart jaune (UX2-07) ;
//   - la liste vue par un porteur du visa, pour les actions de transition.
//
// Le point sensible vérifié ici : une transition de statut doit être un
// BOUTON de commande, jamais un lien. Rendue en <a href>, elle partirait en
// GET — 405 au mieux, transition déclenchée par un préchargement au pire.

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

const existe = (fichier) => fs.existsSync(`${DOSSIER}/${fichier}`);
const lire = (fichier) => fs.readFileSync(`${DOSSIER}/${fichier}`, 'utf8');

/** Monte une page rendue, sans exécuter de script (contrôle du HTML seul). */
const monter = (fichier) => {
    const virtualConsole = new VirtualConsole();
    return new JSDOM(lire(fichier), { virtualConsole }).window.document;
};

(async () => {
    console.log('\n── Étape ② : récapitulatif d\'un bon PRÊT à partir ──');
    const pret = monter('recapitulatif-pret.html');

    verifier('le stepper marque l\'étape ② comme courante',
        (pret.querySelector('.achat-stepper [aria-current="step"]')?.textContent ?? '').includes('Récapitulatif'),
        pret.querySelector('.achat-stepper [aria-current="step"]')?.textContent.trim());

    verifier('le composant partagé « Récapitulatif de BC » est présent',
        pret.querySelector('#recap-lignes') !== null);
    verifier('les lignes y sont en LECTURE SEULE (aucun champ de saisie)',
        pret.querySelectorAll('#recap-lignes input, #recap-lignes select').length === 0);

    ['recap-total-ht', 'recap-total-tva', 'recap-total-ttc']
        .forEach((id) => verifier(`total #${id}`, pret.getElementById(id) !== null));
    verifier('le total est qualifié TTC (SPEC_UX §0.4)',
        (pret.getElementById('recap-total-ttc')?.textContent ?? '').includes('FCFA TTC'));
    verifier('le total HT est qualifié HT',
        (pret.getElementById('recap-total-ht')?.textContent ?? '').includes('FCFA HT'));

    const boutonPret = pret.getElementById('btn-soumettre');
    verifier('le bouton « Soumettre au visa » est présent', boutonPret !== null);
    verifier('il est OUVERT quand rien ne bloque', boutonPret && !boutonPret.hasAttribute('disabled'));
    verifier('« Retour aux lignes » ramène à l\'étape ①',
        (pret.querySelector('#barre-recapitulatif a')?.getAttribute('href') ?? '').includes('/edit'));
    verifier('la barre du récapitulatif est collante',
        pret.querySelector('.barre-collante') !== null);
    verifier('les chiffres de la soumission accompagnent le bouton (SW-01)',
        boutonPret && boutonPret.getAttribute('data-montant-ttc')
        && boutonPret.getAttribute('data-nb-lignes'));

    console.log('\n── Étape ② : récapitulatif d\'un bon BLOQUÉ ──');
    if (existe('recapitulatif-bloque.html')) {
        const bloque = monter('recapitulatif-bloque.html');
        const boutonBloque = bloque.getElementById('btn-soumettre');

        verifier('le bouton est grisé quand un blocage subsiste',
            boutonBloque && boutonBloque.hasAttribute('disabled'));
        verifier('il porte son DIAGNOSTIC en infobulle (SPEC_UX §0.3)',
            (boutonBloque?.getAttribute('title') ?? '').includes('ligne'),
            boutonBloque?.getAttribute('title'));
        verifier('l\'encart rouge explique le blocage',
            bloque.querySelector('.alert-danger') !== null
            && bloque.querySelector('.alert-danger').textContent.includes('au moins une ligne'));
    } else {
        verifier('artefact du bon bloqué disponible', false, 'recapitulatif-bloque.html manquant');
    }

    console.log('\n── Encart jaune de réouverture (UX2-07) ──');
    if (existe('form-renvoye.html')) {
        const renvoye = monter('form-renvoye.html');
        const encart = renvoye.getElementById('encart-renvoi');

        verifier('l\'encart de renvoi est affiché', encart !== null);
        verifier('il est jaune (alert-warning)', encart?.classList.contains('alert-warning'));
        verifier('il nomme l\'auteur du renvoi et sa date',
            (encart?.textContent ?? '').includes('Renvoyé par'));
        verifier('il reprend le MOTIF du renvoi',
            (encart?.textContent ?? '').includes('Motif :'),
            (encart?.textContent ?? '').trim().split('\n').filter(Boolean).slice(-1)[0]?.trim());
        verifier('il est refermable',
            encart?.querySelector('[data-bs-dismiss="alert"]') !== null);
    } else {
        verifier('artefact du brouillon renvoyé disponible', false, 'form-renvoye.html manquant');
    }

    console.log('\n── Actions de transition dans la liste (A-02) ──');
    const visa = JSON.parse(lire('liste-visa.json'));
    verifier('des bons soumis sont listés', visa.rows.length > 0, `${visa.total} bon(s)`);

    const actions = visa.rows.flatMap((ligne) => ligne.actions);
    const parCle = (cle) => actions.filter((a) => a.cle === cle);

    verifier('l\'action « valider » est proposée au porteur du visa', parCle('valider').length > 0);
    verifier('l\'action « renvoyer » est proposée au porteur du visa', parCle('renvoyer').length > 0);

    // LE point : une transition doit être un POST, pas un lien.
    ['valider', 'renvoyer', 'reprendre'].forEach((cle) => {
        const trouvees = parCle(cle);
        if (trouvees.length === 0) return;
        verifier(`« ${cle} » est déclarée en POST (jamais un lien GET)`,
            trouvees.every((a) => a.methode === 'POST'),
            trouvees[0].methode);
    });

    const supprimer = parCle('supprimer');
    if (supprimer.length > 0) {
        verifier('« supprimer » est déclarée en DELETE',
            supprimer.every((a) => a.methode === 'DELETE'));
    }

    const voir = parCle('voir');
    if (voir.length > 0) {
        verifier('« voir » reste une simple navigation (GET)',
            voir.every((a) => (a.methode ?? 'GET') === 'GET'));
    }

    console.log('\n── Rendu des actions par le formatter de la liste ──');
    // On exécute le VRAI formatter de la vue sur la charge réelle : c'est le
    // seul moyen de vérifier le HTML que l'utilisateur recevra.
    const source = fs.readFileSync(`${RACINE}/public/js/modules/achat/bons-commande/index.js`, 'utf8');
    const bac = new JSDOM('<!doctype html><html><body></body></html>', {
        runScripts: 'outside-only',
    }).window;

    const echapperHtml = (t) => String(t ?? '').replace(
        /[&<>"]/g,
        (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])
    );

    bac.echapper = echapperHtml;

    const extrait = source.slice(
        source.indexOf('window.bcActionsFormatter'),
        source.indexOf('$(function ()')
    );
    bac.eval(extrait);

    const ligneSoumise = visa.rows[0];
    const html = bac.bcActionsFormatter(ligneSoumise.actions, ligneSoumise);

    verifier('les transitions sont rendues en <button>, pas en <a>',
        !/<a[^>]*bc-commande/.test(html) && /<button[^>]*bc-commande/.test(html));
    verifier('chaque commande transporte sa méthode HTTP',
        (html.match(/data-methode="POST"/g) ?? []).length > 0);
    verifier('aucune ancre morte dans les actions', !html.includes('href="#"'));
    verifier('chaque action porte un libellé accessible', html.includes('aria-label'));

    console.log('\n── Signaux de SW-02 (D-06) ──');
    if (existe('signaux.json')) {
        const signaux = JSON.parse(lire('signaux.json'));

        verifier('les chiffres du bon accompagnent les signaux',
            signaux.bon && typeof signaux.bon.montant_ttc === 'number' && signaux.bon.nb_lignes > 0,
            `${signaux.bon?.nb_lignes} ligne(s), ${signaux.bon?.montant_ttc} TTC`);
        verifier('le cumul fournisseur du mois est fourni',
            signaux.cumul && typeof signaux.cumul.rang_du_mois === 'number',
            `rang ${signaux.cumul?.rang_du_mois}, cumul ${signaux.cumul?.cumul_ttc_mois}`);
        verifier('le rang compte le bon en cours de visa',
            signaux.cumul.rang_du_mois === signaux.cumul.nb_bc_mois + 1);
        verifier('les écarts de prix sont une liste (dépliable ligne à ligne)',
            Array.isArray(signaux.ecarts_prix));
        verifier('le marqueur d\'auto-validation est présent',
            typeof signaux.auto_validation === 'boolean');
    } else {
        verifier('artefact des signaux disponible', false, 'signaux.json manquant');
    }

    console.log(`\n${echecs === 0 ? '✅ Tous les contrôles passent' : `❌ ${echecs} contrôle(s) en échec`}`);
    process.exit(echecs === 0 ? 0 : 1);
})();
