/**
 * Fiche Licence - Module Parc Info
 * Pattern: Show & Edit mode "In-place"
 */

document.addEventListener('DOMContentLoaded', function() {
    const $form = $('#form-edit-licence');
    const $viewActions = $('#view-actions');
    const $formActions = $('#form-actions');
    const $btnEnableEdit = $('#btn-enable-edit');
    const $btnSaveEdit = $('#btn-save-edit');
    const $btnOpenRenouveler = $('#btn-open-renouveler');
    const $btnQuickAddFournisseur = $('#btn-quickadd-fournisseur');
    const $modalFournisseur = new bootstrap.Modal('#modal-quickadd-fournisseur');
    const $formFournisseur = $('#form-quickadd-fournisseur');

    const licenceId = window.location.pathname.split('/').pop();

    // ── GESTION DU MODE ÉDITION ──
    $btnEnableEdit.on('click', function() {
        $form.find('input, select, textarea').prop('disabled', false);
        $btnQuickAddFournisseur.removeClass('d-none');
        $viewActions.addClass('d-none');
        $formActions.removeClass('d-none');
    });

    // ── ENREGISTREMENT ──
    $btnSaveEdit.on('click', function() {
        $btnSaveEdit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: route('parc-info.licences.update', licenceId),
            method: 'PUT',
            data: $form.serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Mis à jour !',
                        text: res.message,
                        timer: 1500
                    }).then(() => window.location.reload());
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
                $btnSaveEdit.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // ── RENOUVELLEMENT ──
    $btnOpenRenouveler.on('click', function() {
        Swal.fire({
            title: 'Renouveler la licence',
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label">Nouvelle date d'expiration</label>
                    <input type="date" id="swal-date" class="form-control">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Coût du renouvellement (EUR)</label>
                    <input type="number" id="swal-cout" class="form-control" placeholder="0.00">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Renouveler',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const date = document.getElementById('swal-date').value;
                const cout = document.getElementById('swal-cout').value;
                if (!date) return Swal.showValidationMessage('La date est requise');
                return $.ajax({
                    url: route('parc-info.licences.renouveler', licenceId),
                    method: 'POST',
                    data: {
                        date_nouvelle_expiration: date,
                        cout_renouvellement: cout
                    }
                }).catch(err => {
                    Swal.showValidationMessage(err.responseJSON?.message || 'Erreur lors du renouvellement');
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Succès', result.value.message, 'success').then(() => window.location.reload());
            }
        });
    });

    // ── QUICK ADD FOURNISSEUR ──
    $btnQuickAddFournisseur.on('click', () => {
        $formFournisseur[0].reset();
        $modalFournisseur.show();
    });

    $formFournisseur.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-fournisseur');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');

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
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error');
            },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    // ── SEARCH DEBOUNCE ──
    let searchTimeout = null;

    function searchTargets(type, query, $container) {
        $container.html('<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Chargement...</div>');

        let url = '';
        let data = { q: query };

        if (type === 'user') {
            url = route('parc-info.ordinateurs.search-employes');
        } else {
            url = route('parc-info.search-equipements');
            data.type = type;
        }

        $.ajax({
            url: url,
            method: 'GET',
            data: data,
            success: function (results) {
                $container.empty();
                if (results.length === 0) {
                    $container.html('<div class="text-center py-4 text-muted"><i class="fas fa-search-minus me-2"></i>Aucun résultat trouvé.</div>');
                    return;
                }

                results.forEach(item => {
                    let cardHtml = '';
                    if (type === 'user') {
                        cardHtml = `
                            <div class="card border mb-2 shadow-sm rounded-3">
                                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><i class="fas fa-user text-muted me-2"></i>${item.nom} ${item.prenom}</h6>
                                        <small class="text-muted d-block">ID : ${item.dossier_employe_id}</small>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm btn-submit-affectation" data-type="user" data-id="${item.dossier_employe_id}">
                                        Affecter <i class="fas fa-check-circle ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        cardHtml = `
                            <div class="card border mb-2 shadow-sm rounded-3">
                                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><i class="fas fa-desktop text-muted me-2"></i>${item.code}</h6>
                                        <small class="text-muted d-block">${item.marque} ${item.modele} — Emplacement : ${item.emplacement}</small>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm btn-submit-affectation" data-type="device" data-id="${item.id}">
                                        Affecter <i class="fas fa-check-circle ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                    $container.append(cardHtml);
                });
            },
            error: function () {
                $container.html('<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur lors de la recherche.</div>');
            }
        });
    }

    // Input listeners for search
    $('.search-target-input').on('keyup input', function () {
        const $input = $(this);
        const query = $input.val().trim();
        const type = $input.data('type');
        const $container = $input.closest('.tab-pane').find('.target-results-container');

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            $container.html('<div class="text-center py-4 text-muted small"><i class="fas fa-keyboard me-2"></i>Saisissez au moins 2 caractères pour commencer la recherche...</div>');
            return;
        }

        searchTimeout = setTimeout(() => {
            searchTargets(type, query, $container);
        }, 300);
    });

    // Handle affectation click
    $(document).on('click', '.btn-submit-affectation', function () {
        const $btn = $(this);
        const typeAffectation = $btn.data('type');
        const targetId = $btn.data('id');

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        const postData = {
            type_affectation: typeAffectation,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        if (typeAffectation === 'user') {
            postData.employe_id = targetId;
        } else {
            postData.equipement_id = targetId;
        }

        $.ajax({
            url: route('parc-info.licences.affecter', licenceId),
            method: 'POST',
            data: postData,
            success: function (res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès !',
                        text: res.message,
                        timer: 1500
                    }).then(() => window.location.reload());
                }
            },
            error: function (xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'affectation', 'error');
                $btn.prop('disabled', false).html('Affecter <i class="fas fa-check-circle ms-1"></i>');
            }
        });
    });

    // ── DÉSAFFECTATION ──
    $(document).on('click', '.btn-desaffecter', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Désaffecter la licence ?',
            text: "Cette licence ne sera plus associée à cet équipement ou employé.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, désaffecter',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/parc-info/informatique/licences/affectations/${id}/desaffecter`,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Succès', res.message, 'success').then(() => window.location.reload());
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la désaffectation', 'error');
                    }
                });
            }
        });
    });
});
