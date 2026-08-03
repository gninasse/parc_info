/**
 * formatters.js — formatters Bootstrap Table du module Stock.
 *
 * Les badges de nature viennent du module Catalogue (S11 — LA référence
 * visuelle commune, jamais dupliquée) : l'import ci-dessous les pose en
 * window.* (natureBadgeFormatter, statutFormatter, fcfaFormatter,
 * seuilFormatter). Ce fichier les réexporte et ajoute les badges propres au
 * Stock : statut de niveau, statut de document, type de mouvement — tous en
 * icône + texte, jamais la couleur seule (S7).
 */
import { NATURES } from '../../catalogue/formatters.js';

export { NATURES };

const echapper = (texte) => $('<span>').text(texte ?? '').html();

/** Statut de niveau : ✓ OK vert · ⚠ SOUS SEUIL orange · ⛔ RUPTURE rouge (S7). */
window.niveauStatutFormatter = function (value) {
    const statuts = {
        OK: { texte: '✓ OK', classes: 'bg-success' },
        SOUS_SEUIL: { texte: '⚠ SOUS SEUIL', classes: 'bg-warning text-dark' },
        RUPTURE: { texte: '⛔ RUPTURE', classes: 'bg-danger' },
    };
    const statut = statuts[value];
    if (!statut) return echapper(value);
    return `<span class="badge ${statut.classes}">${statut.texte}</span>`;
};

/**
 * Statut de document : libellés orientés geste (amendement UX n°2), avec la
 * progression en argument optionnel du row (« 12/15 réf. »).
 */
window.documentStatutFormatter = function (value, row = {}) {
    const statuts = {
        BROUILLON: { texte: 'Brouillon', classes: 'bg-secondary' },
        REFERENCEMENT: { texte: 'Saisie des n° de série', classes: 'bg-warning text-dark' },
        POINTAGE: { texte: 'Pointage en cours', classes: 'bg-warning text-dark' },
        EN_COURS: { texte: 'En cours', classes: 'bg-warning text-dark' },
        VALIDE: { texte: 'Validé', classes: 'bg-success' },
        ANNULE: { texte: 'Annulé', classes: 'bg-secondary text-decoration-line-through' },
    };
    const statut = statuts[value];
    if (!statut) return echapper(value);
    const progression = row.progression ? ` <small>${echapper(row.progression)}</small>` : '';
    return `<span class="badge ${statut.classes}">${statut.texte}</span>${progression}`;
};

/** Type de mouvement : ⬆ ENTREE vert · ⬇ SORTIE rouge · ⇄ TRANSFERT bleu · ± AJUSTEMENT orange. */
window.typeMouvementFormatter = function (value) {
    const types = {
        ENTREE: { texte: '⬆ ENTREE', classes: 'bg-success' },
        SORTIE: { texte: '⬇ SORTIE', classes: 'bg-danger' },
        TRANSFERT_ENTREE: { texte: '⬆ TRANSFERT', classes: 'bg-primary' },
        TRANSFERT_SORTIE: { texte: '⬇ TRANSFERT', classes: 'bg-primary' },
        AJUSTEMENT: { texte: '± AJUSTEMENT', classes: 'bg-warning text-dark' },
    };
    const type = types[value];
    if (!type) return echapper(value);
    return `<span class="badge ${type.classes}">${type.texte}</span>`;
};

/** Seuil effectif : valeur + pastille d'origine « local » / « article » / « — » (UX §2). */
window.seuilEffectifFormatter = function (value, row) {
    if (value === null || value === undefined) return '—';
    const origines = {
        local: '<span class="badge bg-info-subtle text-info-emphasis ms-1">local</span>',
        article: '<span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">article</span>',
    };
    return `${Number(value).toLocaleString('fr-FR')}${origines[row.seuil_origine] ?? ''}`;
};

/** Article : code en monospace + nom. */
window.articleFormatter = function (value, row) {
    return `<span class="font-monospace small text-muted">${echapper(row.article_code)}</span> ${echapper(value)}`;
};

/** Numéro de document : « Brouillon #58 » en italique gris, définitif en monospace. */
window.numeroFormatter = function (value) {
    if (!value) return '—';
    return String(value).startsWith('Brouillon')
        ? `<em class="text-muted">${echapper(value)}</em>`
        : `<span class="font-monospace">${echapper(value)}</span>`;
};

window.monospaceFormatter = function (value) {
    return value ? `<span class="font-monospace">${echapper(value)}</span>` : '—';
};
