/**
 * index.js — point d'entrée Valorisation
 */
import { ValorisationActions } from './ValorisationActions.js';

// Les formatters DOIVENT être en window.* et AVANT DOMContentLoaded
window.typeFormatter = function (value) {
    switch (value) {
        case 'MENSUEL':
            return '<span class="badge bg-primary text-white">Mensuel (Automatique)</span>';
        case 'PONCTUEL':
            return '<span class="badge bg-info text-dark">Ponctuel (Manuel)</span>';
        default:
            return `<span class="badge bg-light text-dark">${value}</span>`;
    }
};

window.dateFormatter = function (value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

$(function () {
    const $table = $('#valorisation-table');

    // Objet tableInstance partagé
    const tableInstance = {
        refresh:       () => $table.bootstrapTable('refresh'),
        getSelectedId: () => {
            const sel = $table.bootstrapTable('getSelections');
            if (!sel.length) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });
                return null;
            }
            return sel[0].id;
        }
    };

    const actions = new ValorisationActions(tableInstance);

    // Activation/désactivation des boutons toolbar selon la sélection
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        $('#btn-view-snapshot').prop('disabled', sel.length !== 1);
    });

    // Filtres externes
    $('#filter-type').on('change', () => $table.bootstrapTable('refresh'));
    
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.type = $('#filter-type').val();
            return params;
        }
    });
});
