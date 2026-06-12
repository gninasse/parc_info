/**
 * index.js — Serveurs Physiques (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.serveursQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.serveurs.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

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
        <a href="/parc-info/informatique/serveurs/${id}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteServeur(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route('parc-info.serveurs.data'), { limit: 9999, offset: 0 }, (res) => {
        const rows = res.rows ?? [];
        $('#kpi-total').text(res.total ?? 0);
        $('#kpi-service').text(rows.filter(r => r.statut === 'en_service').length);
        $('#kpi-stock').text(rows.filter(r => r.statut === 'en_stock').length);
        $('#kpi-alerte').text(rows.filter(r => ['en_reparation', 'perdu', 'reforme'].includes(r.statut)).length);
    });
}

// ── Wizard ────────────────────────────────────────────────────────────────────

const Wizard = (() => {
    let currentStep = 1;

    const $modal = () => $('#serveurModal');
    const $form = () => $('#serveurForm');
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
        $('#btn-prev').toggle(currentStep > 1);
        $('#btn-next').toggleClass('d-none', currentStep >= last);
        $('#btn-submit').toggleClass('d-none', currentStep < last);
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
        $('#srv_id').val('');
        $('#wizard-title').text('Ajouter un serveur physique');
        $('#btn-submit-label').text('Enregistrer le serveur');
        $('.statut-card').removeClass('selected');
        $('.aff-type-card').removeClass('selected');
        $('.aff-summary').addClass('d-none');
        $('#aff-skip-hint').removeClass('d-none');
        $('#poste_travail_id, #local_id').val('');
        $form().find('.is-invalid').removeClass('is-invalid');
        goTo(1);
    }

    function quickAdd(title, placeholder, routeName, selectId) {
        const bsModal = bootstrap.Modal.getInstance(document.getElementById('serveurModal'));
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

    // ── Init events ───────────────────────────────────────────────────────────

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            updateStepper();
            updateNav();
        });

        $('.btn-add-nomenclature').on('click', function() {
            const type = $(this).data('type');
            if (type === 'marque') quickAdd('Nouvelle marque', 'Ex: Dell, HP...', 'parc-info.ordinateurs.store-marque', 'marque_id');
            if (type === 'os') quickAdd('Nouvel OS', 'Ex: Windows Server 2022, Debian...', 'parc-info.ordinateurs.store-type-os', 'os_type_id');
            if (type === 'cpu') quickAdd('Nouveau CPU', 'Ex: Intel Xeon, AMD EPYC...', 'parc-info.ordinateurs.store-type-cpu', 'cpu_type_id');
            if (type === 'ram') quickAdd('Nouveau type RAM', 'Ex: DDR4 ECC, DDR5 ECC...', 'parc-info.ordinateurs.store-type-ram', 'ram_type_id');
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
                $('#poste_travail_id, #local_id').val('');
            }
            goTo(currentStep - 1);
        });

        // Affectation type cards
        $(document).on('click', '.aff-type-card', function () {
            $('.aff-type-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            
            const val = $(this).data('value');
            if (val === 'LOCAL') {
                $(document).trigger('show:local:modal');
            } else if (val === 'POSTE') {
                $(document).trigger('show:poste:modal');
            }
        });

        $(document).on('local:selected', function (e, local) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="LOCAL"]').addClass('selected')
                .find('input[type="radio"]').prop('checked', true);
            $('#local-summary-code').text(local.code);
            $('#local-summary-libelle').text(local.libelle);
            $('#local-summary-etage').text(local.etage);
            $('#local_id').val(local.id);
            $('#poste_travail_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-local-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $(document).on('poste:selected', function (e, poste) {
            if (!$('#step-3').is(':visible')) return;
            $('.aff-type-card').removeClass('selected');
            $('.aff-type-card[data-value="POSTE"]').addClass('selected')
                .find('input[type="radio"]').prop('checked', true);
            $('#poste-summary-code').text(poste.code);
            $('#poste-summary-emplacement').text(poste.emplacement);
            $('#poste_travail_id').val(poste.id);
            $('#local_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-poste-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $form().on('submit', function (e) {
            e.preventDefault();
            
            let formData = $(this).serialize();
            if (!$('input[name="type_cible"]:checked').val()) {
                formData += '&skip_affectation=1';
            }

            const id = $('#srv_id').val();
            const url = id ? route('parc-info.serveurs.update', id) : route('parc-info.serveurs.store');
            const method = id ? 'PUT' : 'POST';
            const $btn = $('#btn-submit');
            
            $btn.prop('disabled', true).find('#btn-submit-label').text('Enregistrement...');

            $.ajax({
                url, method,
                data: formData,
                success: (res) => {
                    if (res.success) {
                        $modal().modal('hide');
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false })
                            .then(() => {
                                if ($('#serveurs-table').length) {
                                    $('#serveurs-table').bootstrapTable('refresh');
                                    loadKpis();
                                } else {
                                    window.location.reload();
                                }
                            });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors ?? {};
                        Object.entries(errors).forEach(([field, msgs]) => {
                            $(`#${field}`).addClass('is-invalid').after(`<div class="invalid-feedback d-block">${msgs[0]}</div>`);
                        });
                        Swal.fire('Erreur de validation', 'Veuillez corriger les erreurs.', 'error');
                    } else {
                        Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).find('#btn-submit-label').text('Enregistrer le serveur');
                },
            });
        });

        $modal().on('hidden.bs.modal', reset);
    }

    function open() {
        reset();
        $modal().modal('show');
    }

    async function openEdit(id) {
        reset();
        $('#wizard-title').text('Modifier le serveur physique');
        $('#btn-submit-label').text('Enregistrer le serveur');
        try {
            const res = await $.get(route('parc-info.serveurs.show-json', id));
            const e = res.data;
            const s = e.serveur ?? {};
            const aff = e.affectation_active;

            $('#srv_id').val(e.id);
            // Statut
            $(`.statut-card[data-value="${e.statut}"]`).trigger('click');
            
            // Go to step 2 to populate fields
            goTo(2);
            $('#code_inventaire').val(e.code_inventaire);
            $('#numero_serie').val(e.numero_serie);
            $('#marque_id').val(e.marque_id);
            $('#modele').val(e.modele);
            $('#date_acquisition').val(e.date_acquisition?.substring(0, 10));
            $('#date_mise_en_service').val(e.date_mise_en_service?.substring(0, 10));
            $('#date_fin_garantie').val(e.date_fin_garantie?.substring(0, 10));
            $('#valeur_achat').val(e.valeur_achat);
            $('#etat').val(e.etat);

            // Server-specific fields
            $('#role_serveur').val(s.role_serveur);
            $('#hyperviseur').val(s.hyperviseur);
            $('#ram_capacite_go').val(s.ram_capacite_go);
            $('#ram_type_id').val(s.ram_type_id);
            $('#cpu_type_id').val(s.cpu_type_id);
            $('#nb_processeurs').val(s.nb_processeurs);
            $('#nb_coeurs_total').val(s.nb_coeurs_total);
            $('#disque_type_id').val(s.disque_type_id);
            $('#stockage_capacite_go').val(s.stockage_capacite_go);
            $('#os_type_id').val(s.os_type_id);
            $('#nom_hote').val(s.nom_hote);
            $('#domaine').val(s.domaine);
            $('#adresse_ip').val(s.adresse_ip);
            $('#adresse_mac').val(s.adresse_mac);
            $('#u_position_depart').val(s.u_position_depart);
            $('#u_position_fin').val(s.u_position_fin);

            // Affectation
            if (aff && !isEnStock()) {
                goTo(3);
                $(`.aff-type-card[data-value="${aff.type_cible}"]`).trigger('click');
                if (aff.type_cible === 'LOCAL' && aff.local) {
                    $('#local_id').val(aff.local_id);
                    $('#local-summary-code').text(aff.local.code);
                    $('#local-summary-libelle').text(aff.local.libelle);
                    $('#local-summary-etage').text(aff.local.etage?.libelle);
                    $('.aff-summary').addClass('d-none');
                    $('#aff-local-summary').removeClass('d-none');
                    $('#aff-skip-hint').addClass('d-none');
                } else if (aff.type_cible === 'POSTE' && aff.poste_travail) {
                    $('#poste_travail_id').val(aff.poste_travail_id);
                    $('#poste-summary-code').text(aff.poste_travail.code);
                    $('#poste-summary-emplacement').text(aff.poste_travail.emplacement);
                    $('.aff-summary').addClass('d-none');
                    $('#aff-poste-summary').removeClass('d-none');
                    $('#aff-skip-hint').addClass('d-none');
                }
            }
            $modal().modal('show');
        } catch (err) {
            Swal.fire('Erreur', 'Impossible de charger les données.', 'error');
        }
    }

    return { init, open, openEdit };
})();

window.Wizard = Wizard;

function deleteServeur(id) {
    Swal.fire({
        title: 'Supprimer ce serveur ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route('parc-info.serveurs.destroy', id),
                method: 'DELETE',
                success: (res) => {
                    if (res.success) {
                        $('#serveurs-table').bootstrapTable('refresh');
                        loadKpis();
                        Swal.fire({ icon: 'success', title: 'Supprimé', timer: 1500, showConfirmButton: false });
                    }
                },
            });
        }
    });
}

$(function () {
    Wizard.init();
    $('#btn-add').on('click', () => Wizard.open());
    
    $('#btn-edit').on('click', () => {
        const sel = $('#serveurs-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = `/parc-info/informatique/serveurs/${sel[0].id}`;
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#serveurs-table').bootstrapTable('getSelections');
        if (sel.length) deleteServeur(sel[0].id);
    });

    $('#serveurs-table').on('check.bs.table uncheck.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#serveurs-table').on('load-success.bs.table', () => {
        $('#btn-edit, #btn-delete').prop('disabled', true);
    });

    $('#btn-apply-filters').on('click', () => $('#serveurs-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-statut').val('');
        $('#serveurs-table').bootstrapTable('refresh');
    });

    if ($('#kpi-total').length) {
        loadKpis();
    }
});
