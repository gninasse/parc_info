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

    // Filtres
    $('#filter_direction_id').on('change', function () {
        const directionId = $(this).val();
        const $serviceSelect = $('#filter_service_id');

        $serviceSelect.prop('disabled', true).html('<option value="">Chargement...</option>');

        if (directionId) {
            $.get(route('grh.employes.services-by-direction', directionId), function (services) {
                let options = '<option value="">Tous les services</option>';
                services.forEach(service => {
                    options += `<option value="${service.id}">${service.libelle}</option>`;
                });
                $serviceSelect.html(options).prop('disabled', false);
            });
        } else {
            $serviceSelect.html('<option value="">Tous les services</option>').prop('disabled', false);
        }
    });

    $('#btn-filter').on('click', function () {
        $table.bootstrapTable('refresh', {
            query: {
                direction_id: $('#filter_direction_id').val(),
                service_id: $('#filter_service_id').val(),
                statut: $('#filter_statut').val()
            }
        });
    });

    // Reset toolbar buttons on refresh
    $table.on('load-success.bs.table', function () {
        $('#btn-edit').prop('disabled', true);
        $('#btn-delete').prop('disabled', true);
    });
});
