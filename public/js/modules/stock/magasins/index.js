/**
 * index.js — page liste Magasins (bootstrap de la page + filtre statut)
 */
import '../shared/formatters.js';
import { MagasinForm } from './MagasinForm.js';
import { MagasinActions } from './MagasinActions.js';

$(function () {
    const $table = $('#magasins-table');

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

    const form = new MagasinForm('#magasinModal', '#magasin-form', {
        onSaved: () => tableInstance.refresh(),
    });
    new MagasinActions(tableInstance, form);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const one = $table.bootstrapTable('getSelections').length === 1;
        $('#btn-show, #btn-edit, #btn-toggle, #btn-delete').prop('disabled', !one);
    });

    $('#filter-statut').on('change', () => $table.bootstrapTable('refresh'));
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.statut = $('#filter-statut').val();
            return params;
        },
    });
});
