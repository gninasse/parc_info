/**
 * index.js — Ordinateurs Fixes (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.ordinateursQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val) =>
    `<span class="fw-bold text-primary small">${val}</span>`;

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
        <a href="/parc-info/informatique/ordinateurs-fixes/${id}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteOrdinateur(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route('parc-info.ordinateurs-fixes.data'), { limit: 9999, offset: 0 }, (res) => {
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

    const $modal = () => $('#ordinateurModal');
    const $form = () => $('#ordinateurForm');
    const $step = (n) => $(`#step-${n}`);
    const $circle = (n) => $(`.wizard-step-circle[data-step="${n}"]`);
    const $label = (n) => $(`.wizard-step-label[data-step="${n}"]`);
    const $line = (n) => $(`.wizard-step-line[data-after="${n}"]`);

    // Retourne true si le statut sélectionné est "en_stock"
    function isEnStock() {
        return $('input[name="statut"]:checked').val() === 'en_stock';
    }

    // Nombre d'étapes effectif selon le statut
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

        // Étape 3 : grisée et barrée si en_stock
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
            if (!ok) { Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir tous les champs obligatoires.', timer: 2500, showConfirmButton: false }); }
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#ord_id').val('');
        $('#wizard-title').text('Ajouter un ordinateur');
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
        $('#wizard-title').text('Modifier l\'ordinateur');
        $('#btn-submit-label').text('Enregistrer');
        try {
            const res = await $.get(route('parc-info.ordinateurs-fixes.show', id));
            const e = res.data;
            const o = e.ordinateur ?? {};
            const aff = e.affectation_active;

            $('#ord_id').val(e.id);
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
            $('#processeur_model').val(o.processeur_model);
            $('#ram_capacite_go').val(o.ram_capacite_go);
            $('#ram_type_id').val(o.ram_type_id);
            $('#os_type_id').val(o.os_type_id);
            $('#stockage_capacite_go').val(o.stockage_capacite_go);
            $('#disque_type_id').val(o.disque_type_id);
            $('#nom_hote').val(o.nom_hote);
            $('#compte_admin_local').val(o.compte_admin_local);
            $('#etat').val(e.etat);
            $(`#type_pc_${o.type_pc}`).prop('checked', true);

            if (aff && !isEnStock()) {
                goTo(3);
                $(`.aff-type-card[data-value="${aff.type_cible}"]`).trigger('click');
                if (aff.type_cible === 'EMPLOYE' && aff.employe) {
                    $('#dossier_employe_id').val(aff.dossier_employe_id);
                    $('#employe-search').val(aff.employe.matricule);
                    $('#employe-nom').val(`${aff.employe.nom} ${aff.employe.prenom}`);
                    $('#aff-date-debut-emp').val(aff.date_debut?.substring(0, 10));
                    $('#aff-type-emp').val(aff.type_affectation);
                }
                if (aff.type_cible === 'POSTE' && aff.poste_travail) {
                    $('#poste_travail_id').val(aff.poste_travail_id);
                    $('#poste-search').val(aff.poste_travail.code);
                    showPosteDetail(aff.poste_travail);
                    $('#aff-date-debut-poste').val(aff.date_debut?.substring(0, 10));
                    $('#aff-type-poste').val(aff.type_affectation);
                }
                if (aff.type_cible === 'LOCAL') {
                    $('#local_id').val(aff.local_id);
                    $('#local-search').val(aff.local?.libelle);
                }
            }
            $modal().modal('show');
        } catch {
            Swal.fire('Erreur', 'Impossible de charger les données.', 'error');
        }
    }

    function showPosteDetail(p) {
        $('#poste-code').text(p.code);
        $('#poste-libelle').text(p.libelle);
        $('#poste-service').text(p.service ?? '—');
        $('#poste-local').text(p.local ?? '—');
        $('#poste-detail').removeClass('d-none');
    }

    // ── Init events ───────────────────────────────────────────────────────────

    function init() {
        // Statut cards — recalcule le stepper immédiatement
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            updateStepper();
            updateNav();
        });

        // Bouton Enregistrer en réparation
        $('#btn-save-reparation').on('click', function(e) {
            e.preventDefault();
            if (!validateStep(2)) return;
            
            const formData = $form().serialize() + '&skip_affectation=1';
            submitForm(formData, false);
        });

        // Navigation wizard
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

        // ── Écoute des événements de sélection (depuis selection_modals.js) ─────
        // Ces événements sont émis après confirmation dans les modales de sélection.
        // Le wizard écoute uniquement quand il est actif (step-3 visible).

        $(document).on('employe:selected', function (e, emp) {
            if (!$('#step-3').is(':visible')) return; // Ignore si c'est la modale show
            // Marquer la carte
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="EMPLOYE"]').addClass('selected')
                .find('input[type="radio"]').prop('checked', true);
            // Mettre à jour summary
            $('#emp-summary-nom').text(emp.nom);
            $('#emp-summary-matricule').text(emp.matricule);
            $('#emp-summary-poste').text(emp.poste);
            $('#emp-summary-rattachement').text(emp.rattachement);
            // Champ hidden
            $('#dossier_employe_id').val(emp.id);
            $('#poste_travail_id, #local_id').val('');
            // Afficher summary, cacher les autres
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

        // ── Ajout rapide de nomenclatures ────────────────────────────────────

        function quickAdd(title, placeholder, routeName, selectId) {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('ordinateurModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title,
                input: 'text',
                inputPlaceholder: placeholder,
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
                    $.post(route(routeName), { libelle: result.value }, (res) => {
                        if (res.success) {
                            $(`#${selectId}`).append(new Option(res.data.libelle, res.data.id, true, true));
                            Swal.fire({ icon: 'success', title: 'Ajouté avec succès', timer: 1500, showConfirmButton: false });
                        }
                    }).fail((xhr) => {
                        if (bsModal) bsModal._focustrap?.activate();
                        Swal.fire('Erreur', xhr.responseJSON?.errors?.libelle?.[0] ?? 'Ce libellé existe déjà.', 'error');
                    });
                }
            });
        }

        // Ajout marque rapide
        $('#btn-add-marque').on('click', () => {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('ordinateurModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title: 'Nouvelle marque',
                input: 'text',
                inputPlaceholder: 'Ex: Dell, HP, Lenovo...',
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
                    $.post(route('parc-info.ordinateurs-fixes.store-marque'), { libelle: result.value }, (res) => {
                        if (res.success) {
                            $('#marque_id').append(new Option(res.data.libelle, res.data.id, true, true));
                            Swal.fire({ icon: 'success', title: 'Marque ajoutée', timer: 1500, showConfirmButton: false });
                        }
                    }).fail((xhr) => {
                        Swal.fire('Erreur', xhr.responseJSON?.errors?.libelle?.[0] ?? 'Cette marque existe déjà.', 'error');
                    });
                }
            });
        });

        $('#btn-add-ram').on('click', () => quickAdd('Nouveau type de RAM', 'Ex: DDR4, DDR5...', 'parc-info.ordinateurs-fixes.store-type-ram', 'ram_type_id'));
        $('#btn-add-os').on('click', () => quickAdd("Nouveau système d'exploitation", 'Ex: Windows 11 Pro, Ubuntu 22.04...', 'parc-info.ordinateurs-fixes.store-type-os', 'os_type_id'));
        $('#btn-add-disque').on('click', () => quickAdd('Nouveau type de disque', 'Ex: SSD NVMe, HDD, SSD SATA...', 'parc-info.ordinateurs-fixes.store-type-disque', 'disque_type_id'));
        $('#btn-add-cpu').on('click', () => quickAdd('Nouveau type de CPU', 'Ex: Intel Core i7, AMD Ryzen 5...', 'parc-info.ordinateurs-fixes.store-type-cpu', 'cpu_type_id'));

        // Soumission
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

            const id = $('#ord_id').val();
            const url = id ? route('parc-info.ordinateurs-fixes.update', id) : route('parc-info.ordinateurs-fixes.store');
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
                        $('#ordinateurs-table').bootstrapTable('refresh');
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
                        $btn.find('#btn-submit-label').text(originalText);
                    } else {
                        $btn.html('<i class="bi bi-tools me-1"></i> Enregistrer en réparation');
                    }
                },
            });
        }

        // Reset à la fermeture
        $modal().on('hidden.bs.modal', reset);
    }

    return { init, open, openEdit };
})();

// ── Dropdown recherche générique ──────────────────────────────────────────────

function showSearchDropdown(inputSelector, items, onSelect) {
    const $input = $(inputSelector);
    $('.search-dropdown').remove();
    const $dd = $('<ul class="search-dropdown list-unstyled bg-white border rounded-3 shadow-sm position-absolute w-100 mb-0 py-1" style="z-index:9999;max-height:200px;overflow-y:auto"></ul>');
    items.forEach(item => {
        $('<li class="px-3 py-2 small cursor-pointer hover-bg-light"></li>')
            .text(item.text)
            .on('click', () => { onSelect(item); $dd.remove(); })
            .appendTo($dd);
    });
    $input.closest('.input-group, .col-12, .col-md-5').css('position', 'relative').append($dd);
    $(document).one('click', () => $dd.remove());
}

// ── Suppression ───────────────────────────────────────────────────────────────

function deleteOrdinateur(id) {
    Swal.fire({
        title: 'Supprimer cet ordinateur ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route('parc-info.ordinateurs-fixes.destroy', id),
                method: 'DELETE',
                success: (res) => {
                    if (res.success) {
                        $('#ordinateurs-table').bootstrapTable('refresh');
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
        const sel = $('#ordinateurs-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = `/parc-info/informatique/ordinateurs-fixes/${sel[0].id}`;
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#ordinateurs-table').bootstrapTable('getSelections');
        if (sel.length) deleteOrdinateur(sel[0].id);
    });

    $('#ordinateurs-table').on('check.bs.table uncheck.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#btn-apply-filters').on('click', () => $('#ordinateurs-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-direction, #filter-statut').val('');
        $('#ordinateurs-table').bootstrapTable('refresh');
    });

    $('#ordinateurs-table').on('load-success.bs.table', () => {
        $('#btn-edit, #btn-delete').prop('disabled', true);
    });

    loadKpis();
});
