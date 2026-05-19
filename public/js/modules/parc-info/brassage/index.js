/**
 * index.js — Panneaux de Brassage (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.brassageQueryParams = function (params) {
    return Object.assign(params, {
        site_id:      $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        statut:       $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.brassage.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

window.statutFormatter = (val) => {
    const map = {
        en_service:    ['success',   'EN SERVICE'],
        en_stock:      ['secondary', 'EN STOCK'],
        en_reparation: ['warning',   'EN RÉPARATION'],
        perdu:         ['danger',    'PERDU / VOLÉ'],
        reforme:       ['dark',      'RÉFORMÉ'],
    };
    const [color, label] = map[val] ?? ['info', val];
    return `<span class="badge bg-${color}-subtle text-${color} border border-${color}-subtle px-2 py-1">${label}</span>`;
};

window.actionsFormatter = (id) =>
    `<div class="d-flex gap-1">
        <a href="${route('parc-info.brassage.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteBrassage(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route('parc-info.brassage.data'), { limit: 9999, offset: 0 }, (res) => {
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
    const TOTAL = 3;

    const $modal  = () => $('#brassageModal');
    const $form   = () => $('#brassageForm');
    const $step   = (n) => $(`#step-${n}`);
    const $circle = (n) => $(`.wizard-step-circle[data-step="${n}"]`);
    const $label  = (n) => $(`.wizard-step-label[data-step="${n}"]`);
    const $line   = (n) => $(`.wizard-step-line[data-after="${n}"]`);

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
                c.addClass('done').html('<i class="bi bi-check-lg" style="font-size:.8rem"></i>');
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
        const last   = totalSteps();
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
            const fields = ['numero_serie', 'modele', 'nb_ports'];
            let ok = true;
            fields.forEach(f => {
                const $el = $(`#${f}`);
                if (!$el.val()) { $el.addClass('is-invalid'); ok = false; }
                else $el.removeClass('is-invalid');
            });
            if (!ok) { Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir tous les champs obligatoires.', timer: 2500, showConfirmButton: false }); }
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#bras_id').val('');
        $('#wizard-title').text('Ajouter un panneau de brassage');
        $('#btn-submit-label').text("Enregistrer l'actif");
        $('.statut-card').removeClass('selected');
        $('.aff-type-card').removeClass('selected');
        $('.aff-summary').addClass('d-none');
        $('#aff-skip-hint').removeClass('d-none');
        $('#local_id').val('');
        $form().find('.is-invalid').removeClass('is-invalid');
        $form().find('.invalid-feedback').remove();
        goTo(1);
    }

    function open() {
        reset();
        $modal().modal('show');
    }

    async function openEdit(id) {
        reset();
        $('#wizard-title').text('Modifier le panneau de brassage');
        $('#btn-submit-label').text('Enregistrer');
        try {
            const res = await $.get(route('parc-info.brassage.show-json', id));
            const e   = res.data;
            const inf = e.infrastructure ?? {};
            const aff = e.affectation_active;

            $('#bras_id').val(e.id);
            $(`.statut-card[data-value="${e.statut}"]`).trigger('click');
            goTo(2);
            $('#code_inventaire').val(e.code_inventaire);
            $('#numero_serie').val(e.numero_serie);
            $('#marque_id').val(e.marque_id);
            $('#modele').val(e.modele);
            $('#date_acquisition').val(e.date_acquisition?.substring(0, 10));
            $('#etat').val(e.etat);
            $('#nb_ports').val(inf.nb_ports);
            $('#categorie_cable').val(inf.categorie_cable);
            $('#type_connecteur').val(inf.type_connecteur);
            $('#u_taille').val(inf.u_taille);

            if (aff && !isEnStock()) {
                goTo(3);
                $('.aff-type-card[data-value="LOCAL"]').addClass('selected')
                    .find('input[type="radio"]').prop('checked', true);
                if (aff.local) {
                    $('#local_id').val(aff.local_id);
                    $('#local-summary-libelle').text(aff.local.libelle ?? '');
                    $('#local-summary-code').text(aff.local.code ?? '');
                    $('#aff-local-summary').removeClass('d-none');
                    $('#aff-skip-hint').addClass('d-none');
                }
            }
            $modal().modal('show');
        } catch {
            Swal.fire('Erreur', 'Impossible de charger les données.', 'error');
        }
    }

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            updateStepper();
            updateNav();
        });

        $('#btn-save-reparation').on('click', function (e) {
            e.preventDefault();
            if (!validateStep(2)) return;
            submitForm($form().serialize() + '&skip_affectation=1', false);
        });

        $('#btn-next').on('click', () => {
            if (validateStep(currentStep)) goTo(currentStep + 1);
        });

        $('#btn-prev').on('click', () => {
            if (currentStep === 3) {
                $('input[name="type_cible"]').prop('checked', false);
                $('.aff-type-card').removeClass('selected');
                $('.aff-summary').addClass('d-none');
                $('#aff-skip-hint').removeClass('d-none');
                $('#local_id').val('');
            }
            goTo(currentStep - 1);
        });

        // ── Écoute LOCAL uniquement ───────────────────────────────────────────
        $(document).on('local:selected', function (e, local) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="LOCAL"]').addClass('selected')
                .find('input[type="radio"]').prop('checked', true);
            $('#local-summary-code').text(local.code);
            $('#local-summary-libelle').text(local.libelle);
            $('#local-summary-type').text(local.type);
            $('#local-summary-etage').text(local.etage);
            $('#local-summary-batiment').text(local.batiment);
            $('#local_id').val(local.id);
            $('.aff-summary').addClass('d-none');
            $('#aff-local-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        // Activer la carte LOCAL au clic
        $(document).on('click', '.aff-type-card', function () {
            $('.aff-type-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
        });

        // ── QuickAdd marque ───────────────────────────────────────────────────
        $('#btn-add-marque').on('click', () => {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('brassageModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title: 'Nouvelle marque',
                input: 'text',
                inputPlaceholder: 'Ex: Nexans, Legrand, Panduit...',
                showCancelButton: true,
                confirmButtonText: 'Ajouter',
                cancelButtonText: 'Annuler',
                didOpen: () => setTimeout(() => Swal.getInput()?.focus(), 50),
                preConfirm: (value) => {
                    if (!value?.trim()) { Swal.showValidationMessage('Le libellé est obligatoire.'); return false; }
                    return value.trim();
                },
            }).then((result) => {
                if (bsModal) bsModal._focustrap?.activate();
                if (result.isConfirmed && result.value) {
                    $.post(route('parc-info.brassage.store-marque'), { libelle: result.value }, (res) => {
                        if (res.success) {
                            $('#marque_id').append(new Option(res.data.libelle, res.data.id, true, true));
                            Swal.fire({ icon: 'success', title: 'Marque ajoutée', timer: 1500, showConfirmButton: false });
                        }
                    }).fail((xhr) => {
                        if (bsModal) bsModal._focustrap?.activate();
                        Swal.fire('Erreur', xhr.responseJSON?.errors?.libelle?.[0] ?? 'Cette marque existe déjà.', 'error');
                    });
                }
            });
        });

        // ── Soumission ────────────────────────────────────────────────────────
        $form().on('submit', async (e) => {
            e.preventDefault();
            let formData = $form().serialize();
            if (!$('input[name="type_cible"]:checked').val()) {
                formData += '&skip_affectation=1';
            }
            submitForm(formData, true);
        });

        function submitForm(formData, isFromSubmit) {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const id     = $('#bras_id').val();
            const url    = id ? route('parc-info.brassage.update', id) : route('parc-info.brassage.store');
            const method = id ? 'PUT' : 'POST';
            const $btn   = isFromSubmit ? $('#btn-submit') : $('#btn-save-reparation');
            const origText = $btn.find('span').text() || $btn.text();

            $btn.prop('disabled', true);
            if (isFromSubmit) {
                $btn.find('#btn-submit-label').text('Enregistrement...');
            } else {
                $btn.html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');
            }

            $.ajax({
                url, method, data: formData,
                success: (res) => {
                    if (res.success) {
                        $modal().modal('hide');
                        $('#brassage-table').bootstrapTable('refresh');
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
                            firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        Swal.fire('Erreur de validation', 'Veuillez corriger les erreurs dans le formulaire.', 'error');
                    } else {
                        Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false);
                    if (isFromSubmit) {
                        $btn.find('#btn-submit-label').text(origText);
                    } else {
                        $btn.html('<i class="bi bi-tools me-1"></i> Enregistrer en réparation');
                    }
                },
            });
        }

        $modal().on('hidden.bs.modal', reset);
    }

    return { init, open, openEdit };
})();

// ── Suppression ───────────────────────────────────────────────────────────────

function deleteBrassage(id) {
    Swal.fire({
        title: 'Supprimer ce panneau de brassage ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route('parc-info.brassage.destroy', id),
                method: 'DELETE',
                success: (res) => {
                    if (res.success) {
                        $('#brassage-table').bootstrapTable('refresh');
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
        const sel = $('#brassage-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = route('parc-info.brassage.show', sel[0].id);
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#brassage-table').bootstrapTable('getSelections');
        if (sel.length) deleteBrassage(sel[0].id);
    });

    $('#brassage-table').on('check.bs.table uncheck.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#btn-apply-filters').on('click', () => $('#brassage-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-direction, #filter-statut').val('');
        $('#brassage-table').bootstrapTable('refresh');
    });

    $('#brassage-table').on('load-success.bs.table', () => {
        $('#btn-edit, #btn-delete').prop('disabled', true);
    });

    loadKpis();
});
