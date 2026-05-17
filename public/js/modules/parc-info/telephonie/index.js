/**
 * Gestion de la Téléphonie - Module Parc Info
 */

const prefix = "parc-info.telephonie";

window.telephonieQueryParams = (params) => {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) => `<a href="${route(prefix + '.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;
window.statutFormatter = (val) => `<span class="badge bg-secondary small">${val}</span>`;
window.actionsFormatter = (id) => `<a href="${route(prefix + '.show', id)}" class="btn btn-sm btn-light border-0"><i class="bi bi-eye"></i></a>`;

const Wizard = (() => {
    let currentStep = 1;
    const $modal = () => $('#reseauModal');
    const $form = () => $('#reseauForm');
    const goTo = (n) => {
        $('.wizard-step').addClass('d-none');
        $(`#step-${n}`).removeClass('d-none');
        currentStep = n;
        $('.wizard-step-circle').removeClass('active');
        $(`.wizard-step-circle[data-step="${n}"]`).addClass('active');
        $('#btn-prev').toggle(n > 1);
        $('#btn-next').toggleClass('d-none', n === 3);
        $('#btn-submit').toggleClass('d-none', n < 3);
    };
    return {
        init: () => {
            $('.statut-card').on('click', function() { $(this).find('input').prop('checked', true); $('.statut-card').removeClass('selected'); $(this).addClass('selected'); });
            $('#btn-next').on('click', () => goTo(currentStep + 1));
            $('#btn-prev').on('click', () => goTo(currentStep - 1));
            $form().on('submit', function(e) {
                e.preventDefault();
                $.ajax({ url: route(prefix + '.store'), method: 'POST', data: $(this).serialize(), success: () => { $modal().modal('hide'); $('#telephonie-table').bootstrapTable('refresh'); } });
            });
            $(document).on('local:selected', (e, local) => { $('#local_id').val(local.id); $('#local-summary-libelle').text(local.libelle); });
        },
        open: () => { $form()[0].reset(); goTo(1); $modal().modal('show'); }
    };
})();

$(function() {
    Wizard.init();
    $('#btn-add').on('click', () => Wizard.open());
    $('#btn-apply-filters').on('click', () => $('#telephonie-table').bootstrapTable('refresh'));
});
