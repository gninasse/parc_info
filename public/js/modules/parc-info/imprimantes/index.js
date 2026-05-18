/**
 * index.js — Imprimantes (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.imprimantesQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        statut: $('#filter-statut').val(),
        type_imprimante_id: $('#filter-type').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.imprimantes.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

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

window.actionsFormatter = (id) =>
    `<div class="d-flex gap-1">
        <a href="${route('parc-info.imprimantes.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteImprimante(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route('parc-info.imprimantes.data'), { limit: 9999, offset: 0 }, (res) => {
        const rows = res.rows ?? [];
        $('#kpi-total').text(res.total ?? 0);
        $('#kpi-service').text(rows.filter(r => r.statut === 'en_service').length);
        $('#kpi-reparation').text(rows.filter(r => r.statut === 'en_reparation').length);
        $('#kpi-stock').text(rows.filter(r => r.statut === 'en_stock').length);
    });
}

// ── Wizard ────────────────────────────────────────────────────────────────────

const Wizard = (() => {
    let currentStep = 1;

    const $modal = () => $('#imprimanteModal');
    const $form = () => $('#imprimanteForm');
    const $step = (n) => $(`#step-${n}`);
    const $circle = (n) => $(`.wizard-step-circle[data-step="${n}"]`);
    const $label = (n) => $(`.wizard-step-label[data-step="${n}"]`);
    const $line = (n) => $(`.wizard-step-line[data-after="${n}"]`);

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

        // Étape 3 : grisée si en_stock
        $circle(3).toggleClass('opacity-25', stock);
        $label(3).toggleClass('opacity-25 text-decoration-line-through', stock);
        $line(2).toggleClass('opacity-25', stock);

        for (let i = 1; i <= 3; i++) {
            const c = $circle(i);
            const l = $label(i);
            c.removeClass('active done');
            l.removeClass('text-primary fw-bold').addClass('text-muted');
            if (i < currentStep) {
                c.addClass('done').html(''); // Icon check via CSS
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
        const statut = $('input[name="statut"]:checked').val();
        
        $('#btn-prev').toggle(currentStep > 1);
        $('#btn-next').toggleClass('d-none', currentStep >= last);
        $('#btn-submit').toggleClass('d-none', currentStep < last);
        $('#btn-save-reparation').toggleClass('d-none', !(currentStep === 2 && statut === 'en_reparation'));
    }

    function validateStep(n) {
        if (n === 1) {
            if (!$('input[name="statut"]:checked').val()) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un statut.', timer: 2000, showConfirmButton: false });
                return false;
            }
        }
        if (n === 2) {
            const fields = ['numero_serie', 'modele'];
            let ok = true;
            fields.forEach(f => {
                const $el = $(`#${f}`);
                if (!$el.val()) { $el.addClass('is-invalid'); ok = false; }
                else $el.removeClass('is-invalid');
            });
            if (!ok) { Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir les champs obligatoires.', timer: 2500, showConfirmButton: false }); }
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#imp_id').val('');
        $('#wizard-title').text('Ajouter une imprimante');
        $('.statut-card').removeClass('selected');
        $('.aff-type-card').removeClass('selected');
        $('.aff-summary').addClass('d-none');
        $('#aff-skip-hint').removeClass('d-none');
        $('#dossier_employe_id, #poste_travail_id, #local_id').val('');
        $form().find('.is-invalid').removeClass('is-invalid');
        $form().find('.invalid-feedback').remove();
        goTo(1);
    }

    function open() {
        reset();
        $modal().modal('show');
    }

    function submitForm(formData, isFromSubmit) {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        const id = $('#imp_id').val();
        const url = id ? route('parc-info.imprimantes.update', id) : route('parc-info.imprimantes.store');
        const method = id ? 'PUT' : 'POST';
        const $btn = isFromSubmit ? $('#btn-submit') : $('#btn-save-reparation');
        
        $btn.prop('disabled', true);
        const originalText = isFromSubmit ? $('#btn-submit-label').text() : $btn.html();
        if (isFromSubmit) $('#btn-submit-label').text('Enregistrement...');
        else $btn.html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');

        $.ajax({
            url, method,
            data: formData,
            success: (res) => {
                if (res.success) {
                    $modal().modal('hide');
                    $('#imprimantes-table').bootstrapTable('refresh');
                    loadKpis();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                }
            },
            error: (xhr) => {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors ?? {};
                    let firstErrorField = null;
                    Object.entries(errors).forEach(([field, msgs]) => {
                        const $field = $(`#${field}`);
                        if ($field.length) {
                            $field.addClass('is-invalid');
                            $field.after(`<div class="invalid-feedback d-block">${msgs[0]}</div>`);
                            if (!firstErrorField) firstErrorField = $field;
                        }
                    });
                    if (firstErrorField) {
                        const errorStep = firstErrorField.closest('.wizard-step').attr('id').replace('step-', '');
                        goTo(parseInt(errorStep));
                    }
                    Swal.fire('Erreur de validation', 'Veuillez corriger les erreurs.', 'error');
                } else {
                    Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                }
            },
            complete: () => {
                $btn.prop('disabled', false);
                if (isFromSubmit) $('#btn-submit-label').text(originalText);
                else $btn.html(originalText);
            },
        });
    }

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            updateStepper();
            updateNav();
        });

        $('#btn-next').on('click', () => { if (validateStep(currentStep)) goTo(currentStep + 1); });
        $('#btn-prev').on('click', () => {
            if (currentStep === 3) {
                $('.aff-type-card').removeClass('selected');
                $('.aff-summary').addClass('d-none');
                $('#aff-skip-hint').removeClass('d-none');
                $('#dossier_employe_id, #poste_travail_id, #local_id').val('');
            }
            goTo(currentStep - 1);
        });

        $('#btn-save-reparation').on('click', () => {
            if (validateStep(2)) submitForm($form().serialize() + '&skip_affectation=1', false);
        });

        $form().on('submit', (e) => {
            e.preventDefault();
            let data = $form().serialize();
            if (!$('input[name="type_cible"]:checked').val()) data += '&skip_affectation=1';
            submitForm(data, true);
        });

        // Quick Adds
        function setupQuickAdd(btnId, title, placeholder, routeName, selectId) {
            $(btnId).on('click', () => {
                Swal.fire({
                    title, input: 'text', inputPlaceholder: placeholder, showCancelButton: true,
                    preConfirm: (v) => v ? v.trim() : Swal.showValidationMessage('Obligatoire')
                }).then((res) => {
                    if (res.isConfirmed) {
                        $.post(route(routeName), { libelle: res.value }, (d) => {
                            $(`#${selectId}`).append(new Option(d.data.libelle, d.data.id, true, true));
                            Swal.fire({ icon:'success', title:'Ajouté', timer:1500, showConfirmButton:false });
                        }).fail(() => Swal.fire('Erreur', 'Ce libellé existe déjà.', 'error'));
                    }
                });
            });
        }
        setupQuickAdd('#btn-add-marque', 'Nouvelle marque', 'Ex: HP, Epson...', 'parc-info.imprimantes.store-marque', 'marque_id');
        setupQuickAdd('#btn-add-type-imprimante', 'Nouvelle technologie', 'Ex: Laser, Jet d\'encre...', 'parc-info.imprimantes.store-type-imprimante', 'type_imprimante_id');

        // Selection Handlers (Step 3)
        $(document).on('employe:selected', function (e, emp) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="EMPLOYE"]').addClass('selected').find('input[type="radio"]').prop('checked', true);
            $('#emp-summary-nom').text(emp.nom_complet);
            $('#emp-summary-matricule').text(emp.matricule);
            $('#dossier_employe_id').val(emp.id);
            $('#poste_travail_id, #local_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-employe-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $(document).on('poste:selected', function (e, poste) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="POSTE"]').addClass('selected').find('input[type="radio"]').prop('checked', true);
            $('#poste-summary-code').text(poste.code);
            $('#poste-summary-libelle').text(poste.libelle);
            $('#poste_travail_id').val(poste.id);
            $('#dossier_employe_id, #local_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-poste-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $(document).on('local:selected', function (e, local) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="LOCAL"]').addClass('selected').find('input[type="radio"]').prop('checked', true);
            $('#local-summary-libelle').text(local.text);
            $('#local_id').val(local.id);
            $('#dossier_employe_id, #poste_travail_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-local-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $modal().on('hidden.bs.modal', reset);
    }

    return { init, open };
})();

// ── Actions ───────────────────────────────────────────────────────────────────

function deleteImprimante(id) {
    Swal.fire({
        title: 'Supprimer cet actif ?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', confirmButtonText: 'Supprimer'
    }).then((res) => {
        if (res.isConfirmed) {
            $.ajax({
                url: route('parc-info.imprimantes.destroy', id), method: 'DELETE',
                success: () => { $('#imprimantes-table').bootstrapTable('refresh'); loadKpis(); }
            });
        }
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────

$(function () {
    Wizard.init();
    $('#btn-add').on('click', () => Wizard.open());
    $('#btn-edit').on('click', () => {
        const sel = $('#imprimantes-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = route('parc-info.imprimantes.show', sel[0].id);
    });
    $('#btn-delete').on('click', () => {
        const sel = $('#imprimantes-table').bootstrapTable('getSelections');
        if (sel.length) deleteImprimante(sel[0].id);
    });
    $('#imprimantes-table').on('check.bs.table uncheck.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });
    $('#btn-apply-filters').on('click', () => $('#imprimantes-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-direction, #filter-statut, #filter-type').val('');
        $('#imprimantes-table').bootstrapTable('refresh');
    });
    loadKpis();
});
