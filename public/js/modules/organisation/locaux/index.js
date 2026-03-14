/**
 * index.js
 * Entry point for Locaux management
 */
import { LocalForm } from './LocalForm.js';
import { LocalActions } from './LocalActions.js';

$(function () {
    const $table = $('#locaux-table');

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

    const localForm = new LocalForm('#createLocalModal', '#localForm', tableInstance);
    new LocalActions(tableInstance, localForm);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const isSingleSelection = selections.length === 1;

        $('#btn-edit').prop('disabled', !isSingleSelection);
        $('#btn-delete').prop('disabled', !isSingleSelection);
    });

    // Filtres cascadants
    $('#filter_site_id').on('change', function () {
        loadBatiments($(this).val(), '#filter_batiment_id');
        $('#filter_etage_id').html('<option value="">Tous les étages</option>').prop('disabled', true);
        refreshTable();
    });

    $('#filter_batiment_id').on('change', function () {
        loadEtages($(this).val(), '#filter_etage_id');
        refreshTable();
    });

    $('#filter_etage_id').on('change', function () {
        refreshTable();
    });

    function refreshTable() {
        const siteId = $('#filter_site_id').val();
        const batimentId = $('#filter_batiment_id').val();
        const etageId = $('#filter_etage_id').val();
        let url = window.localRoutes.data;
        const params = [];
        if (siteId) params.push(`site_id=${siteId}`);
        if (batimentId) params.push(`batiment_id=${batimentId}`);
        if (etageId) params.push(`etage_id=${etageId}`);
        if (params.length > 0) url += '?' + params.join('&');
        $table.bootstrapTable('refresh', { url: url });
    }

    window.loadBatiments = function(siteId, targetSelect, selectedId = null) {
        if (!siteId) {
            $(targetSelect).html('<option value="">Tous les bâtiments</option>').prop('disabled', true);
            return;
        }
        const url = window.localRoutes.batimentsBySite.replace(':siteId', siteId);
        $.get(url, function(data) {
            let options = '<option value="">Tous les bâtiments</option>';
            data.forEach(function(bat) {
                let selected = selectedId == bat.id ? 'selected' : '';
                options += `<option value="${bat.id}" ${selected}>${bat.libelle}</option>`;
            });
            $(targetSelect).html(options).prop('disabled', false);
        });
    };

    window.loadEtages = function(batimentId, targetSelect, selectedId = null) {
        if (!batimentId) {
            $(targetSelect).html('<option value="">Tous les étages</option>').prop('disabled', true);
            return;
        }
        const url = window.localRoutes.etagesByBatiment.replace(':batimentId', batimentId);
        $.get(url, function(data) {
            let options = '<option value="">Tous les étages</option>';
            data.forEach(function(etg) {
                let selected = selectedId == etg.id ? 'selected' : '';
                options += `<option value="${etg.id}" ${selected}>${etg.libelle}</option>`;
            });
            $(targetSelect).html(options).prop('disabled', false);
        });
    };
});
