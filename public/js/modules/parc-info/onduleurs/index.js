/**
 * index.js — Onduleurs (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.onduleursQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        direction_id: $('#filter-direction').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.onduleurs.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

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
        <a href="${route('parc-info.onduleurs.show', id)}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteOnduleur(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route('parc-info.onduleurs.data'), { limit: 9999, offset: 0 }, (res) => {
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

    const $modal = () => $('#onduleurModal');
    const $form = () => $('#onduleurForm');
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
        if (n === 1) {
            if (!$('input[name="statut"]:checked').val()) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un statut.', timer: 2000, showConfirmButton: false });
                return false;
            }
        }
        if (n === 2) {
            const fields = ['numero_serie', 'modele', 'type_infra_id'];
            let ok = true;
            fields.forEach(f => {
                const $el = $(`#${f}`);
                if (!$el.val()) { $el.addClass('is-invalid'); ok = false; }
                else $el.removeClass('is-invalid');
            });
            if (!ok) Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir tous les champs obligatoires.', timer: 2500, showConfirmButton: false });
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#ond_id').val('');
        $('#wizard-title').text('Ajouter un onduleur');
        $('.statut-card, .aff-type-card').removeClass('selected').find('.check-icon').addClass('d-none');
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

    // ── Init events ───────────────────────────────────────────────────────────

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected').find('.check-icon').addClass('d-none');
            $(this).addClass('selected').find('.check-icon').removeClass('d-none');
            $(this).find('input').prop('checked', true);
            updateStepper();
            updateNav();
        });

        $(document).on('click', '.aff-type-card', function () {
            const val = $(this).data('value');
            $('.aff-type-card').removeClass('selected').find('.check-icon').addClass('d-none');
            $(this).addClass('selected').find('.check-icon').removeClass('d-none');
            $(this).find('input').prop('checked', true);
            
            if (val === 'EMPLOYE') $('#employeSelectionModal').modal('show');
            if (val === 'POSTE') $('#posteSelectionModal').modal('show');
            if (val === 'LOCAL') $('#localSelectionModal').modal('show');
        });

        $('#btn-next').on('click', () => { if (validateStep(currentStep)) goTo(currentStep + 1); });
        $('#btn-prev').on('click', () => goTo(currentStep - 1));

        // Sélection d'affectation
        $(document).on('employe:selected', function(e, emp){
            if(!$('#step-3').is(':visible')) return;
            $('#emp-summary-nom').text(emp.nom);
            $('#emp-summary-matricule').text(emp.matricule);
            $('#dossier_employe_id').val(emp.id);
            $('#poste_travail_id, #local_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-employe-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $(document).on('poste:selected', function(e, poste){
            if(!$('#step-3').is(':visible')) return;
            $('#poste-summary-code').text(poste.code);
            $('#poste-summary-libelle').text(poste.libelle);
            $('#poste_travail_id').val(poste.id);
            $('#dossier_employe_id, #local_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-poste-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $(document).on('local:selected', function(e, local){
            if(!$('#step-3').is(':visible')) return;
            $('#local-summary-code').text(local.code);
            $('#local-summary-libelle').text(local.libelle);
            $('#local_id').val(local.id);
            $('#dossier_employe_id, #poste_travail_id').val('');
            $('.aff-summary').addClass('d-none');
            $('#aff-local-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        // QuickAdd Marque
        $('#btn-add-marque').on('click', () => quickAdd('Nouvelle marque', 'Ex: APC, Eaton...', 'parc-info.onduleurs.store-marque', 'marque_id'));

        function quickAdd(title, placeholder, routeName, selectId) {
            const bsModal = bootstrap.Modal.getInstance(document.getElementById('onduleurModal'));
            if (bsModal) bsModal._focustrap?.deactivate();

            Swal.fire({
                title, input: 'text', inputPlaceholder: placeholder,
                showCancelButton: true, confirmButtonText: 'Ajouter',
                didOpen: () => setTimeout(() => Swal.getInput()?.focus(), 50),
                preConfirm: (val) => val?.trim() || Swal.showValidationMessage('Le libellé est obligatoire')
            }).then((res) => {
                if (bsModal) bsModal._focustrap?.activate();
                if (res.isConfirmed) {
                    $.post(route(routeName), { libelle: res.value }, (data) => {
                        $(`#${selectId}`).append(new Option(data.data.libelle, data.data.id, true, true));
                        Swal.fire({ icon: 'success', title: 'Ajouté', timer: 1500, showConfirmButton: false });
                    }).fail(() => Swal.fire('Erreur', 'Impossible d\'ajouter cet élément.', 'error'));
                }
            });
        }

        // Soumission
        $form().on('submit', function(e){
            e.preventDefault();
            const $btn = $('#btn-submit');
            $btn.prop('disabled', true).find('#btn-submit-label').text('Enregistrement...');

            $.ajax({
                url: route('parc-info.onduleurs.store'),
                method: 'POST',
                data: $form().serialize() + (isEnStock() ? '&skip_affectation=1' : ''),
                success: (res) => {
                    $modal().modal('hide');
                    $('#onduleurs-table').bootstrapTable('refresh');
                    loadKpis();
                    Swal.fire({ icon: 'success', title: 'Enregistré', timer: 2000, showConfirmButton: false });
                },
                error: (xhr) => {
                    $btn.prop('disabled', false).find('#btn-submit-label').text('Enregistrer l\'actif');
                    if(xhr.status === 422){
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(f => {
                            $(`#${f}`).addClass('is-invalid').after(`<div class="invalid-feedback">${errors[f][0]}</div>`);
                        });
                        const step = $('.is-invalid').first().closest('.wizard-step').attr('id').replace('step-','');
                        goTo(parseInt(step));
                    } else {
                        Swal.fire('Erreur', 'Une erreur est survenue.', 'error');
                    }
                }
            });
        });

        $modal().on('hidden.bs.modal', reset);
    }

    return { init, open };
})();

// ── Suppression ───────────────────────────────────────────────────────────────

function deleteOnduleur(id) {
    Swal.fire({
        title: 'Supprimer cet onduleur ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Supprimer'
    }).then((res) => {
        if (res.isConfirmed) {
            $.ajax({
                url: route('parc-info.onduleurs.destroy', id),
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: () => {
                    $('#onduleurs-table').bootstrapTable('refresh');
                    loadKpis();
                    Swal.fire({ icon: 'success', title: 'Supprimé', timer: 1500, showConfirmButton: false });
                }
            });
        }
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────

$(function () {
    Wizard.init();
    loadKpis();

    $('#btn-add').on('click', () => Wizard.open());
    $('#btn-apply-filters').on('click', () => $('#onduleurs-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => { $('#filter-site, #filter-direction, #filter-statut').val(''); $('#onduleurs-table').bootstrapTable('refresh'); });

    $('#onduleurs-table').on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#btn-edit').on('click', () => {
        const sel = $('#onduleurs-table').bootstrapTable('getSelections');
        if(sel.length) window.location.href = route('parc-info.onduleurs.show', sel[0].id);
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#onduleurs-table').bootstrapTable('getSelections');
        if(sel.length) deleteOnduleur(sel[0].id);
    });
});
