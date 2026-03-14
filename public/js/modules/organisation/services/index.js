/**
 * index.js
 * Entry point for Services management
 */
import { ServiceForm } from './ServiceForm.js';
import { ServiceActions } from './ServiceActions.js';

$(function () {
    const $table = $('#services-table');

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

    const serviceForm = new ServiceForm('#createServiceModal', '#serviceForm', tableInstance);
    new ServiceActions(tableInstance, serviceForm);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const isSingleSelection = selections.length === 1;

        $('#btn-edit').prop('disabled', !isSingleSelection);
        $('#btn-delete').prop('disabled', !isSingleSelection);
    });

    $('#filter_site_id').on('change', function () {
        loadDirections($(this).val(), '#filter_direction_id');
        refreshTable();
    });

    $('#filter_direction_id').on('change', function () {
        refreshTable();
    });

    function refreshTable() {
        const siteId = $('#filter_site_id').val();
        const directionId = $('#filter_direction_id').val();
        let url = window.serviceRoutes.data;
        const params = [];
        if (siteId) params.push(`site_id=${siteId}`);
        if (directionId) params.push(`direction_id=${directionId}`);
        if (params.length > 0) url += '?' + params.join('&');
        $table.bootstrapTable('refresh', { url: url });
    }

    window.loadDirections = function(siteId, targetSelect, selectedId = null) {
        if (!siteId) {
            $(targetSelect).html('<option value="">Toutes les directions</option>').prop('disabled', true);
            return;
        }
        const url = window.serviceRoutes.directionsBySite.replace(':siteId', siteId);
        $.get(url, function(data) {
            let options = '<option value="">Toutes les directions</option>';
            data.forEach(function(dir) {
                let selected = selectedId == dir.id ? 'selected' : '';
                options += `<option value="${dir.id}" ${selected}>${dir.libelle}</option>`;
            });
            $(targetSelect).html(options).prop('disabled', false);
        });
    };
});
