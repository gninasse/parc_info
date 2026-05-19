/**
 * index.js — Baies & Racks (ParcInfo)
 */

window.racksQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.racks.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

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
        <a href="${route('parc-info.racks.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (_e, _val, row) => deleteRack(row.id),
};

function loadKpis() {
    $.get(route('parc-info.racks.data'), { limit: 9999, offset: 0 }, (res) => {
        const rows = res.rows ?? [];
        $('#kpi-total').text(res.total ?? 0);
        $('#kpi-service').text(rows.filter(r => r.statut === 'en_service').length);
        $('#kpi-reparation').text(rows.filter(r => r.statut === 'en_reparation').length);
        $('#kpi-stock').text(rows.filter(r => r.statut === 'en_stock').length);
    });
}

const Wizard = (() => {
    let currentStep = 1;

    const $modal = () => $('#rackModal');
    const $form = () => $('#rackForm');
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
        const last = totalSteps();
        const statut = $('input[name="statut"]:checked').val();

        $('#btn-prev').toggle(currentStep > 1);
        $('#btn-next').toggleClass('d-none', currentStep >= last);
        $('#btn-submit').toggleClass('d-none', currentStep < last);
        $('#btn-save-reparation').toggleClass('d-none', !(currentStep === 2 && statut === 'en_reparation'));
    }

    function validateStep(n) {
        if (n === 1 && !$('input[name="statut"]:checked').val()) {
            Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un statut.', timer: 2000, showConfirmButton: false });
            return false;
        }

        if (n === 2) {
            const fields = ['numero_serie', 'modele'];
            let ok = true;
            fields.forEach(f => {
                const $el = $(`#${f}`);
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
        $('#rack_id').val('');
        $('#wizard-title').text('Ajouter une baie/rack');
        $('#btn-submit-label').text('Enregistrer l\'actif');
        $('.statut-card, .aff-type-card').removeClass('selected').find('.check-icon').addClass('d-none');
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

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected').find('.check-icon').addClass('d-none');
            $(this).addClass('selected').find('.check-icon').removeClass('d-none');
            $(this).find('input').prop('checked', true);
            updateStepper();
            updateNav();
        });

        $('#btn-next').on('click', () => {
            if (validateStep(currentStep)) goTo(currentStep + 1);
        });

        $('#btn-prev').on('click', () => {
            if (currentStep === 3) {
                $('input[name="type_cible"]').prop('checked', false);
                $('.aff-type-card').removeClass('selected').find('.check-icon').addClass('d-none');
                $('.aff-summary').addClass('d-none');
                $('#aff-skip-hint').removeClass('d-none');
                $('#local_id').val('');
            }
            goTo(currentStep - 1);
        });

        $(document).on('local:selected', function (_e, local) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected').find('.check-icon').addClass('d-none');
            $('.aff-type-card[data-value="LOCAL"]').addClass('selected')
                .find('input[type="radio"]').prop('checked', true).end()
                .find('.check-icon').removeClass('d-none');
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

        function quickAddMarque() {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('rackModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title: 'Nouvelle marque',
                input: 'text',
                inputPlaceholder: 'Ex: APC, Eaton, Rittal...',
                showCancelButton: true,
                confirmButtonText: 'Ajouter',
                cancelButtonText: 'Annuler',
                didOpen: () => setTimeout(() => Swal.getInput()?.focus(), 50),
                preConfirm: (value) => {
                    if (!value?.trim()) {
                        Swal.showValidationMessage('Le libellé est obligatoire.');
                        return false;
                    }
                    return value.trim();
                },
            }).then((result) => {
                if (bsModal) bsModal._focustrap?.activate();
                if (result.isConfirmed && result.value) {
                    $.post(route('parc-info.racks.store-marque'), { libelle: result.value }, (res) => {
                        if (res.success) {
                            $('#marque_id').append(new Option(res.data.libelle, res.data.id, true, true));
                            Swal.fire({ icon: 'success', title: 'Marque ajoutée', timer: 1500, showConfirmButton: false });
                        }
                    }).fail((xhr) => {
                        Swal.fire('Erreur', xhr.responseJSON?.errors?.libelle?.[0] ?? 'Cette marque existe déjà.', 'error');
                    });
                }
            });
        }

        $('#btn-add-marque').on('click', quickAddMarque);

        $('#btn-save-reparation').on('click', function (e) {
            e.preventDefault();
            if (!validateStep(2)) return;
            submitForm($form().serialize() + '&skip_affectation=1', false);
        });

        $form().on('submit', function (e) {
            e.preventDefault();

            const statut = $('input[name="statut"]:checked').val();
            const localId = $('#local_id').val();
            let formData = $form().serialize();

            if (statut === 'en_service' && !localId) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un local d\'installation.', timer: 2500, showConfirmButton: false });
                return;
            }

            if (!localId) {
                formData += '&skip_affectation=1';
            }

            submitForm(formData, true);
        });

        function submitForm(formData, isFromSubmit) {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const url = route('parc-info.racks.store');
            const $btn = isFromSubmit ? $('#btn-submit') : $('#btn-save-reparation');
            const originalText = isFromSubmit ? $('#btn-submit-label').text() : $btn.text();

            $btn.prop('disabled', true);
            if (isFromSubmit) {
                $('#btn-submit-label').text('Enregistrement...');
            } else {
                $btn.html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');
            }

            $.ajax({
                url,
                method: 'POST',
                data: formData,
                success: (res) => {
                    if (res.success) {
                        $modal().modal('hide');
                        $('#racks-table').bootstrapTable('refresh');
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
                                if (!$field.next('.invalid-feedback').length) {
                                    $field.after(`<div class="invalid-feedback d-block">${msgs[0]}</div>`);
                                }
                                if (!firstErrorField) firstErrorField = $field;
                            }
                        });

                        if (firstErrorField) {
                            const errorStep = parseInt(firstErrorField.closest('.wizard-step').attr('id').replace('step-', ''), 10);
                            goTo(errorStep);
                            firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }

                        Swal.fire('Erreur de validation', 'Veuillez corriger les erreurs du formulaire.', 'error');
                    } else {
                        Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false);
                    if (isFromSubmit) {
                        $('#btn-submit-label').text(originalText);
                    } else {
                        $btn.html('<i class="bi bi-tools me-1"></i> Enregistrer en réparation');
                    }
                },
            });
        }

        $modal().on('hidden.bs.modal', reset);
    }

    return { init, open };
})();

function deleteRack(id) {
    Swal.fire({
        title: 'Supprimer cette baie / ce rack ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route('parc-info.racks.destroy', id),
                method: 'DELETE',
                success: (res) => {
                    if (res.success) {
                        $('#racks-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire({ icon: 'success', title: 'Supprimé', timer: 1500, showConfirmButton: false });
                    }
                },
                error: () => Swal.fire('Erreur', 'Impossible de supprimer.', 'error'),
            });
        }
    });
}

$(function () {
    Wizard.init();

    $('#btn-add').on('click', () => Wizard.open());

    $('#btn-edit').on('click', () => {
        const sel = $('#racks-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = route('parc-info.racks.show', sel[0].id);
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#racks-table').bootstrapTable('getSelections');
        if (sel.length) deleteRack(sel[0].id);
    });

    $('#racks-table').on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#btn-apply-filters').on('click', () => $('#racks-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-direction, #filter-statut').val('');
        $('#racks-table').bootstrapTable('refresh');
    });

    loadKpis();
});
