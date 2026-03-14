/**
 * index.js
 * Entry point for Etages management
 */
import { EtageForm } from './EtageForm.js';
import { EtageActions } from './EtageActions.js';

$(function () {
    const $table = $('#etages-table');

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

    const etageForm = new EtageForm('#createEtageModal', '#etageForm', tableInstance);
    new EtageActions(tableInstance, etageForm);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const isSingleSelection = selections.length === 1;

        $('#btn-edit').prop('disabled', !isSingleSelection);
        $('#btn-delete').prop('disabled', !isSingleSelection);
    });

    // Filtres
    $('#filter_site_id').on('change', function () {
        loadBatiments($(this).val(), '#filter_batiment_id');
        refreshTable();
    });

    $('#filter_batiment_id').on('change', function () {
        refreshTable();
    });

    function refreshTable() {
        const siteId = $('#filter_site_id').val();
        const batimentId = $('#filter_batiment_id').val();
        let url = window.etageRoutes.data;
        const params = [];
        if (siteId) params.push(`site_id=${siteId}`);
        if (batimentId) params.push(`batiment_id=${batimentId}`);
        if (params.length > 0) url += '?' + params.join('&');
        $table.bootstrapTable('refresh', { url: url });
    }

    window.loadBatiments = function(siteId, targetSelect, selectedId = null) {
        if (!siteId) {
            $(targetSelect).html('<option value="">Tous les bâtiments</option>').prop('disabled', true);
            return;
        }
        const url = window.etageRoutes.batimentsBySite.replace(':siteId', siteId);
        $.get(url, function(data) {
            let options = '<option value="">Tous les bâtiments</option>';
            data.forEach(function(bat) {
                let selected = selectedId == bat.id ? 'selected' : '';
                options += `<option value="${bat.id}" ${selected}>${bat.libelle}</option>`;
            });
            $(targetSelect).html(options).prop('disabled', false);
        });
    };
});
