/**
 * index.js — point d'entrée Inventaires
 */
import { InventaireForm }    from './InventaireForm.js';
import { InventaireActions } from './InventaireActions.js';

// Les formatters DOIVENT être en window.* et AVANT DOMContentLoaded
window.inventaireStatutFormatter = function (value) {
    switch (value) {
        case 'BROUILLON':
            return '<span class="badge bg-warning text-white">Brouillon (En cours)</span>';
        case 'VALIDE':
            return '<span class="badge bg-success">Clôturé & Validé</span>';
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
    const $table = $('#inventaires-table');

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

    const form    = new InventaireForm('#inventaireModal', '#inventaire-form', tableInstance);
    const actions = new InventaireActions(tableInstance, form);

    // Activation/désactivation des boutons toolbar selon la sélection
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        
        $('#btn-view-inventaire').prop('disabled', !one);
        
        if (one) {
            const row = sel[0];
            const isBrouillon = row.statut === 'BROUILLON';
            
            // Saisie
            const saisieUrl = route('stock.inventaires.saisie', row.id);
            $('#btn-saisie-inventaire').attr('href', isBrouillon ? saisieUrl : '#');
            $('#btn-saisie-inventaire').toggleClass('disabled', !isBrouillon);

            // Actions validation/annulation
            $('#btn-approve-inventaire').prop('disabled', !isBrouillon);
            $('#btn-cancel-inventaire').prop('disabled', !isBrouillon);
        } else {
            $('#btn-saisie-inventaire').attr('href', '#').addClass('disabled');
            $('#btn-approve-inventaire').prop('disabled', true);
            $('#btn-cancel-inventaire').prop('disabled', true);
        }
    });

    // Filtres externes
    $('#filter-statut, #filter-magasin').on('change', () => $table.bootstrapTable('refresh'));
    
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.statut = $('#filter-statut').val();
            params.magasin_id = $('#filter-magasin').val();
            return params;
        }
    });
});
