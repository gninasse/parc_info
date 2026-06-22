/**
 * dynamic-index.js — Generic index logic for dynamic ParcInfo categories
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.equipementsQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route(window.parcInfoConfig.routePrefix + '.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

window.statutFormatter = (val) => {
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

window.actionsFormatter = (id) =>
    `<div class="d-flex gap-1">
        <a href="${route(window.parcInfoConfig.routePrefix + '.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteEquipement(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route(window.parcInfoConfig.routePrefix + '.data'), { limit: 9999, offset: 0 }, (res) => {
        const rows = res.rows ?? [];
        $('#kpi-total').text(res.total ?? 0);
        $('#kpi-service').text(rows.filter(r => r.statut === 'en_service').length);
        $('#kpi-reparation').text(rows.filter(r => r.statut === 'en_reparation').length);
        $('#kpi-stock').text(rows.filter(r => r.statut && r.statut.startsWith('en_stock')).length);
    });
}

function deleteEquipement(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est définitive !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer !',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route(window.parcInfoConfig.routePrefix + '.destroy', id),
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        $('#equipements-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire('Supprimé !', res.message, 'success');
                    }
                },
                error: () => {
                    Swal.fire('Erreur', 'Impossible de supprimer cet équipement.', 'error');
                }
            });
        }
    });
}

// ── Wizard ────────────────────────────────────────────────────────────────────

const Wizard = (() => {
    let currentStep = 1;

    const $modal = () => $('#equipementModal');
    const $form = () => $('#equipementForm');
    const $step = (n) => $(`#step-${n}`);
    const $circle = (n) => $(`.wizard-step-circle[data-step="${n}"]`);
    const $label = (n) => $(`.wizard-step-label[data-step="${n}"]`);
    const $line = (n) => $(`.wizard-step-line[data-after="${n}"]`);

    function isEnStock() {
        const val = $('input[name="statut"]:checked').val();
        return val && val.startsWith('en_stock');
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

        // Étape 3 : grisée si en stock
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
            // Validation des champs dynamiques requis
            $form().find('[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    ok = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            if (!ok) { Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir tous les champs obligatoires.', timer: 2500, showConfirmButton: false }); }
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#equipement_id').val('');
        $('#wizard-title').text(`Ajouter un ${window.parcInfoConfig.categoryLibelle}`);
        $('#btn-submit-label').text("Enregistrer l'actif");
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
        $('#wizard-title').text(`Modifier l'équipement`);
        $('#btn-submit-label').text('Enregistrer');
        try {
            const res = await $.get(route('parc-info.equipements.show-json', id));
            const e = res.data;
            const aff = e.affectation_active;

            $('#equipement_id').val(e.id);
            // Statut
            $(`.statut-card[data-value="${e.statut}"]`).trigger('click');
            // Infos
            goTo(2);
            $('#code_inventaire').val(e.code_inventaire);
            $('#numero_serie').val(e.numero_serie);
            $('#marque_id').val(e.marque_id);
            $('#modele').val(e.modele);
            $('#date_acquisition').val(e.date_acquisition?.substring(0, 10));
            $('#date_fin_garantie').val(e.date_fin_garantie?.substring(0, 10));
            $('#valeur_achat').val(e.valeur_achat);
            $('#etat').val(e.etat);

            // Remplir les champs dynamiques
            if (e.champs_valeurs) {
                Object.entries(e.champs_valeurs).forEach(([code, val]) => {
                    const $field = $(`#champ_${code}`);
                    if ($field.length) {
                        if ($field.is(':checkbox')) {
                            $field.prop('checked', !!val);
                        } else {
                            $field.val(val);
                        }
                    }
                });
            }

            if (aff && !isEnStock()) {
                goTo(3);
                $(`.aff-type-card[data-value="${aff.type_cible}"]`).trigger('click');
                if (aff.type_cible === 'EMPLOYE' && aff.employe) {
                    $('#dossier_employe_id').val(aff.dossier_employe_id);
                    $('#emp-summary-nom').text(aff.employe.nom_complet);
                    $('#emp-summary-matricule').text(aff.employe.matricule);
                    $('#emp-summary-poste').text(aff.employe.poste || '—');
                    $('#emp-summary-rattachement').text(aff.employe.rattachement || '—');
                    $('.aff-summary').addClass('d-none');
                    $('#aff-employe-summary').removeClass('d-none');
                    $('#aff-skip-hint').addClass('d-none');
                }
                if (aff.type_cible === 'POSTE' && aff.poste_travail) {
                    $('#poste_travail_id').val(aff.poste_travail_id);
                    $('#poste-summary-code').text(aff.poste_travail.code);
                    $('#poste-summary-libelle').text(aff.poste_travail.libelle);
                    $('#poste-summary-emplacement').text(aff.poste_travail.local?.libelle || '—');
                    $('.aff-summary').addClass('d-none');
                    $('#aff-poste-summary').removeClass('d-none');
                    $('#aff-skip-hint').addClass('d-none');
                }
                if (aff.type_cible === 'LOCAL' && aff.local) {
                    $('#local_id').val(aff.local_id);
                    $('#local-summary-code').text(aff.local.code || '—');
                    $('#local-summary-libelle').text(aff.local.libelle);
                    $('#local-summary-type').text(aff.local.type || '—');
                    $('#local-summary-etage').text(aff.local.etage?.libelle || '—');
                    $('#local-summary-batiment').text(aff.local.etage?.batiment?.libelle || '—');
                    $('.aff-summary').addClass('d-none');
                    $('#aff-local-summary').removeClass('d-none');
                    $('#aff-skip-hint').addClass('d-none');
                }
            }
            $modal().modal('show');
        } catch (err) {
            console.error(err);
            Swal.fire('Erreur', 'Impossible de charger les données.', 'error');
        }
    }

    function init() {
        // Clic statut
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            updateStepper();
            updateNav();
        });

        // Enregistrer en réparation
        $('#btn-save-reparation').on('click', function(e) {
            e.preventDefault();
            if (!validateStep(2)) return;
            
            const formData = $form().serialize() + '&skip_affectation=1';
            submitForm(formData, false);
        });

        // Navigation
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

        // Sélection affectations
        $(document).on('employe:selected', function (e, emp) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="EMPLOYE"]').addClass('selected')
                .find('input[type="radio"]').prop('checked', true);
            $('#emp-summary-nom').text(emp.nom);
            $('#emp-summary-matricule').text(emp.matricule);
            $('#emp-summary-poste').text(emp.poste);
            $('#emp-summary-rattachement').text(emp.rattachement);
            $('#dossier_employe_id').val(emp.id);
            $('#poste_travail_id, #local_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-employe-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $(document).on('poste:selected', function (e, poste) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="POSTE"]').addClass('selected')
                .find('input[type="radio"]').prop('checked', true);
            $('#poste-summary-code').text(poste.code);
            $('#poste-summary-libelle').text(poste.libelle);
            $('#poste-summary-emplacement').text(poste.emplacement);
            $('#poste_travail_id').val(poste.id);
            $('#dossier_employe_id, #local_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-poste-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

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
            $('#dossier_employe_id, #poste_travail_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-local-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        // Choix direct du type d'affectation
        $(document).on('click', '.aff-type-card', function () {
            const val = $(this).data('value');
            if (val === 'EMPLOYE') $('#employeSelectionModal').modal('show');
            if (val === 'POSTE') $('#posteSelectionModal').modal('show');
            if (val === 'LOCAL') $('#localSelectionModal').modal('show');
        });

        // Ajout marque rapide
        $('.btn-add-marque-global').on('click', () => {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('equipementModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title: 'Nouvelle marque',
                input: 'text',
                inputPlaceholder: 'Ex: Dell, HP, Lenovo...',
                showCancelButton: true,
                confirmButtonText: 'Ajouter',
                cancelButtonText: 'Annuler',
                didOpen: () => setTimeout(() => Swal.getInput()?.focus(), 50),
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
                    $.post(route(window.parcInfoConfig.routePrefix + '.store-marque'), { libelle: result.value }, (res) => {
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

        // Ajout dictionnaire rapide
        $(document).on('click', '.btn-quick-add-dict', function () {
            const dictCode = $(this).data('dict-code');
            const fieldId = $(this).data('field-id');
            const fieldLibelle = $(this).data('field-libelle');
            
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('equipementModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title: `Ajouter : ${fieldLibelle}`,
                input: 'text',
                inputPlaceholder: 'Saisir la nouvelle valeur...',
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
                    $.post(route('parc-info.dictionnaires.valeurs.store'), { libelle: result.value, dictionnaire_code: dictCode }, (res) => {
                        if (res.success) {
                            $(`#${fieldId}`).append(new Option(res.data.valeur, res.data.id, true, true));
                            Swal.fire({ icon: 'success', title: 'Valeur ajoutée', timer: 1500, showConfirmButton: false });
                        }
                    }).fail((xhr) => {
                        if (bsModal) bsModal._focustrap?.activate();
                        Swal.fire('Erreur', xhr.responseJSON?.errors?.libelle?.[0] ?? 'Cette valeur existe déjà.', 'error');
                    });
                }
            });
        });

        // Soumission
        $form().on('submit', async (e) => {
            e.preventDefault();
            let formData = $form().serialize();
            if (!$('input[name="type_cible"]:checked').val()) {
                formData += '&skip_affectation=1';
            }
            submitForm(formData, true);
        });
    }

    function submitForm(formData, isFromSubmit) {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        const id = $('#equipement_id').val();
        const url = id ? route(window.parcInfoConfig.routePrefix + '.update', id) : route(window.parcInfoConfig.routePrefix + '.store');
        const method = id ? 'PUT' : 'POST';
        const $btn = isFromSubmit ? $('#btn-submit') : $('#btn-save-reparation');
        const originalText = $btn.find('span').text() || $btn.text();
        
        $btn.prop('disabled', true);
        if (isFromSubmit) {
            $btn.find('#btn-submit-label').text('Enregistrement...');
        } else {
            $btn.html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');
        }

        $.ajax({
            url, method,
            data: formData,
            success: (res) => {
                if (res.success) {
                    $modal().modal('hide');
                    $('#equipements-table').bootstrapTable('refresh');
                    loadKpis();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                }
            },
            error: (xhr) => {
                $btn.prop('disabled', false);
                if (isFromSubmit) {
                    $btn.find('#btn-submit-label').text(originalText);
                } else {
                    $btn.html('<i class="bi bi-tools me-1"></i> Enregistrer en réparation');
                }

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors ?? {};
                    let firstErrorField = null;

                    Object.entries(errors).forEach(([field, msgs]) => {
                        // replace dots with underscores for nested inputs e.g. champs_valeurs.type_pc -> champ_type_pc
                        const inputId = field.startsWith('champs_valeurs.') 
                            ? 'champ_' + field.substring(15) 
                            : field;
                        const $field = $(`#${inputId}`);
                        if ($field.length) {
                            $field.addClass('is-invalid');
                            $field.after(`<div class="invalid-feedback d-block">${msgs[0]}</div>`);
                            if (!firstErrorField) firstErrorField = $field;
                        }
                    });

                    if (firstErrorField) {
                        firstErrorField.focus();
                        Swal.fire({ icon: 'error', title: 'Erreur de validation', text: 'Veuillez corriger les erreurs signalées.' });
                    }
                } else {
                    Swal.fire('Erreur', 'Une erreur est survenue lors de l\'enregistrement.', 'error');
                }
            }
        });
    }

    return { init, open, openEdit };
})();

// ── Document Ready ───────────────────────────────────────────────────────────

$(document).ready(function () {
    // Initialise table buttons
    const $table = $('#equipements-table');
    const $btnEdit = $('#btn-edit');
    const $btnDelete = $('#btn-delete');

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        $btnEdit.prop('disabled', selections.length !== 1);
        $btnDelete.prop('disabled', selections.length === 0);
    });

    $('#btn-apply-filters').on('click', () => {
        $table.bootstrapTable('refresh');
        loadKpis();
    });

    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-direction, #filter-statut').val('');
        $table.bootstrapTable('refresh');
        loadKpis();
    });

    $('#btn-add').on('click', () => Wizard.open());
    $btnEdit.on('click', () => {
        const selections = $table.bootstrapTable('getSelections');
        if (selections.length === 1) {
            Wizard.openEdit(selections[0].id);
        }
    });

    $btnDelete.on('click', () => {
        const selections = $table.bootstrapTable('getSelections');
        if (selections.length > 0) {
            deleteEquipement(selections[0].id);
        }
    });

    Wizard.init();
    loadKpis();
});
