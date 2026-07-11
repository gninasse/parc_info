/**
 * centre-impression.js — Print Center logic for multiple equipment label printing
 */

window.printQueryParams = function (params) {
    return Object.assign(params, {
        categorie_id: $('#filter-categorie').val(),
        site_id: $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codePrintFormatter = (val, row) => {
    // Determine the route dynamically if possible, or fallback to the show json route prefix
    // We can extract module routing if needed, or simply link to the item detail page
    return `<span class="fw-bold text-dark small">${val}</span>`;
};

window.statutPrintFormatter = (val) => {
    const map = {
        en_service: ['success', 'EN SERVICE'],
        en_stock: ['secondary', 'EN STOCK'],
        en_stock_magasin: ['secondary', 'MAGASIN'],
        en_stock_dsi: ['info', 'STOCK DSI'],
        en_reparation: ['warning', 'EN RÉPARATION'],
        perdu: ['danger', 'PERDU / VOLÉ'],
        reforme: ['dark', 'RÉFORMÉ'],
    };
    const [color, label] = map[val] ?? ['info', val];
    return `<span class="badge bg-${color}-subtle text-${color} border border-${color}-subtle px-2 py-1">${label}</span>`;
};

$(document).ready(function () {
    const $table = $('#equipements-print-table');
    const $btnPrint = $('#btn-print-selected');
    const $selectedCount = $('#selected-count');

    // Update print button status on selection change
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const count = selections.length;
        
        $selectedCount.text(count);
        $btnPrint.prop('disabled', count === 0);
    });

    // Apply filters
    $('#btn-apply-filters').on('click', function () {
        $table.bootstrapTable('refresh');
    });

    // Reset filters
    $('#btn-reset-filters').on('click', function () {
        $('#filter-categorie, #filter-site, #filter-direction, #filter-statut').val('');
        $table.bootstrapTable('refresh');
    });

    // Print selected labels
    $btnPrint.on('click', function () {
        const selections = $table.bootstrapTable('getSelections');
        if (selections.length === 0) return;

        const ids = selections.map(row => row.id).join(',');
        const printUrl = `${window.printCenterConfig.printUrl}?ids=${ids}`;
        
        // Open printing in a new tab
        window.open(printUrl, '_blank');
    });
});
