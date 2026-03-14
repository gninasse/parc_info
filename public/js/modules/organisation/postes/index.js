/**
 * index.js
 * Entry point for PosteTravails management
 */
import { PosteTravailForm } from './PosteTravailForm.js';
import { PosteTravailActions } from './PosteTravailActions.js';

$(function () {
    const $table = $('#postes-table');

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

    const posteForm = new PosteTravailForm('#posteModal', '#posteForm', tableInstance);
    new PosteTravailActions(tableInstance, posteForm);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const isSingleSelection = selections.length === 1;

        $('#btn-edit').prop('disabled', !isSingleSelection);
        $('#btn-delete').prop('disabled', !isSingleSelection);
    });

    $('#filter_service_id').on('change', function () {
        $table.bootstrapTable('refresh', {
            query: { service_id: $(this).val() }
        });
    });
});
