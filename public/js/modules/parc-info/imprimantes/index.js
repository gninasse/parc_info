/**
 * Gestion des Imprimantes - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Wizards
 */

const prefix = window.routePrefix || 'parc-info.imprimantes';

window.imprimantesQueryParams = (params) => {
    return Object.assign(params, {
        type_imprimante_id: $('#filter-type').val(),
        site_id: $('#filter-site').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) => `<a href="${route(prefix + '.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

window.statutFormatter = (val) => {
    const map = {
        en_service: ['success', 'EN SERVICE'],
        en_stock: ['secondary', 'EN STOCK'],
        en_reparation: ['warning', 'EN RÉPARATION'],
        perdu: ['danger', 'PERDU / VOLÉ'],
        reforme: ['dark', 'RÉFORMÉ'],
    };
    const [color, label] = map[val] ?? ['info', val];
    return `<span class="badge bg-${color}-subtle text-${color} border border-${color}-subtle px-2 py-1 small">${label}</span>`;
};

window.actionsFormatter = (id) => `
    <div class="d-flex gap-1">
        <a href="${route(prefix + '.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': function (e, value, row, index) {
        deleteImprimante(row.id);
    }
};

function loadKpis() {
    $.get(route(prefix + '.data'), { limit: 9999, offset: 0 }, (res) => {
        const rows = res.rows ?? [];
        $('#kpi-total').text(res.total ?? 0);
        $('#kpi-service').text(rows.filter(r => r.statut === 'en_service').length);
        $('#kpi-reparation').text(rows.filter(r => r.statut === 'en_reparation').length);
        $('#kpi-stock').text(rows.filter(r => r.statut === 'en_stock').length);
    });
}

const Wizard = (() => {
    let currentStep = 1;
    const $modal = () => $('#reseauModal');
    const $form = () => $('#reseauForm');
    const $step = (n) => $('#step-' + n);
    const $circle = (n) => $('.wizard-step-circle[data-step="' + n + '"]');
    const $line = (n) => $('.wizard-step-line[data-after="' + n + '"]');

    function isEnStock() { return $('input[name="statut"]:checked').val() === 'en_stock'; }
    function totalSteps() { return isEnStock() ? 2 : 3; }

    function goTo(n) {
        $step(currentStep).addClass('d-none');
        currentStep = n;
        $step(currentStep).removeClass('d-none');
        updateStepper();
        updateNav();
    }

    function updateStepper() {
        for (let i = 1; i <= 3; i++) {
            const c = $circle(i);
            c.removeClass('active done');
            if (i < currentStep) c.addClass('done').html('<i class="bi bi-check-lg"></i>');
            else if (i === currentStep) c.addClass('active').text(i);
            else c.text(i);
        }
    }

    function updateNav() {
        const last = totalSteps();
        $('#btn-prev').toggle(currentStep > 1);
        $('#btn-next').toggleClass('d-none', currentStep >= last);
        $('#btn-submit').toggleClass('d-none', currentStep < last);
    }

    function reset() {
        $form()[0].reset();
        $('#res_id').val('');
        $('.statut-card').removeClass('selected');
        $('#local_id').val('');
        $('#aff-local-summary').addClass('d-none');
        $('#btn-select-local-init').removeClass('d-none');
        goTo(1);
    }

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected').find('input').prop('checked', true);
        });

        $('#btn-next').on('click', () => goTo(currentStep + 1));
        $('#btn-prev').on('click', () => goTo(currentStep - 1));

        $form().on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-submit');
            $btn.prop('disabled', true).text('Enregistrement...');
            $.ajax({
                url: route(prefix + '.store'),
                method: 'POST',
                data: $(this).serialize(),
                success: (res) => {
                    if (res.success) {
                        $modal().modal('hide');
                        $('#imprimantes-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire('Succès', res.message, 'success');
                    }
                },
                error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error'),
                complete: () => $btn.prop('disabled', false).text('Enregistrer')
            });
        });

        // Local Selection Event
        $(document).on('local:selected', function(e, local) {
            $('#local_id').val(local.id);
            $('#local-summary-libelle').text(local.libelle);
            $('#aff-local-summary').removeClass('d-none');
            $('#btn-select-local-init').addClass('d-none');
        });
    }

    return { init, open: () => { reset(); $modal().modal('show'); } };
})();

function deleteImprimante(id) {
    Swal.fire({ title: 'Supprimer ?', icon: 'warning', showCancelButton: true }).then(res => {
        if (res.isConfirmed) {
            $.ajax({ url: route(prefix + '.destroy', id), method: 'DELETE', success: () => {
                $('#imprimantes-table').bootstrapTable('refresh');
                loadKpis();
            }});
        }
    });
}

$(function () {
    Wizard.init();
    $('#btn-add').on('click', () => Wizard.open());
    $('#btn-apply-filters').on('click', () => $('#imprimantes-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-type, #filter-site, #filter-statut').val('');
        $('#imprimantes-table').bootstrapTable('refresh');
    });
    loadKpis();
});
