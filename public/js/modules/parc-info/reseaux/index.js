// ── Table config & formatters ───────────────────────────────────────────────────

const prefix = window.routePrefix || 'parc-info.switches';

window.reseauxQueryParams = (params) => {
    return Object.assign(params, {
        type_reseau_id: $('#filter-type').val(),
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
    return `<span class="badge bg-${color}-subtle text-${color} border border-${color}-subtle px-2 py-1">${label}</span>`;
};

window.actionsFormatter = (id) => `
    <div class="d-flex gap-1">
        <a href="${route(prefix + '.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': function (e, value, row, index) {
        deleteReseau(row.id);
    }
};

// ── KPI ────────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route(prefix + '.data'), { limit: 9999, offset: 0 }, (res) => {
        const rows = res.rows ?? [];
        $('#kpi-total').text(res.total ?? 0);
        $('#kpi-service').text(rows.filter(r => r.statut === 'en_service').length);
        $('#kpi-reparation').text(rows.filter(r => r.statut === 'en_reparation').length);
        $('#kpi-stock').text(rows.filter(r => r.statut === 'en_stock').length);
    });
}

// ── Wizard Logic ───────────────────────────────────────────────────────────────

const Wizard = (() => {
    let currentStep = 1;

    const $modal = () => $('#reseauModal');
    const $form = () => $('#reseauForm');
    const $step = (n) => $('#step-' + n);
    const $circle = (n) => $('.wizard-step-circle[data-step="' + n + '"]');
    const $label = (n) => $('.wizard-step-label[data-step="' + n + '"]');
    const $line = (n) => $('.wizard-step-line[data-after="' + n + '"]');

    function isEnStock() {
        return $('input[name="statut"]:checked').val() === 'en_stock';
    }

    function totalSteps() {
        return isEnStock() ? 2 : 3;
    }

    function goTo(n) {
        $step(currentStep).addClass('d-none');
        currentStep = n;
        $step(currentStep).removeClass('d-none');
        updateStepper();
        updateNav();
    }

    function updateStepper() {
        const stock = isEnStock();

        $circle(3).toggleClass('opacity-25', stock);
        $label(3).toggleClass('opacity-25 text-decoration-line-through', stock);
        $line(2).toggleClass('opacity-25', stock);

        for (let i = 1; i <= 3; i++) {
            const c = $circle(i);
            const l = $label(i);

            c.removeClass('active done');
            l.removeClass('text-primary fw-bold').addClass('text-muted');

            if (i < currentStep) {
                c.addClass('done').html('<i class="bi bi-check-lg" style="font-size: .8rem"></i>');
                $line(i).addClass('done');
            } else if (i === currentStep) {
                c.addClass('active').text(i);
                l.removeClass('text-muted').addClass('text-primary fw-bold');
            } else {
                c.text(i);
                $line(i).removeClass('done');
            }
        }
    }

    function updateNav() {
        const last = totalSteps();
        $('#btn-prev').toggle(currentStep > 1);
        $('#btn-next').toggleClass('d-none', currentStep >= last);
        $('#btn-submit').toggleClass('d-none', currentStep < last);
    }

    function validateStep(n) {
        if (n === 1) {
            if (!$('input[name="statut"]:checked').val()) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un statut initial.', timer: 2000, showConfirmButton: false });
                return false;
            }

            const fields = ['numero_serie', 'modele', 'etat'];
            let ok = true;
            fields.forEach(f => {
                const $el = $('#' + f);
                if (!$el.val()) {
                    $el.addClass('is-invalid');
                    ok = false;
                } else {
                    $el.removeClass('is-invalid');
                }
            });
            if (!ok) {
                Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir tous les champs obligatoires.', timer: 2500, showConfirmButton: false });
            }
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#res_id').val('');
        $('#wizard-title').text('Ajouter un équipement réseau');
        $('#btn-submit-label').text('Enregistrer l\'équipement');

        $('.statut-card').removeClass('selected');
        $('.aff-type-card').removeClass('selected');

        $('.aff-summary').addClass('d-none');
        $('#aff-skip-hint').removeClass('d-none');

        $('#local_id').val('');

        $form().find('.is-invalid').removeClass('is-invalid');

        goTo(1);
    }

    function open() {
        reset();
        $modal().modal('show');
    }

    function quickAdd(title, placeholder, routeName, selectId) {
        const bsModal = bootstrap.Modal.getInstance(document.getElementById('reseauModal'));
        if (bsModal) bsModal._focustrap?.deactivate();

        Swal.fire({
            title: title,
            input: 'text',
            inputPlaceholder: placeholder,
            showCancelButton: true,
            confirmButtonText: 'Ajouter',
            cancelButtonText: 'Annuler',
            didOpen: () => {
                setTimeout(() => Swal.getInput()?.focus(), 50);
            },
            preConfirm: (value) => {
                if (!value || !value.trim()) {
                    Swal.showValidationMessage('Le libellé est obligatoire.');
                    return false;
                }
                return value.trim();
            },
        }).then((result) => {
            if (bsModal) bsModal._focustrap?.activate();
            if (result.isConfirmed && result.value) {
                $.post(route(routeName), { libelle: result.value }, (res) => {
                    if (res.success) {
                        $('#' + selectId).append(new Option(res.data.libelle, res.data.id, true, true));
                        Swal.fire({ icon: 'success', title: 'Ajouté avec succès', timer: 1500, showConfirmButton: false });
                    }
                }).fail((xhr) => {
                    if (bsModal) bsModal._focustrap?.activate();
                    Swal.fire('Erreur', xhr.responseJSON?.errors?.libelle?.[0] ?? 'Ce libellé existe déjà.', 'error');
                });
            }
        });
    }

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input').prop('checked', true);
        });

        $(document).on('click', '.aff-type-card', function () {
            $('.aff-type-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input').prop('checked', true);
            const type = $(this).find('input').val();
            $('.aff-summary').addClass('d-none');
            $('#aff-summary-' + type.toLowerCase()).removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $('#btn-next').on('click', () => {
            if (validateStep(currentStep)) goTo(currentStep + 1);
        });

        $('#btn-prev').on('click', () => goTo(currentStep - 1));

        $('#btn-submit').on('click', () => {
            const $btn = $('#btn-submit');
            const url = route(prefix + '.store');
            const method = 'POST';
            const formData = $form().serialize();

            $btn.prop('disabled', true).find('#btn-submit-label').text('Enregistrement...');

            $.ajax({
                url, method,
                data: formData,
                success: (res) => {
                    if (res.success) {
                        $modal().modal('hide');
                        $('#reseaux-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors ?? {};
                        Object.entries(errors).forEach(([field, msgs]) => {
                            $('#' + field).addClass('is-invalid').after(`<div class="invalid-feedback d-block">${msgs[0]}</div>`);
                        });
                        Swal.fire('Erreur de validation', 'Veuillez corriger les erreurs en surbrillance.', 'error');
                    } else {
                        Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).find('#btn-submit-label').text('Enregistrer l\'équipement');
                },
            });
        });

        $modal().on('hidden.bs.modal', reset);

        $('#btn-add-type-reseau').on('click', () => quickAdd('Ajouter un type de réseau', 'ex: Fibre optique, WiFi...', prefix + '.store-type', 'type_reseau_id'));
    }

    return { init, open };
})();

// ── Suppression ───────────────────────────────────────────────────────────────

function deleteReseau(id) {
    Swal.fire({
        title: 'Supprimer cet équipement ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route(prefix + '.destroy', id),
                method: 'DELETE',
                success: (res) => {
                    if (res.success) {
                        $('#reseaux-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire({ icon: 'success', title: 'Supprimé', timer: 1500, showConfirmButton: false });
                    }
                },
                error: () => Swal.fire('Erreur', 'Impossible de supprimer.', 'error'),
            });
        }
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────

$(function () {
    Wizard.init();

    $('#btn-add').on('click', () => Wizard.open());

    $('#btn-edit').on('click', () => {
        const sel = $('#reseaux-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = route(prefix + '.show', sel[0].id);
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#reseaux-table').bootstrapTable('getSelections');
        if (sel.length) deleteReseau(sel[0].id);
    });

    $('#reseaux-table').on('check.bs.table uncheck.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#reseaux-table').on('load-success.bs.table', () => {
        $('#btn-edit, #btn-delete').prop('disabled', true);
    });

    $('#btn-apply-filters').on('click', () => $('#reseaux-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-type, #filter-site, #filter-statut').val('');
        $('#reseaux-table').bootstrapTable('refresh');
    });

    loadKpis();
});
