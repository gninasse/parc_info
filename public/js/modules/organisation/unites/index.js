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

    // Filters logic
    $('#filter_site_id').on('change', function () {
        const siteId = $(this).val();
        loadDirections(siteId, '#filter_direction_id');
        refreshTable();
    });

    $('#filter_direction_id').on('change', function () {
        const directionId = $(this).val();
        loadServices(directionId, '#filter_service_id');
        refreshTable();
    });

    $('#filter_service_id').on('change', function () {
        refreshTable();
    });

    function refreshTable() {
        const siteId = $('#filter_site_id').val();
        const directionId = $('#filter_direction_id').val();
        const serviceId = $('#filter_service_id').val();
        
        let url = window.uniteRoutes.data;
        const params = [];
        if (siteId) params.push(`site_id=${siteId}`);
        if (directionId) params.push(`direction_id=${directionId}`);
        if (serviceId) params.push(`service_id=${serviceId}`);
        
        if (params.length > 0) url += '?' + params.join('&');
        $table.bootstrapTable('refresh', { url: url });
    }

    function loadDirections(siteId, targetSelect) {
        const $target = $(targetSelect);
        if (!siteId) {
            $target.html('<option value="">Toutes les directions</option>').prop('disabled', true);
            $('#filter_service_id').html('<option value="">Tous les services</option>').prop('disabled', true);
            return;
        }

        const url = window.uniteRoutes.directionsBySite.replace(':siteId', siteId);
        $.get(url, function (data) {
            let html = '<option value="">Toutes les directions</option>';
            data.forEach(item => {
                html += `<option value="${item.id}">${item.libelle}</option>`;
            });
            $target.html(html).prop('disabled', false);
        });
    }

    function loadServices(directionId, targetSelect) {
        const $target = $(targetSelect);
        if (!directionId) {
            $target.html('<option value="">Tous les services</option>').prop('disabled', true);
            return;
        }

        const url = window.uniteRoutes.servicesByDirection.replace(':directionId', directionId);
        $.get(url, function (data) {
            let html = '<option value="">Tous les services</option>';
            data.forEach(item => {
                html += `<option value="${item.id}">${item.libelle}</option>`;
            });
            $target.html(html).prop('disabled', false);
        });
    }
});
