/**
 * Fiche Consommable - Module Parc Info
 * Pattern: Show & Edit mode "In-place" + Stock movements + Layered selection modals
 */

document.addEventListener('DOMContentLoaded', function() {
    const $form = $('#form-edit-consommable');
    const $viewActions = $('#view-actions');
    const $formActions = $('#form-actions');
    const $btnEnableEdit = $('#btn-enable-edit');
    const $btnSaveEdit = $('#btn-save-edit');

    const $modalConsommer = new bootstrap.Modal('#modal-consommer-consommable');
    const $formConsommer = $('#form-consommer-consommable');
    const $btnOpenAppro = $('#btn-open-appro');
    const $btnOpenConsommer = $('#btn-open-consommer');

    // Modale de sélection d'équipement
    const $modalEq = new bootstrap.Modal('#equipementSelectionModal');
    let selectedEq = null;

    const consommableId = window.location.pathname.split('/').filter(Boolean).pop();

    // ── GESTION DU MODE ÉDITION ──
    $btnEnableEdit.on('click', function() {
        $form.find('input, select, textarea').prop('disabled', false);
        $viewActions.addClass('d-none');
        $formActions.removeClass('d-none');
    });

    $btnSaveEdit.on('click', function() {
        $btnSaveEdit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');
        $.ajax({
            url: route('parc-info.consommables.update', consommableId),
            method: 'POST',
            data: $form.serialize() + '&_method=PUT',
            success: function(res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Mis à jour !', text: res.message, timer: 1500 }).then(() => window.location.reload());
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Erreur', 'error');
                $btnSaveEdit.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // ── RÉAPPROVISIONNEMENT ──
    $btnOpenAppro.on('click', function() {
        Swal.fire({
            title: 'Réapprovisionner le stock',
            input: 'number',
            inputLabel: 'Quantité reçue',
            showCancelButton: true,
            confirmButtonText: 'Enregistrer',
            showLoaderOnConfirm: true,
            preConfirm: (val) => {
                if (!val || val <= 0) return Swal.showValidationMessage('Quantité invalide');
                return $.ajax({
                    url: route('parc-info.consommables.approvisionner', consommableId),
                    method: 'POST',
                    data: { quantite: val }
                }).catch(err => Swal.showValidationMessage(err.responseJSON?.message || 'Erreur'));
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Succès', result.value.message, 'success').then(() => window.location.reload());
            }
        });
    });

    // ── CONSOMMATION ──
    $btnOpenConsommer.on('click', function() {
        $formConsommer[0].reset();
        $('#consommation-equipement-id').val('');
        $('#consommation-equipement-label').val('');
        selectedEq = null;
        $modalConsommer.show();
    });

    // Ouverture de la modale de sélection sur la modale de consommation
    $('#btn-select-equipement').on('click', function() {
        loadEquipements();
        $modalEq.show();
    });

    // ── RECHERCHE ÉQUIPEMENTS ──
    let eqSearchTimeout = null;
    $('#eq-search, #eq-filter-statut').on('input change', () => {
        clearTimeout(eqSearchTimeout);
        eqSearchTimeout = setTimeout(loadEquipements, 500);
    });

    function loadEquipements() {
        $('#eq-list').addClass('opacity-50');
        $('#eq-skeleton').removeClass('d-none');
        
        $.ajax({
            url: route('parc-info.search-equipements'),
            data: {
                q: $('#eq-search').val(),
                statut: $('#eq-filter-statut').val()
            },
            success: function(data) {
                let html = '';
                data.forEach(e => {
                    html += `
                        <tr class="eq-row" data-id="${e.id}" data-label="${e.code} - ${e.modele}">
                            <td class="text-center">
                                <div class="form-check"><input class="form-check-input" type="radio" name="eq-radio" value="${e.id}"></div>
                            </td>
                            <td><span class="fw-bold text-primary">${e.code}</span></td>
                            <td>${e.marque} <strong>${e.modele}</strong></td>
                            <td><small>${e.emplacement}</small></td>
                            <td>${e.statut_label}</td>
                        </tr>
                    `;
                });
                $('#eq-list').html(html || '<tr><td colspan="5" class="text-center py-4">Aucun équipement trouvé.</td></tr>').removeClass('opacity-50');
                $('#eq-skeleton').addClass('d-none');
                
                // Sélection au clic sur la ligne
                $('.eq-row').on('click', function() {
                    $(this).find('input').prop('checked', true);
                    $('.eq-row').removeClass('eq-row-selected');
                    $(this).addClass('eq-row-selected');
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

    // Soumission de la consommation
    $formConsommer.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-consommation');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');

        $.ajax({
            url: route('parc-info.consommables.consommer', consommableId),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalConsommer.hide();
                    Swal.fire('Succès', res.message, 'success').then(() => window.location.reload());
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la sortie', 'error');
            },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Valider la sortie')
        });
    });

    // Fix pour les modales superposées (scroll)
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length) {
            $('body').addClass('modal-open');
        }
    });
});
