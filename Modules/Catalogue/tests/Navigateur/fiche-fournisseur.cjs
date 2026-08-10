/**
 * Contrôle de la fiche fournisseur (onglets + CRUD des contacts).
 *
 * Les tests PHPUnit s'arrêtent à la réponse HTTP : ils ne disent pas si les
 * onglets sont correctement reliés à leurs panneaux, ni si la table des
 * contacts est configurée pour la sélection de ligne. Ce script lit le HTML
 * réellement produit.
 *
 * Usage : node Modules/Catalogue/tests/Navigateur/fiche-fournisseur.cjs [fichier]
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

const artefacts = process.env.CATALOGUE_ARTEFACTS || '/tmp/verif_catalogue';
const fichier = process.argv[2] || path.join(artefacts, 'fiche-fournisseur.html');

if (!fs.existsSync(fichier)) {
    console.log(`  ⨯ page introuvable : ${fichier} — lancer artefacts.php d'abord`);
    process.exit(1);
}

const html = fs.readFileSync(fichier, 'utf8');

// ── 1. Les onglets ────────────────────────────────────────────────────────

console.log('\n── Onglets de la fiche fournisseur ──');

const attendus = ['onglet-informations', 'onglet-contacts', 'onglet-articles', 'onglet-journal'];

attendus.forEach((identifiant) => {
    const declencheur = new RegExp(`data-bs-target="#${identifiant}"`);
    const panneau = new RegExp(`id="${identifiant}"`);

    if (!declencheur.test(html)) {
        ko(`l'onglet « ${identifiant} » n'a pas de déclencheur`);
        return;
    }

    if (!panneau.test(html)) {
        ko(`l'onglet « ${identifiant} » n'a pas de panneau correspondant`);
        return;
    }

    ok(`« ${identifiant} » : déclencheur et panneau présents`);
});

// Un seul onglet actif au départ, sinon deux panneaux s'affichent ensemble.
//
// On compte UNIQUEMENT les onglets porteurs de data-bs-toggle="tab" : la
// barre de navigation du gabarit contient elle aussi des `nav-link active`,
// et les compter donnait un faux positif (« 3 onglets actifs ») qui n'avait
// rien à voir avec cette fiche.
const ongletsActifs = (html.match(/<button[^>]*class="nav-link active"[^>]*data-bs-toggle="tab"[^>]*>/g) || []).length;

if (ongletsActifs === 1) {
    ok('un seul onglet est actif au chargement');
} else {
    ko(`${ongletsActifs} onglets actifs au chargement (attendu : 1)`);
}

const panneauxActifs = (html.match(/tab-pane fade show active/g) || []).length;
if (panneauxActifs === 1) {
    ok('un seul panneau est visible au chargement');
} else {
    ko(`${panneauxActifs} panneaux visibles au chargement (attendu : 1)`);
}

// ── 2. Accessibilité des onglets ──────────────────────────────────────────

console.log('\n── Accessibilité ──');

const boutonsOnglet = html.match(/<button[^>]*data-bs-toggle="tab"[^>]*>/g) || [];

if (boutonsOnglet.length === 0) {
    ko('aucun onglet trouvé');
} else {
    const sansRole = boutonsOnglet.filter((b) => !/role="tab"/.test(b));
    const sansControls = boutonsOnglet.filter((b) => !/aria-controls="/.test(b));
    const sansSelected = boutonsOnglet.filter((b) => !/aria-selected="/.test(b));

    if (sansRole.length === 0) ok(`les ${boutonsOnglet.length} onglets portent role="tab"`);
    else ko(`${sansRole.length} onglet(s) sans role="tab"`);

    if (sansControls.length === 0) ok('chaque onglet désigne son panneau (aria-controls)');
    else ko(`${sansControls.length} onglet(s) sans aria-controls`);

    if (sansSelected.length === 0) ok('chaque onglet déclare son état de sélection');
    else ko(`${sansSelected.length} onglet(s) sans aria-selected`);

    // Les onglets sont des BOUTONS, pas des liens href="#" : au clavier, un
    // lien vers nulle part ne répond pas à la barre d'espace et déroute les
    // lecteurs d'écran.
    const liensOnglet = html.match(/<a[^>]*data-bs-toggle="tab"[^>]*>/g) || [];
    if (liensOnglet.length === 0) ok('aucun onglet n\'est un lien mort (href="#")');
    else ko(`${liensOnglet.length} onglet(s) rendus en <a> au lieu de <button>`);
}

// ── 3. La table des contacts ──────────────────────────────────────────────

console.log('\n── Table des contacts ──');

const table = (html.match(/<table[^>]*id="contacts-table"[^>]*>/) || [])[0];

if (!table) {
    ko('la table des contacts est absente');
} else {
    ok('la table des contacts est présente');

    // Toolbar + sélection de ligne : convention du projet.
    if (/data-click-to-select="true"/.test(table)) ok('la sélection se fait au clic sur la ligne');
    else ko('data-click-to-select absent : la sélection de ligne ne fonctionnera pas');

    if (/data-single-select="true"/.test(table)) ok('la sélection est unique');
    else ko('data-single-select absent : plusieurs lignes sélectionnables, boutons ambigus');

    if (/data-url="[^"]*contacts[^"]*"/.test(table)) ok('la table pointe vers la route des contacts');
    else ko('la table n\'a pas d\'URL de données');

    if (/data-checkbox="true"/.test(html)) ok('la colonne de sélection est déclarée');
    else ko('aucune colonne de sélection : rien à cocher');
}

// ── 4. La toolbar ─────────────────────────────────────────────────────────

console.log('\n── Toolbar des contacts ──');

const boutons = {
    'btn-contact-add': 'Ajouter',
    'btn-contact-edit': 'Modifier',
    'btn-contact-principal': 'Définir principal',
    'btn-contact-delete': 'Supprimer',
};

Object.entries(boutons).forEach(([identifiant, libelle]) => {
    if (new RegExp(`id="${identifiant}"`).test(html)) {
        ok(`bouton « ${libelle} » présent`);
    } else {
        ko(`bouton « ${libelle} » absent`);
    }
});

// Les boutons agissant sur une sélection doivent partir DÉSACTIVÉS : actifs
// sans sélection, ils produiraient une erreur au premier clic.
['btn-contact-edit', 'btn-contact-principal', 'btn-contact-delete'].forEach((identifiant) => {
    const balise = (html.match(new RegExp(`<button[^>]*id="${identifiant}"[^>]*>`)) || [])[0];

    if (!balise) return;

    if (/\bdisabled\b/.test(balise)) {
        ok(`« ${identifiant} » part désactivé (aucune sélection)`);
    } else {
        ko(`« ${identifiant} » est actif sans sélection : clic en erreur`);
    }
});

// ── 5. La modale de saisie ────────────────────────────────────────────────

console.log('\n── Modale de contact ──');

if (/id="contactModal"/.test(html)) {
    ok('la modale de contact est présente');

    const champs = ['c-nom', 'c-prenom', 'c-fonction', 'c-telephone', 'c-email', 'c-est-principal', 'c-est-actif'];
    const manquants = champs.filter((champ) => !new RegExp(`id="${champ}"`).test(html));

    if (manquants.length === 0) ok(`les ${champs.length} champs de saisie sont présents`);
    else ko(`champs manquants : ${manquants.join(', ')}`);

    // Chaque champ doit être étiqueté, sinon le lecteur d'écran annonce un
    // champ sans nom.
    const sansEtiquette = champs.filter((champ) => !new RegExp(`for="${champ}"`).test(html));

    if (sansEtiquette.length === 0) ok('chaque champ porte une étiquette');
    else ko(`champs sans étiquette : ${sansEtiquette.join(', ')}`);
} else {
    ko('la modale de contact est absente');
}

console.log(
    echecs === 0
        ? '\nFiche fournisseur : conforme'
        : `\n${echecs} contrôle(s) en échec`
);

process.exit(echecs === 0 ? 0 : 1);
