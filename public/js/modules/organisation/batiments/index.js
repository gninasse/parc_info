/**
 * index.js
 * Entry point for Batiments management
 */
import { BatimentForm } from './BatimentForm.js';
import { BatimentActions } from './BatimentActions.js';

$(function () {
    const $table = $('#batiments-table');

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
        }
    };

    const batimentForm = new BatimentForm('#createBatimentModal', '#batimentForm', tableInstance);
    new BatimentActions(tableInstance, batimentForm);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const isSingleSelection = selections.length === 1;

        $('#btn-edit').prop('disabled', !isSingleSelection);
        $('#btn-delete').prop('disabled', !isSingleSelection);
    });

    $('#filter_site_id').on('change', function () {
        const siteId = $(this).val();
        let url = window.batimentRoutes.data;
        if (siteId) url += '?site_id=' + siteId;
        $table.bootstrapTable('refresh', { url: url });
    });
});
