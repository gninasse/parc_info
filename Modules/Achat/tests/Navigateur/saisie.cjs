// Vérification navigateur de l'écran A-03 (saisie d'un brouillon) et de la
// modale M-01 (sélection multiple d'articles).
//
// On charge le HTML réellement rendu par Blade, on exécute le vrai JavaScript
// de la vue dans un DOM, et on vérifie ce que l'utilisateur voit et déclenche :
// stepper, verrouillage du fournisseur, pilules de motif et de TVA, compteurs
// du pied collant, sélection persistante de la modale, garde de sortie.
//
// Le point le plus important vérifié ici : la prévisualisation JS des totaux
// donne le MÊME résultat que le calcul du serveur (IA-1). Si les deux
// divergeaient, l'utilisateur verrait un montant et en engagerait un autre.

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

const lire = (fichier) => fs.readFileSync(`${DOSSIER}/${fichier}`, 'utf8');

/**
 * Monte la page de saisie dans un DOM et y exécute les modules de la vue.
 *
 * jQuery est remplacé par un double couvrant exactement ce dont la vue se
 * sert. Les modules ES sont enveloppés chacun dans sa fonction, comme dans le
 * navigateur, et leurs imports croisés sont recâblés à la main.
 */
async function monter(fichierHtml = 'form-create.html') {
    const html = lire(fichierHtml);
    const virtualConsole = new VirtualConsole();
    const erreursJs = [];
    virtualConsole.on('jsdomError', (e) => erreursJs.push(e.message));
    virtualConsole.on('error', (e) => erreursJs.push(String(e)));

    const etat = { requetes: [], swals: [] };

    const dom = new JSDOM(html, {
        runScripts: 'dangerously',
        pretendToBeVisual: true,
        url: 'https://parc_info.local/achat/bons-commande/create',
        virtualConsole,
        beforeParse(window) {
            const doc = window.document;

            const fabriquer = (selecteur) => {
                let elements;

                if (typeof selecteur === 'string' && selecteur.trim().startsWith('<')) {
                    elements = [doc.createElement('span')];
                } else if (typeof selecteur === 'string') {
                    elements = Array.from(doc.querySelectorAll(selecteur));
                } else if (selecteur instanceof window.Element) {
                    elements = [selecteur];
                } else if (selecteur && selecteur.elements) {
                    elements = selecteur.elements;
                } else {
                    elements = [];
                }

                const objet = {
                    elements,
                    length: elements.length,
                    0: elements[0],
                    text(v) {
                        if (v === undefined) return elements.map((e) => e.textContent).join('');
                        elements.forEach((e) => { e.textContent = v; });
                        return objet;
                    },
                    html(v) {
                        if (v === undefined) return elements.map((e) => e.innerHTML).join('');
                        elements.forEach((e) => { e.innerHTML = v; });
                        return objet;
                    },
                    val(v) {
                        if (v === undefined) return elements[0] ? elements[0].value : undefined;
                        elements.forEach((e) => { e.value = v; });
                        return objet;
                    },
                    attr(nom, v) {
                        if (v === undefined) return elements[0] ? elements[0].getAttribute(nom) : undefined;
                        elements.forEach((e) => e.setAttribute(nom, v));
                        return objet;
                    },
                    data(nom) {
                        if (!elements[0]) return undefined;
                        const brut = elements[0].getAttribute('data-' + nom);
                        return brut === null ? undefined : brut;
                    },
                    prop(nom, v) {
                        if (v === undefined) return elements[0] ? elements[0][nom] : undefined;
                        elements.forEach((e) => { e[nom] = v; });
                        return objet;
                    },
                    is: (s) => (s === ':checked' ? elements.some((e) => e.checked) : false),
                    hasClass: (c) => elements.some((e) => e.classList.contains(c)),
                    addClass(c) { elements.forEach((e) => c.split(' ').forEach((x) => e.classList.add(x))); return objet; },
                    removeClass(c) { elements.forEach((e) => c.split(' ').forEach((x) => e.classList.remove(x))); return objet; },
                    toggleClass(c, cond) { elements.forEach((e) => e.classList.toggle(c, Boolean(cond))); return objet; },
                    toggle(v) { elements.forEach((e) => { e.style.display = v ? '' : 'none'; }); return objet; },
                    empty() { elements.forEach((e) => { e.innerHTML = ''; }); return objet; },
                    find: (s) => fabriquer(elements.flatMap((e) => Array.from(e.querySelectorAll(s)))[0]
                        || { elements: elements.flatMap((e) => Array.from(e.querySelectorAll(s))) }),
                    closest: (s) => fabriquer(elements[0] ? elements[0].closest(s) : null),
                    each(fn) { elements.forEach((e, i) => fn.call(e, i, e)); return objet; },
                    map: (fn) => ({ get: () => elements.map((e, i) => fn(i, e)) }),
                    trigger(evenement) {
                        elements.forEach((e) => e.dispatchEvent(new window.Event(evenement, { bubbles: true })));
                        return objet;
                    },
                    on(evenements, arg2, arg3) {
                        const delegue = typeof arg2 === 'string';
                        const gestionnaire = delegue ? arg3 : arg2;

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
                                        evenement.currentTarget = cible;
                                        gestionnaire.call(cible, evenement);
                                    }
                                });
                            });
                        });

                        return objet;
                    },
                };

                return objet;
            };

            const jq = (arg) => (typeof arg === 'function' ? arg() : fabriquer(arg));
            Object.assign(jq, fabriquer);
            jq.ajaxSetup = () => {};
            jq.getJSON = (url, donnees) => {
                etat.requetes.push({ url: String(url), donnees });
                const promesse = {
                    done: () => promesse,
                    fail: () => promesse,
                    always: () => promesse,
                    then: () => promesse,
                    catch: () => promesse,
                };
                return promesse;
            };
            jq.ajax = (options) => {
                etat.requetes.push(options);
                const promesse = {
                    then: () => promesse,
                    catch: () => promesse,
                    always: () => promesse,
                };
                return promesse;
            };

            window.$ = window.jQuery = jq;
            window.Ziggy = { routes: {}, url: 'https://parc_info.local', port: null, defaults: {} };
            window.OverlayScrollbarsGlobal = { OverlayScrollbars: () => {} };
            window.route = () => '';
            window.bootstrap = {
                Tooltip: function () {},
                Modal: { getInstance: () => ({ hide: () => {} }) },
            };
            window.Swal = { fire: (options) => { etat.swals.push(options); return { then: () => {} }; } };
            window.__etat = etat;
        },
    });

    // Les trois modules ES, enveloppés comme dans le navigateur. Les imports
    // croisés (NATURES, ModaleArticles) sont recâblés explicitement.
    const source = (chemin) => fs.readFileSync(`${RACINE}/public/js/modules/${chemin}`, 'utf8');
    const nettoyer = (s) => s.replace(/^import .*$/gm, '').replace(/^export (class|const|function)/gm, '$1').replace(/^export .*$/gm, '');

    const script = dom.window.document.createElement('script');
    script.textContent = [
        '(function(){' + nettoyer(source('catalogue/formatters.js')) + '\nwindow.__NATURES = NATURES;})();',
        '(function(){const NATURES = window.__NATURES;' + nettoyer(source('achat/bons-commande/modale-articles.js')) + '\nwindow.__ModaleArticles = ModaleArticles;})();',
        '(function(){' + nettoyer(source('achat/bons-commande/modale-fournisseur.js')) + '\nwindow.__ModaleFournisseur = ModaleFournisseur;})();',
        '(function(){const NATURES = window.__NATURES; const ModaleArticles = window.__ModaleArticles; const ModaleFournisseur = window.__ModaleFournisseur;'
            + nettoyer(source('achat/bons-commande/form.js')) + '})();',
    ].join('\n');
    dom.window.document.body.appendChild(script);

    return { dom, window: dom.window, doc: dom.window.document, etat, erreursJs };
}

(async () => {
    const { window, doc, etat, erreursJs } = await monter();

    console.log('\n── Chargement de l\'écran A-03 ──');
    verifier('aucune erreur JavaScript', erreursJs.length === 0, erreursJs.join(' | '));

    console.log('\n── Stepper à 2 étapes (UX2-03) ──');
    const etapes = Array.from(doc.querySelectorAll('.achat-stepper li span:last-child'))
        .map((e) => e.textContent.trim())
        .filter(Boolean);
    verifier('étape ① « Lignes & montants »', etapes.some((e) => e.includes('Lignes')), etapes.join(' / '));
    verifier('étape ② « Récapitulatif & soumission »', etapes.some((e) => e.includes('Récapitulatif')));
    verifier('l\'étape courante est annoncée aux lecteurs d\'écran',
        doc.querySelector('.achat-stepper [aria-current="step"]') !== null);

    console.log('\n── Carte En-tête ──');
    ['a-fournisseur', 'a-fournisseur-libelle', 'btn-choisir-fournisseur', 'a-date', 'a-service', 'a-reference-demande', 'a-observation-type', 'a-observation-texte']
        .forEach((id) => verifier(`champ #${id}`, doc.getElementById(id) !== null));
    verifier('le fournisseur se choisit par MODALE (pattern du projet)',
        doc.getElementById('modal-fournisseur') !== null
        && doc.getElementById('btn-choisir-fournisseur').getAttribute('data-bs-target') === '#modal-fournisseur');
    verifier('la modale fournisseur est en sélection radio',
        doc.querySelector('#mf-table thead') !== null && doc.getElementById('mf-choisir') !== null);
    verifier('la mention « Référentiel du module Catalogue » est présente',
        doc.body.textContent.includes('Référentiel du module Catalogue'));

    const pilulesMotif = Array.from(doc.querySelectorAll('.pilule-motif')).map((b) => b.textContent.trim());
    verifier('les pilules de motif viennent des paramètres', pilulesMotif.length > 0, pilulesMotif.join(' · '));

    console.log('\n── Pilules de motif (interaction réelle) ──');
    const premierMotif = doc.querySelector('.pilule-motif');
    premierMotif.dispatchEvent(new window.Event('click', { bubbles: true }));
    verifier('cliquer une pilule la marque active', premierMotif.classList.contains('active'));
    verifier('le motif est reporté dans le champ caché',
        doc.getElementById('a-observation-type').value === premierMotif.getAttribute('data-motif'),
        doc.getElementById('a-observation-type').value);

    const pilluleAutre = Array.from(doc.querySelectorAll('.pilule-motif'))
        .find((b) => b.getAttribute('data-motif') === 'autre');
    if (pilluleAutre) {
        pilluleAutre.dispatchEvent(new window.Event('click', { bubbles: true }));
        verifier('le motif « Autre » déplie le champ de texte requis',
            !doc.getElementById('a-observation-texte').classList.contains('d-none'));
    }

    console.log('\n── Pied de page collant à compteurs (SPEC_UX §0.4) ──');
    verifier('le pied est collant', doc.querySelector('.barre-collante') !== null);
    verifier('compteur de lignes', doc.getElementById('pied-lignes').textContent.includes('ligne'));
    verifier('compteur d\'unités', doc.getElementById('pied-unites').textContent.includes('unité'));
    verifier('total qualifié HT', doc.getElementById('pied-ht').textContent.includes('FCFA HT'));
    verifier('total qualifié TTC', doc.getElementById('pied-ttc').textContent.includes('FCFA TTC'));
    verifier('la nature d\'estimation des totaux est annoncée (IA-1)',
        doc.getElementById('mention-previsualisation').textContent.includes('estimation'));

    console.log('\n── État initial ──');
    verifier('« Continuer » est fermé sans ligne, avec son diagnostic',
        doc.getElementById('btn-continuer').disabled
        && doc.getElementById('btn-continuer').getAttribute('title').includes('ligne'),
        doc.getElementById('btn-continuer').getAttribute('title'));
    verifier('l\'état vide des lignes est visible',
        !doc.getElementById('lignes-vides').classList.contains('d-none'));
    verifier('le choix du fournisseur est ouvert tant qu\'il n\'y a pas de ligne',
        doc.getElementById('btn-choisir-fournisseur').disabled === false);
    verifier('le filtre interne des lignes est masqué sous 10 lignes',
        doc.getElementById('filtre-lignes').classList.contains('d-none'));

    console.log('\n── Modale M-01 ──');
    verifier('la modale est présente', doc.getElementById('modal-articles') !== null);
    ['ma-recherche', 'ma-nature', 'ma-corps', 'ma-compteur', 'ma-ajouter']
        .forEach((id) => verifier(`élément #${id}`, doc.getElementById(id) !== null));
    verifier('le bouton d\'ajout est fermé sans sélection', doc.getElementById('ma-ajouter').disabled);
    verifier('le lien D7 renvoie au Catalogue dans un nouvel onglet',
        doc.querySelector('#modal-articles a[target="_blank"]') !== null);
    verifier('le lien D7 porte rel="noopener"',
        (doc.querySelector('#modal-articles a[target="_blank"]').getAttribute('rel') || '').includes('noopener'));
    const colonnesModale = Array.from(doc.querySelectorAll('#ma-table thead th')).map((t) => t.textContent.trim());
    ['Code', 'Désignation', 'Unité', 'Prix indicatif', 'TVA']
        .forEach((c) => verifier(`colonne « ${c} » de M-01`, colonnesModale.includes(c)));

    console.log('\n── Cohérence des totaux JS ⇄ serveur (IA-1) ──');
    // La charge du serveur pour un brouillon réel, et la même prévisualisation
    // recalculée par la fonction de la vue : les deux doivent coïncider.
    const brouillon = JSON.parse(lire('brouillon.json'));
    const previsualisation = brouillon.data.lignes.reduce(
        (acc, ligne) => {
            const ht = Math.round(ligne.quantite * ligne.prix_unitaire_ht * 100) / 100;
            acc.ht += ht;
            acc.tva += Math.round(ht * ligne.taux_tva) / 100;
            return acc;
        },
        { ht: 0, tva: 0 }
    );
    previsualisation.ht = Math.round(previsualisation.ht * 100) / 100;
    previsualisation.tva = Math.round(previsualisation.tva * 100) / 100;

    verifier('le HT prévisualisé égale le HT du serveur',
        Math.abs(previsualisation.ht - brouillon.data.montant_ht) < 0.01,
        `JS ${previsualisation.ht} / serveur ${brouillon.data.montant_ht}`);
    verifier('la TVA prévisualisée égale la TVA du serveur',
        Math.abs(previsualisation.tva - brouillon.data.montant_tva) < 0.01,
        `JS ${previsualisation.tva} / serveur ${brouillon.data.montant_tva}`);
    verifier('le TTC prévisualisé égale le TTC du serveur',
        Math.abs(previsualisation.ht + previsualisation.tva - brouillon.data.montant_ttc) < 0.01);

    console.log('\n── Écran d\'édition (lignes existantes) ──');
    const edition = await monter('form-edit.html');
    verifier('aucune erreur JavaScript à l\'édition', edition.erreursJs.length === 0, edition.erreursJs.join(' | '));

    const lignesRendues = edition.doc.querySelectorAll('#lignes-corps tr');
    verifier('les lignes enregistrées sont rendues', lignesRendues.length > 0, `${lignesRendues.length} ligne(s)`);
    verifier('le choix du fournisseur est VERROUILLÉ dès qu\'une ligne existe',
        edition.doc.getElementById('btn-choisir-fournisseur').disabled === true);
    verifier('l\'infobulle de verrouillage est visible',
        !edition.doc.getElementById('aide-fournisseur-verrouille').classList.contains('d-none'));
    verifier('le compteur de lignes est à jour',
        edition.doc.getElementById('compteur-lignes').textContent === String(lignesRendues.length));
    verifier('chaque ligne porte des champs quantité / prix / TVA',
        Array.from(lignesRendues).every((tr) =>
            tr.querySelector('[data-champ="quantite"]')
            && tr.querySelector('[data-champ="prix_unitaire_ht"]')
            && tr.querySelector('[data-champ="taux_tva"]')));
    verifier('le taux de TVA s\'affiche en pilule cliquable (UX-04)',
        Array.from(lignesRendues).every((tr) => tr.querySelector('[data-role="pilule-tva"]') !== null));
    verifier('la pilule de TVA est atteignable au clavier',
        Array.from(lignesRendues).every((tr) => tr.querySelector('[data-role="pilule-tva"]').getAttribute('tabindex') === '0'));
    verifier('chaque ligne offre sa suppression',
        Array.from(lignesRendues).every((tr) => tr.querySelector('[data-role="supprimer"]') !== null));
    verifier('« Continuer » s\'ouvre quand le bon est complet',
        edition.doc.getElementById('btn-continuer').disabled === false);

    console.log('\n── Pilule de TVA : bascule champ ⇄ pilule (UX-04) ──');
    const premiereLigne = edition.doc.querySelector('#lignes-corps tr');
    premiereLigne.querySelector('[data-role="pilule-tva"]')
        .dispatchEvent(new edition.window.Event('click', { bubbles: true }));
    verifier('le clic remplace la pilule par un champ',
        premiereLigne.querySelector('[data-role="pilule-tva"]').classList.contains('d-none')
        && !premiereLigne.querySelector('.champ-tva').classList.contains('d-none'));

    console.log('\n── Suppression d\'une ligne (sans confirmation : c\'est un brouillon) ──');
    const avant = edition.doc.querySelectorAll('#lignes-corps tr').length;
    premiereLigne.querySelector('[data-role="supprimer"]')
        .dispatchEvent(new edition.window.Event('click', { bubbles: true }));
    verifier('la ligne est retirée immédiatement',
        edition.doc.querySelectorAll('#lignes-corps tr').length === avant - 1,
        `${avant} → ${edition.doc.querySelectorAll('#lignes-corps tr').length}`);
    verifier('aucune confirmation n\'est demandée pour un brouillon',
        edition.etat.swals.length === 0);

    console.log('\n── Référence de prix PO-01 ──');
    const appelsPrix = edition.etat.requetes.filter((r) => String(r.url || '').includes('reference-prix'));
    verifier('la référence de prix est demandée au serveur pour chaque ligne',
        appelsPrix.length > 0, `${appelsPrix.length} appel(s)`);
    verifier('le prix saisi est transmis pour le calcul d\'écart',
        appelsPrix.every((r) => String(r.url).includes('prix=')));

    console.log(`\n${echecs === 0 ? '✅ Tous les contrôles passent' : `❌ ${echecs} contrôle(s) en échec`}`);
    process.exit(echecs === 0 ? 0 : 1);
})();
