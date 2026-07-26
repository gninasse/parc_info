/**
 * index.js — point d'entrée Magasins
 */
import { MagasinForm }    from './MagasinForm.js';
import { MagasinActions } from './MagasinActions.js';

// Les formatters DOIVENT être en window.* et AVANT DOMContentLoaded
window.dateFormatter = function (value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

window.statutFormatter = function (value) {
    return value
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-secondary">Inactif</span>';
};

$(function () {
    const $table = $('#magasins-table');

    // Objet tableInstance partagé entre les classes
    const tableInstance = {
        refresh:       () => $table.bootstrapTable('refresh'),
        getSelectedId: () => {
            const sel = $table.bootstrapTable('getSelections');
            if (!sel.length) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });
                return null;
            }
            return sel[0].id;
        },
        getSelectedRow: () => {
            const sel = $table.bootstrapTable('getSelections');
            return sel.length ? sel[0] : null;
        }
    };

    const form    = new MagasinForm('#magasinModal', '#magasin-form', tableInstance);
    const actions = new MagasinActions(tableInstance, form);

    // Activation/désactivation des boutons toolbar selon la sélection
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        $('#btn-edit').prop('disabled', !one);
        $('#btn-manage-responsibles').prop('disabled', !one);
        $('#btn-manage-rights').prop('disabled', !one);
        $('#btn-delete').prop('disabled', sel.length === 0);
    });

    // Injection des filtres externes dans les params Bootstrap Table
    $('#filter-type, #filter-status').on('change', () => $table.bootstrapTable('refresh'));
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.type = $('#filter-type').val();
            params.est_actif = $('#filter-status').val();
            return params;
        }
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
