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

    // ── AFFECTATION ──
    $('#type_affectation').on('change', function() {
        if (this.value === 'user') {
            $('#div-employe').removeClass('d-none');
            $('#div-equipement').addClass('d-none');
        } else {
            $('#div-employe').addClass('d-none');
            $('#div-equipement').removeClass('d-none');
        }
    });

    $('#form-affecter-licence').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: route('parc-info.licences.affecter', licenceId),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire('Succès', res.message, 'success').then(() => window.location.reload());
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'affectation', 'error');
            }
        });
    });

    // Select2 AJAX Employés
    $('.select2-ajax-employes').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modal-affectation'),
        ajax: {
            url: route('parc-info.ordinateurs.search-employes'),
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({
                results: data.map(item => ({
                    id: item.dossier_employe_id,
                    text: `${item.nom} ${item.prenom} (${item.dossier_employe_id})`
                }))
            })
        },
        minimumInputLength: 2,
        placeholder: 'Chercher un employé...'
    });

    // Select2 AJAX Postes
    $('.select2-ajax-postes').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modal-affectation'),
        ajax: {
            url: route('parc-info.ordinateurs.search-postes'),
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({
                results: data.map(item => ({
                    id: item.id,
                    text: `${item.code_inventaire} - ${item.modele}`
                }))
            })
        },
        minimumInputLength: 2,
        placeholder: 'Chercher un poste...'
    });
});
