/**
 * index.js — Mobiles & Tablettes (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.mobilesQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        type_mobile_id: $('#filter-type').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.mobiles.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

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
        <a href="${route('parc-info.mobiles.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Détails"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteMobile(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route('parc-info.mobiles.data'), { limit: 9999, offset: 0 }, (res) => {
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

    const $modal = () => $('#mobileModal');
    const $form = () => $('#mobileForm');
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

        // Étape 3 visibilité
        $circle(3).toggleClass('opacity-25', stock);
        $label(3).toggleClass('opacity-25 text-decoration-line-through', stock);
        $line(2).toggleClass('opacity-25', stock);

        for (let i = 1; i <= 3; i++) {
            const c = $circle(i);
            const l = $label(i);
            c.removeClass('active done');
            l.removeClass('text-primary fw-bold').addClass('text-muted');

            if (i < currentStep) {
                c.addClass('done').text('');
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
            if (!ok) { Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir les informations obligatoires.', timer: 2500, showConfirmButton: false }); }
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#mob_id').val('');
        $('#wizard-title').text('Ajouter un équipement mobile');
        $('#btn-submit-label').text('Enregistrer l\'actif');
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

    async function openEdit(id) {
        reset();
        $('#wizard-title').text('Modifier l\'équipement mobile');
        $('#btn-submit-label').text('Enregistrer les modifications');
        try {
            const res = await $.get(route('parc-info.mobiles.show-json', id));
            const e = res.data;
            const m = e.mobile ?? {};
            const aff = e.affectation_active;

            $('#mob_id').val(e.id);
            $(`.statut-card[data-value="${e.statut}"]`).trigger('click');
            goTo(2);

            $('#code_inventaire').val(e.code_inventaire);
            $('#numero_serie').val(e.numero_serie);
            $('#marque_id').val(e.marque_id);
            $('#modele').val(e.modele);
            $('#type_mobile_id').val(m.type_mobile_id);
            $('#version_os').val(m.version_os);
            $('#imei_1').val(m.imei_1);
            $('#imei_2').val(m.imei_2);
            $('#num_tel_associe').val(m.num_tel_associe);
            $('#statut_mdm').val(m.statut_mdm);
            $('#capacite_batterie_mah').val(m.capacite_batterie_mah);
            $('#etat_ecran').val(m.etat_ecran);
            $('#etat').val(e.etat);
            $('#date_acquisition').val(e.date_acquisition?.substring(0, 10));
            $('#date_fin_garantie').val(e.date_fin_garantie?.substring(0, 10));
            $('#a_coque_protection').prop('checked', !!m.a_coque_protection);

            if (aff && !isEnStock()) {
                goTo(3);
                $(`.aff-type-card[data-value="${aff.type_cible}"]`).trigger('click');
                $(document).trigger(`${aff.type_cible.toLowerCase()}:selected`, [aff[aff.type_cible.toLowerCase()]]);
            }
            $modal().modal('show');
        } catch {
            Swal.fire('Erreur', 'Impossible de charger les données du mobile.', 'error');
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
            const formData = $form().serialize() + '&skip_affectation=1';
            submitForm(formData, false);
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
                $('#dossier_employe_id, #poste_travail_id, #local_id').val('');
            }
            goTo(currentStep - 1);
        });

        // Événements Bus (selection_modals.js)
        $(document).on('employe:selected', function (e, emp) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="EMPLOYE"]').addClass('selected').find('input[type="radio"]').prop('checked', true);
            $('#emp-summary-nom').text(emp.nom || `${emp.nom} ${emp.prenom}`);
            $('#emp-summary-matricule').text(emp.matricule);
            $('#emp-summary-poste').text(emp.poste || '—');
            $('#emp-summary-rattachement').text(emp.rattachement || '—');
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
            $('#poste-summary-emplacement').text(poste.emplacement || '—');
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
            $('#local-summary-code').text(local.code || '—');
            $('#local-summary-libelle').text(local.libelle);
            $('#local-summary-complet').text(local.text || local.nom_complet);
            $('#local_id').val(local.id);
            $('#dossier_employe_id, #poste_travail_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-local-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        // Quick Add Marque
        $('#btn-add-marque').on('click', function () {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('mobileModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title: 'Nouvelle marque',
                input: 'text',
                showCancelButton: true,
                confirmButtonText: 'Ajouter',
                preConfirm: (libelle) => {
                    if (!libelle) { Swal.showValidationMessage('Le libellé est requis'); return; }
                    return $.post(route('parc-info.mobiles.store-marque'), { libelle, _token: $('meta[name="csrf-token"]').attr('content') });
                }
            }).then(res => {
                if (bsModal) bsModal._focustrap?.activate();
                if (res.isConfirmed && res.value?.success) {
                    const mq = res.value.data;
                    $('#marque_id').append(`<option value="${mq.id}" selected>${mq.libelle}</option>`).val(mq.id);
                }
            });
        });

        // Quick Add Type Mobile
        $('#btn-add-type-mobile').on('click', function () {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('mobileModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title: 'Nouveau type de mobile',
                input: 'text',
                inputPlaceholder: 'Ex: Tablette, Smartphone...',
                showCancelButton: true,
                confirmButtonText: 'Ajouter',
                preConfirm: (libelle) => {
                    if (!libelle) { Swal.showValidationMessage('Le libellé est requis'); return; }
                    return $.post(route('parc-info.mobiles.store-type-mobile'), { libelle, _token: $('meta[name="csrf-token"]').attr('content') });
                }
            }).then(res => {
                if (bsModal) bsModal._focustrap?.activate();
                if (res.isConfirmed && res.value?.success) {
                    const t = res.value.data;
                    $('#type_mobile_id').append(`<option value="${t.id}" selected>${t.libelle}</option>`).val(t.id);
                }
            });
        });

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

            const id = $('#mob_id').val();
            const url = id ? route('parc-info.mobiles.update', id) : route('parc-info.mobiles.store');
            const method = id ? 'PUT' : 'POST';
            const $btn = isFromSubmit ? $('#btn-submit') : $('#btn-save-reparation');
            const originalText = $btn.find('span').text() || $btn.text();

            $btn.prop('disabled', true);
            if (isFromSubmit) { $btn.find('#btn-submit-label').text('Traitement...'); }
            else { $btn.html('<i class="bi bi-hourglass-split me-1"></i> Traitement...'); }

            $.ajax({
                url, method, data: formData,
                success: (res) => {
                    if (res.success) {
                        $modal().modal('hide');
                        $('#mobiles-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors ?? {};
                        let firstField = null;
                        Object.entries(errors).forEach(([field, msgs]) => {
                            const $field = $(`[name="${field}"]`);
                            if ($field.length) {
                                $field.addClass('is-invalid').after(`<div class="invalid-feedback d-block">${msgs[0]}</div>`);
                                if (!firstField) firstField = $field;
                            }
                        });
                        if (firstField) {
                            const stepDiv = firstField.closest('.wizard-step');
                            if (stepDiv.length) {
                                const errStep = parseInt(stepDiv.attr('id').replace('step-', ''));
                                goTo(errStep);
                            }
                        }
                    } else {
                        Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false);
                    if (isFromSubmit) { $btn.find('#btn-submit-label').text(originalText); }
                    else { $btn.html('<i class="bi bi-tools me-1"></i> Enregistrer en réparation'); }
                }
            });
        }

        $modal().on('hidden.bs.modal', reset);
    }

    return { init, open, openEdit };
})();

// ── Suppression ───────────────────────────────────────────────────────────────

function deleteMobile(id) {
    Swal.fire({
        title: 'Supprimer cet équipement ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route('parc-info.mobiles.destroy', id),
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        $('#mobiles-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire({ icon: 'success', title: 'Supprimé !', timer: 1500, showConfirmButton: false });
                    }
                },
                error: () => Swal.fire('Erreur', 'Impossible de supprimer cet actif.', 'error'),
            });
        }
    });
}

// ── Initialisation ────────────────────────────────────────────────────────────

$(function () {
    Wizard.init();

    $('#btn-add').on('click', () => Wizard.open());

    $('#btn-edit').on('click', () => {
        const sel = $('#mobiles-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = route('parc-info.mobiles.show', sel[0].id);
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#mobiles-table').bootstrapTable('getSelections');
        if (sel.length) deleteMobile(sel[0].id);
    });

    $('#mobiles-table').on('check.bs.table uncheck.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#btn-apply-filters').on('click', () => $('#mobiles-table').bootstrapTable('refresh'));

    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-direction, #filter-type, #filter-statut').val('');
        $('#mobiles-table').bootstrapTable('refresh');
    });

    $('#mobiles-table').on('load-success.bs.table', () => {
        $('#btn-edit, #btn-delete').prop('disabled', true);
    });

    loadKpis();
});
