/**
 * index.js — Mobiles & Tablettes (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.mobilesQueryParams = function (params) {
    return Object.assign(params, {
        type_mobile_id: $('#filter-type').val(),
        site_id: $('#filter-site').val(),
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
        <a href="/parc-info/informatique/mobiles/${id}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
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
        if (n === 1 && !$('input[name="statut"]:checked').val()) {
            Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un statut.', timer: 2000, showConfirmButton: false });
            return false;
        }
        if (n === 2) {
            const fields = ['numero_serie', 'modele', 'etat'];
            let ok = true;
            fields.forEach(f => {
                const $el = $(`#${f}`);
                if (!$el.val()) { $el.addClass('is-invalid'); ok = false; }
                else $el.removeClass('is-invalid');
            });
            return ok;
        }
        return true;
    }

    function reset() {
        $form()[0].reset();
        $('#mob_id').val('');
        $('.statut-card, .aff-type-card').removeClass('selected');
        $('.aff-summary').addClass('d-none');
        $('#aff-skip-hint').removeClass('d-none');
        $('#dossier_employe_id, #local_id').val('');
        $form().find('.is-invalid').removeClass('is-invalid');
        goTo(1);
    }

    function quickAdd(title, placeholder, routeName, selectId) {
        const bsModal = bootstrap.Modal.getInstance(document.getElementById('mobileModal'));
        if (bsModal) bsModal._focustrap?.deactivate();

        Swal.fire({
            title, input: 'text', inputPlaceholder: placeholder, showCancelButton: true,
            confirmButtonText: 'Ajouter', cancelButtonText: 'Annuler',
            didOpen: () => setTimeout(() => Swal.getInput()?.focus(), 50),
            preConfirm: (value) => value?.trim() || Swal.showValidationMessage('Le libellé est obligatoire.')
        }).then((result) => {
            if (bsModal) bsModal._focustrap?.activate();
            if (result.isConfirmed) {
                $.post(route(routeName), { libelle: result.value }, (res) => {
                    if (res.success) {
                        $(`#${selectId}`).append(new Option(res.data.libelle, res.data.id, true, true));
                        Swal.fire({ icon: 'success', title: 'Ajouté', timer: 1500, showConfirmButton: false });
                    }
                });
            }
        });
    }

    function init() {
        $(document).on('click', '.statut-card', function () {
            $('.statut-card').removeClass('selected');
            $(this).addClass('selected').find('input[type="radio"]').prop('checked', true);
            updateStepper(); updateNav();
        });

        $('#btn-add-type-mobile').on('click', () => quickAdd('Nouveau type de terminal', 'Ex: Smartphone, Tablette, PDA...', 'parc-info.mobiles.store-type', 'type_mobile_id'));

        $('#btn-next').on('click', () => validateStep(currentStep) && goTo(currentStep + 1));
        $('#btn-prev').on('click', () => goTo(currentStep - 1));

        // Affectation events
        $(document).on('click', '.aff-type-card', function () {
            $('.aff-type-card').removeClass('selected');
            $(this).addClass('selected').find('input[type="radio"]').prop('checked', true);
            const val = $(this).data('value');
            if (val === 'EMPLOYE') $(document).trigger('show:employe:modal');
            else if (val === 'LOCAL') $(document).trigger('show:local:modal');
        });

        $(document).on('employe:selected', function (e, emp) {
            if (!$('#step-3').is(':visible')) return;
            $('#emp-summary-nom').text(emp.nom);
            $('#emp-summary-matricule').text(emp.matricule);
            $('#emp-summary-rattachement').text(emp.rattachement);
            $('#dossier_employe_id').val(emp.id);
            $('#local_id').val('');
            $('.aff-summary').addClass('d-none'); $('#aff-employe-summary').removeClass('d-none'); $('#aff-skip-hint').addClass('d-none');
        });

        $(document).on('local:selected', function (e, local) {
            if (!$('#step-3').is(':visible')) return;
            $('#local-summary-libelle').text(local.libelle);
            $('#local-summary-etage').text(local.etage);
            $('#local-summary-batiment').text(local.batiment);
            $('#local_id').val(local.id);
            $('#dossier_employe_id').val('');
            $('.aff-summary').addClass('d-none'); $('#aff-local-summary').removeClass('d-none'); $('#aff-skip-hint').addClass('d-none');
        });

        $form().on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-submit');
            $btn.prop('disabled', true).text('Enregistrement...');
            $.ajax({
                url: route('parc-info.mobiles.store'),
                method: 'POST',
                data: $(this).serialize() + ($('input[name="type_cible"]:checked').val() ? '' : '&skip_affectation=1'),
                success: (res) => {
                    $modal().modal('hide');
                    $('#mobiles-table').bootstrapTable('refresh');
                    loadKpis();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                },
                error: (xhr) => {
                    Swal.fire('Erreur', 'Veuillez vérifier les champs.', 'error');
                    $btn.prop('disabled', false).text('Enregistrer');
                }
            });
        });

        $modal().on('hidden.bs.modal', reset);
    }

    return { init, open: () => { reset(); $modal().modal('show'); } };
})();

function deleteMobile(id) {
    Swal.fire({ title: 'Supprimer ce terminal ?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Supprimer' })
        .then((r) => r.isConfirmed && $.ajax({ url: route('parc-info.mobiles.destroy', id), method: 'DELETE', success: () => { $('#mobiles-table').bootstrapTable('refresh'); loadKpis(); } }));
}

$(function () {
    Wizard.init();
    $('#btn-add').on('click', () => Wizard.open());
    $('#btn-apply-filters').on('click', () => $('#mobiles-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => { $('#filter-type, #filter-site, #filter-statut').val(''); $('#mobiles-table').bootstrapTable('refresh'); });
    loadKpis();
});
