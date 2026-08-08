/**
 * index.js — page Catégories (bootstrap de la page + filtres)
 */
import { CategorieForm } from './CategorieForm.js';
import { CategorieActions } from './CategorieActions.js';

const echapper = (texte) => $('<span>').text(texte ?? '').html();

// Formatters globaux, définis avant l'init de la table
window.statutFormatter = function (value) {
    return value
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-danger">Inactif</span>';
};

// Rendu arborescent : niveaux 1 en gras, niveaux 2 indentés préfixés « └ »
window.libelleFormatter = function (value, row) {
    if (row.niveau === 2) {
        return `<span class="ms-3 text-muted">└</span> ${echapper(value)}`;
    }
    return `<strong>${echapper(value)}</strong>`;
};

$(function () {
    const $table = $('#categories-table');

    const tableInstance = {
        refresh: () => $table.bootstrapTable('refresh'),
        getSelectedId: () => {
            const sel = $table.bootstrapTable('getSelections');
            if (!sel.length) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });
                return null;
            }
            return sel[0].id;
        },
    };

    const form = new CategorieForm('#categorieModal', '#categorie-form', tableInstance);
    new CategorieActions(tableInstance, form);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        $('#btn-edit, #btn-toggle, #btn-delete').prop('disabled', sel.length !== 1);
    });

    $('#filter-statut').on('change', () => $table.bootstrapTable('refresh'));
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.est_actif = $('#filter-statut').val();
            return params;
        },
    });
});
