/**
 * index.js
 * Entry point for Unités de Mesure management
 */
import { UniteForm } from './UniteForm.js';
import { UniteActions } from './UniteActions.js';

$(function () {
    const $table = $('#unites-table');

    // Formatters
    window.dateFormatter = function (value) {
        if (!value) return '-';
        return new Date(value).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    window.typeFormatter = function (value) {
        const types = {
            'masse': 'Masse',
            'volume': 'Volume',
            'longueur': 'Longueur',
            'quantite': 'Quantité'
        };
        return types[value] || value;
    };

    // Table instance helper
    const tableInstance = {
        refresh: () => $table.bootstrapTable('refresh'),
        getSelectedId: () => {
            const selections = $table.bootstrapTable('getSelections');
            if (selections.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Attention',
                    text: 'Veuillez sélectionner une ligne'
                });
                return null;
            }
            return selections[0].id;
        },
        getSelected: () => {
            const selections = $table.bootstrapTable('getSelections');
            return selections.length > 0 ? selections[0] : null;
        }
    };

    // Initialize Form and Actions
    const uniteForm = new UniteForm('#createUniteModal', '#uniteForm', tableInstance);
    new UniteActions(tableInstance, uniteForm);

    // Enable/Disable buttons on selection
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const isSingleSelection = selections.length === 1;

        $('#btn-edit-unite').prop('disabled', !isSingleSelection);
        $('#btn-delete-unite').prop('disabled', !isSingleSelection);
    });
});
