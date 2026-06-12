/**
 * index.js — Machines Virtuelles (ParcInfo)
 */

// ── Formatters Bootstrap Table ────────────────────────────────────────────────

window.serveursQueryParams = function (params) {
    return Object.assign(params, {
        site_id: $('#filter-site').val(),
        statut: $('#filter-statut').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.serveurs-virtuels.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

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
        <a href="/parc-info/informatique/serveurs-virtuels/${id}" class="btn btn-sm btn-outline-secondary border-0" title="Voir / Modifier"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteServeurVirtuel(row.id),
};

// ── KPI ───────────────────────────────────────────────────────────────────────

function loadKpis() {
    $.get(route('parc-info.serveurs-virtuels.data'), { limit: 9999, offset: 0 }, (res) => {
        const rows = res.rows ?? [];
        $('#kpi-total').text(res.total ?? 0);
        $('#kpi-service').text(rows.filter(r => r.statut === 'en_service').length);
        $('#kpi-stock').text(rows.filter(r => r.statut === 'en_stock').length);
        $('#kpi-alerte').text(rows.filter(r => ['en_reparation', 'perdu', 'reforme'].includes(r.statut)).length);
    });
}

// ── Wizard ────────────────────────────────────────────────────────────────────

const Wizard = (() => {
    const $modal = () => $('#serveurModal');
    const $form = () => $('#serveurForm');

    function isEnStock() {
        return $('#statut').val() === 'en_stock';
    }

    function toggleAffectationSection() {
        const stock = isEnStock();
        $('#affectation-section').toggle(!stock);
        if (stock) {
            $('#local_id').val('');
            $('#aff-local-summary').addClass('d-none');
            $('#aff-skip-hint').removeClass('d-none');
        }
    }

    function reset() {
        $form()[0].reset();
        $('#srv_id').val('');
        $('#wizard-title').text('Ajouter une machine virtuelle');
        $('#btn-submit-label').text('Enregistrer la VM');
        $('#statut').val('en_service');
        $('.aff-summary').addClass('d-none');
        $('#aff-skip-hint').removeClass('d-none');
        $('#local_id').val('');
        $form().find('.is-invalid').removeClass('is-invalid');
        $form().find('.invalid-feedback').remove();
        toggleAffectationSection();
    }

    function quickAdd(title, placeholder, routeName, selectId) {
        const bsModal = bootstrap.Modal.getInstance(document.getElementById('serveurModal'));
        if (bsModal) {
            bsModal._focustrap?.deactivate();
        }

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
            if (bsModal) {
                bsModal._focustrap?.activate();
            }
            if (result.isConfirmed && result.value) {
                $.post(route(routeName), { libelle: result.value }, (res) => {
                    if (res.success) {
                        $(`#${selectId}`).append(new Option(res.data.libelle, res.data.id, true, true));
                        Swal.fire({ icon: 'success', title: 'Ajouté avec succès', timer: 1500, showConfirmButton: false });
                    }
                }).fail((xhr) => {
                    if (bsModal) {
                        bsModal._focustrap?.activate();
                    }
                    Swal.fire('Erreur', xhr.responseJSON?.errors?.libelle?.[0] ?? 'Ce libellé existe déjà.', 'error');
                });
            }
        });
    }

    function init() {
        $('#statut').on('change', function () {
            toggleAffectationSection();
        });

        $('.btn-add-nomenclature').on('click', function() {
            const type = $(this).data('type');
            if (type === 'os') {
                const selectId = $(this).siblings('select').attr('id') || 'os_type_id';
                quickAdd('Nouvel OS', 'Ex: Windows Server 2022, Debian...', 'parc-info.ordinateurs.store-type-os', selectId);
            }
        });

        $('#btn-select-local').on('click', function () {
            $(document).trigger('show:local:modal');
        });

        $(document).on('local:selected', function (e, local) {
            if (!$modal().is(':visible')) {
                return;
            }
            $('#local-summary-code').text(local.code);
            $('#local-summary-libelle').text(local.libelle);
            $('#local-summary-etage').text(local.etage);
            $('#local_id').val(local.id);
            $('.aff-summary').addClass('d-none');
            $('#aff-local-summary').removeClass('d-none');
            $('#aff-skip-hint').addClass('d-none');
        });

        $form().on('submit', function (e) {
            e.preventDefault();
            
            $form().find('.is-invalid').removeClass('is-invalid');
            $form().find('.invalid-feedback').remove();

            const requiredFields = ['numero_serie', 'serveur_hote_id', 'statut'];
            let hasError = false;
            requiredFields.forEach(field => {
                const $field = $(`#${field}`);
                if (!$field.val()) {
                    $field.addClass('is-invalid');
                    hasError = true;
                }
            });

            if (hasError) {
                Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez remplir tous les champs obligatoires.', timer: 2500, showConfirmButton: false });
                return;
            }

            let formData = $(this).serialize();
            if (isEnStock() || !$('#local_id').val()) {
                formData += '&skip_affectation=1';
            }

            const id = $('#srv_id').val();
            const url = id ? route('parc-info.serveurs-virtuels.update', id) : route('parc-info.serveurs-virtuels.store');
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
                    $btn.prop('disabled', false).find('#btn-submit-label').text('Enregistrer la VM');
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
        $('#wizard-title').text('Modifier la machine virtuelle');
        $('#btn-submit-label').text('Enregistrer la VM');
        try {
            const res = await $.get(route('parc-info.serveurs-virtuels.show-json', id));
            const e = res.data;
            const s = e.serveur_virtuel ?? e.serveurVirtuel ?? {};
            const aff = e.affectation_active;

            $('#srv_id').val(e.id);
            $('#statut').val(e.statut);
            toggleAffectationSection();
            
            $('#code_inventaire').val(e.code_inventaire);
            $('#numero_serie').val(e.numero_serie);
            
            $('#role_serveur').val(s.role_serveur);
            $('#hyperviseur').val(s.hyperviseur);
            $('#ram_capacite_go').val(s.ram_capacite_go);
            $('#nb_processeurs').val(s.nb_processeurs);
            $('#nb_coeurs_total').val(s.nb_coeurs_total);
            $('#stockage_capacite_go').val(s.stockage_capacite_go);
            $('#os_type_id').val(s.os_type_id);
            $('#nom_hote').val(s.nom_hote);
            $('#domaine').val(s.domaine);
            $('#adresse_ip').val(s.adresse_ip);
            $('#adresse_mac').val(s.adresse_mac);
            $('#serveur_hote_id').val(s.serveur_hote_id);

            if (aff && !isEnStock()) {
                if (aff.type_cible === 'LOCAL' && aff.local) {
                    $('#local_id').val(aff.local_id);
                    $('#local-summary-code').text(aff.local.code);
                    $('#local-summary-libelle').text(aff.local.libelle);
                    $('#local-summary-etage').text(aff.local.etage?.libelle ?? aff.local.etage);
                    $('.aff-summary').addClass('d-none');
                    $('#aff-local-summary').removeClass('d-none');
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

function deleteServeurVirtuel(id) {
    Swal.fire({
        title: 'Supprimer cette VM ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route('parc-info.serveurs-virtuels.destroy', id),
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
        if (sel.length) window.location.href = `/parc-info/informatique/serveurs-virtuels/${sel[0].id}`;
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#serveurs-table').bootstrapTable('getSelections');
        if (sel.length) deleteServeurVirtuel(sel[0].id);
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
