/**
 * Gestion des Consommables - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Modales + Layered selection
 */

window.consommablesQueryParams = function(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
        type_consommable_id: $('#filter-type').val(),
        statut: $('#filter-statut').val()
    };
};

window.codeFormatter = function(value, row) {
    return `<a href="${route('parc-info.consommables.show', row.id)}" class="fw-bold text-primary text-decoration-none">${value}</a>`;
};

window.stockFormatter = function(value, row) {
    const isLow = row.stock_actuel <= parseInt(row.seuil.split('/')[0].trim());
    return `<span class="fw-bold ${isLow ? 'text-danger' : 'text-dark'}">${value}</span> <small class="text-muted">${row.unite}</small>`;
};

window.statusFormatter = function(value, row) {
    return row.status_label;
};

window.actionsFormatter = function(value, row) {
    return `
        <div class="btn-group btn-group-sm">
            <button class="btn btn-light border btn-action-appro" data-id="${row.id}" data-nom="${row.nom}" title="Réapprovisionner">
                <i class="fas fa-plus-circle text-success"></i>
            </button>
            <button class="btn btn-light border btn-action-consommer" data-id="${row.id}" data-nom="${row.nom}" title="Sortie stock">
                <i class="fas fa-minus-circle text-primary"></i>
            </button>
            <a href="${route('parc-info.consommables.show', row.id)}" class="btn btn-light border" title="Voir détails">
                <i class="fas fa-eye text-info"></i>
            </a>
        </div>
    `;
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#consommables-table');
    const $modal = new bootstrap.Modal('#modal-consommable');
    const $form = $('#form-consommable');
    const $btnSave = $('#btn-save-consommable');
    
    const $modalType = new bootstrap.Modal('#modal-quickadd-type-cons');
    const $formType = $('#form-quickadd-type-cons');
    
    const $modalFournisseur = new bootstrap.Modal('#modal-quickadd-fournisseur');
    const $formFournisseur = $('#form-quickadd-fournisseur');

    // Mouvements & Sélection
    const $modalConsommer = new bootstrap.Modal('#modal-consommer-consommable');
    const $formConsommer = $('#form-consommer-consommable');
    const $modalEq = new bootstrap.Modal('#equipementSelectionModal');
    let selectedEq = null;
    let currentConsommableId = null;

    const $btnEditToolbar = $('#btn-edit');
    const $btnToggleToolbar = $('#btn-toggle-status');
    const $btnDeleteToolbar = $('#btn-delete');

    // Mise à jour des KPIs
    $table.on('load-success.bs.table', function (e, data) {
        if (data.stats) {
            $('#kpi-total').text(data.stats.total);
            $('#kpi-rupture').text(data.stats.en_rupture);
            $('#kpi-valeur').text(new Intl.NumberFormat('fr-FR').format(data.stats.valeur_totale) + ' €');
            $('#kpi-mouvements').text(data.stats.mouvements_mois);
        }
    });

    // ── FILTRES ──
    $('#btn-apply-filters').on('click', () => $table.bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-type, #filter-statut').val('').trigger('change');
        $table.bootstrapTable('refresh');
    });

    // ── SELECTION ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        $btnEditToolbar.prop('disabled', !hasOne);
        $btnToggleToolbar.prop('disabled', !hasOne);
        $btnDeleteToolbar.prop('disabled', !hasOne);
    });

    // ── AJOUT ──
    $('#btn-add').on('click', function() {
        $form[0].reset();
        $('#consommable-id').val('');
        $('#modalConsommableLabel span').text('Nouveau Consommable');
        $form.find('.select2-modal').val('').trigger('change');
        $modal.show();
    });

    // ── MODIFICATION ──
    function editConsommable(id) {
        $.ajax({
            url: route('parc-info.consommables.show', id) + '?json=1',
            method: 'GET',
            success: function(c) {
                $('#consommable-id').val(c.id);
                $form.find('[name="code"]').val(c.code);
                $form.find('[name="nom"]').val(c.nom);
                $form.find('[name="type_consommable_id"]').val(c.type_consommable_id).trigger('change');
                $form.find('[name="marque_id"]').val(c.marque_id).trigger('change');
                $form.find('[name="quantite_stock_min"]').val(c.quantite_stock_min);
                $form.find('[name="quantite_stock_max"]').val(c.quantite_stock_max);
                $form.find('[name="cout_unitaire"]').val(c.cout_unitaire);
                $form.find('[name="fournisseur_principal_id"]').val(c.fournisseur_principal_id).trigger('change');
                $form.find('[name="notes"]').val(c.notes);
                
                $('#modalConsommableLabel span').text('Modifier le Consommable');
                $modal.show();
            }
        });
    }

    $btnEditToolbar.on('click', () => editConsommable($table.bootstrapTable('getSelections')[0].id));

    // ── ENREGISTREMENT ──
    $form.on('submit', function(e) {
        e.preventDefault();
        const id = $('#consommable-id').val();
        const url = id ? route('parc-info.consommables.update', id) : route('parc-info.consommables.store');
        
        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize() + (id ? '&_method=PUT' : ''),
            success: function(res) {
                if (res.success) {
                    $modal.hide();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    $table.bootstrapTable('refresh');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
            },
            complete: () => $btnSave.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    // ── APPROVISIONNEMENT ──
    $(document).on('click', '.btn-action-appro', function() {
        const id = $(this).data('id');
        const nom = $(this).data('nom');
        Swal.fire({
            title: 'Réapprovisionner',
            text: nom,
            input: 'number',
            inputLabel: 'Quantité reçue',
            showCancelButton: true,
            confirmButtonText: 'Ajouter',
            showLoaderOnConfirm: true,
            preConfirm: (val) => {
                if (!val || val <= 0) return Swal.showValidationMessage('Quantité invalide');
                return $.ajax({
                    url: route('parc-info.consommables.approvisionner', id),
                    method: 'POST',
                    data: { quantite: val }
                }).catch(err => Swal.showValidationMessage(err.responseJSON?.message || 'Erreur'));
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Succès', result.value.message, 'success');
                $table.bootstrapTable('refresh');
            }
        });
    });

    // ── CONSOMMATION (MODALE DÉDIÉE) ──
    $(document).on('click', '.btn-action-consommer', function() {
        currentConsommableId = $(this).data('id');
        const nom = $(this).data('nom');
        
        $formConsommer[0].reset();
        $('#consommation-equipement-id').val('');
        $('#consommation-equipement-label').val('');
        $formConsommer.find('.text-primary').text(nom);
        selectedEq = null;
        
        $modalConsommer.show();
    });

    // Sélection d'équipement
    $('#btn-select-equipement').on('click', function() {
        loadEquipements();
        $modalEq.show();
    });

    function loadEquipements() {
        $('#eq-list').addClass('opacity-50');
        $('#eq-skeleton').removeClass('d-none');
        $.ajax({
            url: route('parc-info.search-equipements'),
            data: { q: $('#eq-search').val(), statut: $('#eq-filter-statut').val() },
            success: function(data) {
                let html = '';
                data.forEach(e => {
                    html += `
                        <tr class="eq-row" data-id="${e.id}" data-label="${e.code} - ${e.modele}">
                            <td class="text-center"><div class="form-check"><input class="form-check-input" type="radio" name="eq-radio" value="${e.id}"></div></td>
                            <td><span class="fw-bold text-primary">${e.code}</span></td>
                            <td>${e.marque} <strong>${e.modele}</strong></td>
                            <td><small>${e.emplacement}</small></td>
                            <td>${e.statut_label}</td>
                        </tr>
                    `;
                });
                $('#eq-list').html(html || '<tr><td colspan="5" class="text-center py-4">Aucun équipement trouvé.</td></tr>').removeClass('opacity-50');
                $('#eq-skeleton').addClass('d-none');
                $('.eq-row').on('click', function() {
                    $(this).find('input').prop('checked', true);
                    $('.eq-row').removeClass('eq-row-selected'); $(this).addClass('eq-row-selected');
                    $('#eq-confirm').prop('disabled', false);
                    selectedEq = { id: $(this).data('id'), label: $(this).data('label') };
                });
            }
        });
    }

    $('#eq-confirm').on('click', function() {
        if (selectedEq) {
            $('#consommation-equipement-id').val(selectedEq.id);
            $('#consommation-equipement-label').val(selectedEq.label);
            $modalEq.hide();
        }
    });

    $formConsommer.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-consommation');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: route('parc-info.consommables.consommer', currentConsommableId),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalConsommer.hide();
                    Swal.fire('Succès', res.message, 'success');
                    $table.bootstrapTable('refresh');
                }
            },
            error: function(xhr) { Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error'); },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Valider la sortie')
        });
    });

    // Quick Adds
    $('#btn-quickadd-type-cons').on('click', () => {
        const modalEl = document.getElementById('modal-consommable');
        const originalFocus = modalEl.getAttribute('tabindex'); modalEl.removeAttribute('tabindex');
        $formType[0].reset(); $modalType.show();
        $formType.off('submit').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: route('parc-info.consommables.store-type'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $modalType.hide();
                    $('#select-type-consommable').append(new Option(res.data.nom, res.data.id, true, true)).trigger('change');
                },
                complete: () => { if (originalFocus) modalEl.setAttribute('tabindex', originalFocus); }
            });
        });
    });

    $('#btn-quickadd-fournisseur').on('click', () => {
        const modalEl = document.getElementById('modal-consommable');
        const originalFocus = modalEl.getAttribute('tabindex'); modalEl.removeAttribute('tabindex');
        $formFournisseur[0].reset(); $modalFournisseur.show();
        $formFournisseur.off('submit').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: route('parc-info.licences.store-fournisseur'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $modalFournisseur.hide();
                    $('#select-fournisseur').append(new Option(res.data.nom, res.data.id, true, true)).trigger('change');
                },
                complete: () => { if (originalFocus) modalEl.setAttribute('tabindex', originalFocus); }
            });
        });
    });

    // Fix scroll modales
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length) $('body').addClass('modal-open');
    });

    $('.select2-modal').select2({ theme: 'bootstrap-5' });
});
