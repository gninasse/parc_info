/**
 * Suivi du stock des consommables.
 */
document.addEventListener('DOMContentLoaded', function () {
    const $table = $('#items-table');

    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.niveau = $('#filter-niveau').val();
            return params;
        },
    });

    $('#filter-niveau').on('change', () => $table.bootstrapTable('refresh'));
});
