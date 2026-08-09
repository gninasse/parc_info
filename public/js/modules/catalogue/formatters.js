/**
 * formatters.js — formatters Bootstrap Table partagés du module Catalogue.
 * Les badges de nature sont LA référence visuelle commune (SFD §4) : Stock et
 * Achat devront importer ce fichier plutôt que redéfinir les leurs.
 */

const echapper = (texte) => $('<span>').text(texte ?? '').html();

const NATURES = {
    consommable: { court: 'C', libelle: 'Consommable', classes: 'bg-info text-dark', icone: '' },
    piece: { court: 'P', libelle: 'Pièce', classes: 'text-white', style: 'background-color:#6f42c1;', icone: '' },
    equipement: { court: 'E', libelle: 'Équipement', classes: 'bg-dark', icone: '' },
    licence: { court: 'L', libelle: 'Licence', classes: 'text-dark', style: 'background-color:#e2d9f3;', icone: '<i class="fas fa-key me-1"></i>' },
    // P0-A : service commandé, jamais stocké — badge S sarcelle.
    prestation: { court: 'S', libelle: 'Prestation', classes: 'text-white', style: 'background-color:#20c997;', icone: '' },
};

/** Badge de nature : E gris foncé, C cyan, P violet, L violet clair + clé, S sarcelle. */
window.natureBadgeFormatter = function (value) {
    const nature = NATURES[value];
    if (!nature) return echapper(value);
    return `<span class="badge ${nature.classes}" style="${nature.style ?? ''}" title="${nature.libelle}">${nature.icone}${nature.court}</span>`;
};

window.statutFormatter = function (value) {
    return value
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-danger">Inactif</span>';
};

/** Montant FCFA : « 450 000 FCFA », tiret si absent. */
window.fcfaFormatter = function (value) {
    if (value === null || value === undefined || value === '') return '—';
    return `${Number(value).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FCFA`;
};

/** Seuil : tiret pour les natures sans stock (equipement/licence/prestation). */
window.seuilFormatter = function (value, row) {
    if (row.nature === 'equipement' || row.nature === 'licence' || row.nature === 'prestation' || value === null || value === undefined) return '—';
    return Number(value).toLocaleString('fr-FR');
};

export { NATURES };
