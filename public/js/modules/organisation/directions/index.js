/**
 * index.js
 * Entry point for Directions management
 */
import { DirectionForm } from './DirectionForm.js';
import { DirectionActions } from './DirectionActions.js';

$(function () {
    const $table = $('#directions-table');

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

    const directionForm = new DirectionForm('#createDirectionModal', '#directionForm', tableInstance);
    new DirectionActions(tableInstance, directionForm);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const isSingleSelection = selections.length === 1;

        $('#btn-edit').prop('disabled', !isSingleSelection);
        $('#btn-delete').prop('disabled', !isSingleSelection);
    });

    $('#filter_site_id').on('change', function () {
        $table.bootstrapTable('refresh', {
            url: "{{ route('organisation.directions.data') }}?" + $.param({ site_id: $(this).val() })
        });
    });
});
