/**
 * Gestion des Licences - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Modales
 */

window.licencesQueryParams = function(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
        logiciel_id: $('#filter-logiciel').val(),
        statut: $('#filter-statut').val()
    };
};

window.logicielFormatter = function(value, row) {
    return `<a href="${route('parc-info.licences.show', row.id)}" class="fw-bold text-primary text-decoration-none">${value}</a>`;
};

window.expirationFormatter = function(value, row) {
    const validite = row.statut_validite;
    let badgeClass = 'bg-success';
    if (validite === 'EXPIREE') badgeClass = 'bg-danger';
    else if (validite === 'ALERTE') badgeClass = 'bg-warning text-dark';
    return `<span class="badge ${badgeClass}">${value}</span>`;
};

window.utilisationFormatter = function(value, row) {
    const color = value > 90 ? 'bg-danger' : 'bg-success';
    return `
        <div class="d-flex align-items-center gap-2" style="min-width: 100px;">
            <div class="progress flex-grow-1" style="height: 6px;">
                <div class="progress-bar ${color}" role="progressbar" style="width: ${value}%"></div>
            </div>
            <span class="small fw-bold">${Math.round(value)}%</span>
        </div>
    `;
};

window.actionsFormatter = function(value, row) {
    return `
        <div class="btn-group btn-group-sm">
            <a href="${route('parc-info.licences.show', row.id)}" class="btn btn-light border" title="Voir détails">
                <i class="fas fa-eye text-primary"></i>
            </a>
            <button class="btn btn-light border btn-action-edit" data-id="${row.id}" title="Modifier">
                <i class="fas fa-edit text-info"></i>
            </button>
            <button class="btn btn-light border btn-action-toggle" data-id="${row.id}" title="Changer statut">
                <i class="fas fa-power-off ${row.actif ? 'text-danger' : 'text-success'}"></i>
            </button>
        </div>
    `;
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#licences-table');
    const $modalLicence = new bootstrap.Modal('#modal-licence');
    const $modalFournisseur = new bootstrap.Modal('#modal-quickadd-fournisseur');
    const $formLicence = $('#form-licence');
    const $formFournisseur = $('#form-quickadd-fournisseur');
    const $btnSaveLicence = $('#btn-save-licence');
    
    const $btnEditToolbar = $('#btn-edit');
    const $btnToggleToolbar = $('#btn-toggle-status');
    const $btnDeleteToolbar = $('#btn-delete');

    // Mettre à jour les KPIs après le chargement des données
    $table.on('load-success.bs.table', function (e, data) {
        if (data.stats) {
            $('#kpi-total').text(data.stats.total);
            $('#kpi-expirant').text(data.stats.expirant_30j);
            $('#kpi-surexploitees').text(data.stats.surexploitees);
            $('#kpi-budget').text(new Intl.NumberFormat('fr-FR').format(data.stats.cout_total_annuel) + ' €');
        }
    });

    // ── GESTION DES FILTRES ──
    $('#btn-apply-filters').on('click', () => $table.bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-logiciel, #filter-statut').val('').trigger('change');
        $table.bootstrapTable('refresh');
    });

    // ── GESTION DE LA SELECTION ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        $btnEditToolbar.prop('disabled', !hasOne);
        $btnToggleToolbar.prop('disabled', !hasOne);
        $btnDeleteToolbar.prop('disabled', !hasOne);
    });

    // ── AJOUT LICENCE ──
    $('#btn-add').on('click', function() {
        $formLicence[0].reset();
        $('#licence-id').val('');
        $('#modalLicenceLabel span').text('Nouvelle Licence');
        $formLicence.find('.select2-modal').val('').trigger('change');
        $modalLicence.show();
    });

    // ── MODIFICATION LICENCE ──
    function editLicence(id) {
        $.ajax({
            url: route('parc-info.licences.show', id) + '?json=1',
            method: 'GET',
            success: function(l) {
                $('#licence-id').val(l.id);
                $formLicence.find('[name="logiciel_id"]').val(l.logiciel_id).trigger('change');
                $formLicence.find('[name="cle_licence"]').val(l.cle_licence);
                $formLicence.find('[name="numero_contrat"]').val(l.numero_contrat);
                $formLicence.find('[name="type_activation"]').val(l.type_activation);
                $formLicence.find('[name="modele_licencing"]').val(l.modele_licencing);
                $formLicence.find('[name="nombre_postes_accordes"]').val(l.nombre_postes_accordes);
                $formLicence.find('[name="statut"]').val(l.statut);
                $formLicence.find('[name="date_acquisition"]').val(l.date_acquisition ? l.date_acquisition.split('T')[0] : '');
                $formLicence.find('[name="date_activation"]').val(l.date_activation ? l.date_activation.split('T')[0] : '');
                $formLicence.find('[name="date_expiration"]').val(l.date_expiration ? l.date_expiration.split('T')[0] : '');
                $formLicence.find('[name="cout_unitaire"]').val(l.cout_unitaire);
                $formLicence.find('[name="cout_total"]').val(l.cout_total);
                $formLicence.find('[name="fournisseur_id"]').val(l.fournisseur_id).trigger('change');
                $formLicence.find('[name="contact_support_id"]').val(l.contact_support_id).trigger('change');
                $formLicence.find('[name="contrat_maintenance_id"]').val(l.contrat_maintenance_id).trigger('change');
                
                $('#modalLicenceLabel span').text('Modifier la Licence');
                $modalLicence.show();
            }
        });
    }

    $btnEditToolbar.on('click', () => editLicence($table.bootstrapTable('getSelections')[0].id));
    $(document).on('click', '.btn-action-edit', function() { editLicence($(this).data('id')); });

    // ── ENREGISTREMENT LICENCE ──
    $formLicence.on('submit', function(e) {
        e.preventDefault();
        const id = $('#licence-id').val();
        const url = id ? route('parc-info.licences.update', id) : route('parc-info.licences.store');
        const method = id ? 'PUT' : 'POST';
        
        $btnSaveLicence.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: url,
            method: method,
            data: $formLicence.serialize(),
            success: function(res) {
                if (res.success) {
                    $modalLicence.hide();
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
            complete: function() {
                $btnSaveLicence.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // ── TOGGLE STATUT ──
    function toggleStatus(id) {
        Swal.fire({
            title: 'Changer le statut actif/inactif ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, changer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.licences.toggle', id),
                    method: 'PATCH',
                    success: function(res) {
                        Swal.fire('Mis à jour !', res.message, 'success');
                        $table.bootstrapTable('refresh');
                    }
                });
            }
        });
    }

    $btnToggleToolbar.on('click', () => toggleStatus($table.bootstrapTable('getSelections')[0].id));

    // ── QUICK ADD FOURNISSEUR (MODALE) ──
    $('#btn-quickadd-fournisseur').on('click', function() {
        $formFournisseur[0].reset();
        $modalFournisseur.show();
    });

    $formFournisseur.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-fournisseur');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: route('parc-info.licences.store-fournisseur'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalFournisseur.hide();
                    const newF = res.data;
                    const newOption = new Option(newF.nom, newF.id, true, true);
                    $('#select-fournisseur').append(newOption).trigger('change');
                    Swal.fire('Ajouté !', res.message, 'success');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    $('.select2-modal').select2({ theme: 'bootstrap-5' });
});
