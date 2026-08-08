/**
 * index.js — page liste Fournisseurs (bootstrap de la page + filtres)
 */
import { FournisseurForm } from './FournisseurForm.js';
import { FournisseurActions } from './FournisseurActions.js';

// Les formatters doivent être en window.* et définis avant l'init de la table
window.statutFormatter = function (value) {
    return value
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-danger">Inactif</span>';
};

$(function () {
    const $table = $('#fournisseurs-table');

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

    const form = new FournisseurForm('#fournisseurModal', '#fournisseur-form', {
        onSaved: () => tableInstance.refresh(),
    });
    new FournisseurActions(tableInstance, form);

    // Activation des boutons toolbar selon la sélection
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        $('#btn-show, #btn-edit, #btn-toggle, #btn-delete').prop('disabled', !one);
    });

    // Filtre statut injecté dans les paramètres de la table
    $('#filter-statut').on('change', () => $table.bootstrapTable('refresh'));
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.statut = $('#filter-statut').val();
            return params;
        },
    });
});
