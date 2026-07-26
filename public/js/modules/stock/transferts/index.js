/**
 * index.js — point d'entrée Transferts
 */
import { TransfertForm }    from './TransfertForm.js';
import { TransfertActions } from './TransfertActions.js';

// Les formatters DOIVENT être en window.* et AVANT DOMContentLoaded
window.transferStatutFormatter = function (value) {
    switch (value) {
        case 'EN_ATTENTE':
            return '<span class="badge bg-warning text-white">En attente</span>';
        case 'VALIDE':
            return '<span class="badge bg-success">Validé</span>';
        case 'REJETE':
            return '<span class="badge bg-danger">Rejeté</span>';
        case 'ANNULE':
            return '<span class="badge bg-secondary">Annulé</span>';
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
    const $table = $('#transferts-table');

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
        },
        getSelectedRow: () => {
            const sel = $table.bootstrapTable('getSelections');
            return sel.length ? sel[0] : null;
        }
    };

    const form    = new TransfertForm('#transfertModal', '#transfert-form', tableInstance);
    const actions = new TransfertActions(tableInstance, form);

    // Activation/désactivation des boutons toolbar selon la sélection
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        
        $('#btn-view-transfert').prop('disabled', !one);
        
        if (one) {
            const row = sel[0];
            const isEnAttente = row.statut === 'EN_ATTENTE';
            $('#btn-approve-transfert').prop('disabled', !isEnAttente);
            $('#btn-reject-transfert').prop('disabled', !isEnAttente);
            $('#btn-cancel-transfert').prop('disabled', !isEnAttente);
        } else {
            $('#btn-approve-transfert').prop('disabled', true);
            $('#btn-reject-transfert').prop('disabled', true);
            $('#btn-cancel-transfert').prop('disabled', true);
        }
    });

    // Filtres externes
    $('#filter-statut, #filter-source, #filter-dest').on('change', () => $table.bootstrapTable('refresh'));
    
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.statut = $('#filter-statut').val();
            params.magasin_source_id = $('#filter-source').val();
            params.magasin_destination_id = $('#filter-dest').val();
            return params;
        }
    });
});
