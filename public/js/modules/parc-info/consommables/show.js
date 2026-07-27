/**
 * Gestion du détail des Consommables - Module Parc Info
 * Pattern: Inline Editing + Redesigned Stock Movements
 */

$(function () {
    let isEditMode = false;
    const $formFiche = $('#ficheForm');
    const $btnEditToggle = $('#btn-edit-toggle');
    const $ficheActions = $('#fiche-actions');
    const $fields = $formFiche.find('.field-input');

    // EF-STK-05 — les modals de mouvements ont été retirés (entrées/sorties
    // portées par le module Stock) : instanciation défensive pour ne pas
    // casser le reste de l'écran.
    const elConsommer = document.getElementById('modal-consommer-consommable');
    const modalConsommer = elConsommer ? new bootstrap.Modal(elConsommer) : null;
    const $formConsommer = $('#form-consommer-consommable');
    const elEq = document.getElementById('equipementSelectionModal');
    const modalEq = elEq ? new bootstrap.Modal(elEq) : null;
    let selectedEq = null;

    if (elConsommer) {
        $('.select2-mouvement').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modal-consommer-consommable')
        });
    }

    // ── MODE EDITION INLINE ──
    function setEditMode(on) {
        isEditMode = on;
        $fields.each(function () {
            $(this).prop('disabled', !on);
        });

        if (on) {
            $btnEditToggle.removeClass('btn-primary').addClass('btn-outline-secondary')
                .html('<i class="bi bi-x-circle me-1"></i> Annuler');
            $ficheActions.removeClass('d-none').addClass('d-flex');
        } else {
            $btnEditToggle.removeClass('btn-outline-secondary').addClass('btn-primary')
                .html('<i class="bi bi-pencil me-1"></i> Modifier');
            $ficheActions.removeClass('d-flex').addClass('d-none');
            // Reset form to initial state
            $formFiche[0].reset();
        }
    }

    $btnEditToggle.on('click', function () {
        setEditMode(!isEditMode);
    });

    $('#btn-cancel-edit').on('click', function () {
        setEditMode(false);
    });

    // ── ENREGISTREMENT DE LA FICHE INFO ──
    $formFiche.on('submit', function (e) {
        e.preventDefault();
        const $btnSave = $('#btn-save-fiche');
        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...');

        $.ajax({
            url: `/parc-info/informatique/consommables/${consommableId}`,
            method: 'PUT',
            data: $formFiche.serialize(),
            success: function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });

                    // Disable edit mode without resetting inputs
                    isEditMode = false;
                    $fields.prop('disabled', true);
                    $btnEditToggle.removeClass('btn-outline-secondary').addClass('btn-primary')
                        .html('<i class="bi bi-pencil me-1"></i> Modifier');
                    $ficheActions.removeClass('d-flex').addClass('d-none');
                    
                    location.reload();
                }
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur de validation', msg || 'Une erreur est survenue', 'error');
            },
            complete: function () {
                $btnSave.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Enregistrer');
            }
        });
    });

    // ── TOGGLE STATUT ──
    $('#btn-toggle-status').on('click', function () {
        $.ajax({
            url: `/parc-info/informatique/consommables/${consommableId}/toggle`,
            method: 'PATCH',
            data: { _token: csrfToken },
            success: function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 }).then(() => location.reload());
                }
            },
            error: function (xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Impossible de changer le statut', 'error');
            }
        });
    });

    // ── SUPPRESSION CONSOMMABLE ──
    $('#btn-delete').on('click', function () {
        Swal.fire({
            title: 'Supprimer cet article ?',
            text: "Cette action est irréversible et impossible si des mouvements de stock y sont associés.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/parc-info/informatique/consommables/${consommableId}`,
                    method: 'DELETE',
                    data: { _token: csrfToken },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire('Supprimé !', res.message, 'success').then(() => {
                                window.location.href = '/parc-info/informatique/consommables';
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                    }
                });
            }
        });
    });

    // ── APPROVISIONNEMENT (REAPPRO) ──
    $('#btn-open-appro').on('click', function () {
        Swal.fire({
            title: 'Réapprovisionner le stock',
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">Quantité reçue <span class="text-danger">*</span></label>
                    <input type="number" id="appro-qty" class="form-control" min="1" value="1">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">Prix unitaire (€) (Optionnel)</label>
                    <input type="number" id="appro-price" class="form-control" min="0" step="0.01" placeholder="Par défaut: coût unitaire actuel">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">Référence commande (Optionnel)</label>
                    <input type="text" id="appro-ref" class="form-control" placeholder="ex: CMD-2026-001">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Valider',
            cancelButtonText: 'Annuler',
            focusConfirm: false,
            preConfirm: () => {
                const qty = document.getElementById('appro-qty').value;
                const price = document.getElementById('appro-price').value;
                const ref = document.getElementById('appro-ref').value;
                if (!qty || qty <= 0) {
                    Swal.showValidationMessage('Veuillez saisir une quantité valide supérieure à 0.');
                    return false;
                }
                return { quantite: qty, prix_unitaire: price, reference_commande: ref };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/parc-info/informatique/consommables/${consommableId}/approvisionner`,
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        ...result.value
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Succès', res.message, 'success').then(() => location.reload());
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error');
                    }
                });
            }
        });
    });

    // ── SORTIE DE STOCK (CONSOMMATION) ──
    $('#btn-open-consommer').on('click', function () {
        $formConsommer[0].reset();
        
        // Reset selections to default Target: NONE
        $('.aff-type-card').removeClass('selected');
        $('.aff-type-card').find('.check-icon').addClass('d-none');
        
        const $noneCard = $('.aff-type-card[data-value="NONE"]');
        $noneCard.addClass('selected');
        $noneCard.find('.check-icon').removeClass('d-none');
        $noneCard.find('input[type="radio"]').prop('checked', true);
        
        $('.target-input-section').addClass('d-none');
        $('#target-input-none').removeClass('d-none');

        $('#consommation-equipement-id').val('');
        $('#consommation-equipement-label').val('');
        $('#consommation-employe-id').val('');
        $('#consommation-employe-label').val('');
        $('#consommation-service-id').val('').trigger('change');
        $('#consommation-unite-id').val('').trigger('change');
        selectedEq = null;

        modalConsommer?.show();
    });

    // Target Selection Cards click
    $('.aff-type-card').on('click', function (e) {
        e.preventDefault();
        const val = $(this).data('value');

        $('.aff-type-card').removeClass('selected');
        $('.aff-type-card').find('.check-icon').addClass('d-none');

        $(this).addClass('selected');
        $(this).find('.check-icon').removeClass('d-none');
        $(this).find('input[type="radio"]').prop('checked', true);

        // Hide all inputs
        $('.target-input-section').addClass('d-none');
        
        // Reset inputs
        $('#consommation-equipement-id').val('');
        $('#consommation-equipement-label').val('');
        $('#consommation-employe-id').val('');
        $('#consommation-employe-label').val('');
        $('#consommation-service-id').val('').trigger('change');
        $('#consommation-unite-id').val('').trigger('change');
        selectedEq = null;

        // Show the active target section
        if (val === 'NONE') {
            $('#target-input-none').removeClass('d-none');
        } else if (val === 'EQUIPEMENT') {
            $('#target-input-equipement').removeClass('d-none');
        } else if (val === 'EMPLOYE') {
            $('#target-input-employe').removeClass('d-none');
        } else if (val === 'SERVICE') {
            $('#target-input-service').removeClass('d-none');
        } else if (val === 'UNITE') {
            $('#target-input-unite').removeClass('d-none');
        }
    });

    // Ouvrir sélection d'équipement
    $('#btn-select-equipement').on('click', function () {
        const typeFilter = $('#consommation-equipement-type').val();
        $('#eq-filter-type').val(typeFilter).trigger('change');
        loadEquipements();
        modalEq?.show();
    });

    // Ouvrir sélection d'employé
    $('#btn-select-employe').on('click', function () {
        $('#employeSelectionModal').modal('show');
    });

    // Catcher l'événement employe:selected
    $(document).on('employe:selected', function (e, emp) {
        $('#consommation-employe-id').val(emp.id);
        $('#consommation-employe-label').val(`${emp.nom} (${emp.matricule})`);
    });

    // Recherche d'équipement dans la modale
    let eqSearchTimeout = null;
    $('#eq-search, #eq-filter-statut, #eq-filter-type').on('input change', () => {
        clearTimeout(eqSearchTimeout);
        eqSearchTimeout = setTimeout(loadEquipements, 300);
    });

    function loadEquipements() {
        $('#eq-list').addClass('opacity-50');
        $('#eq-skeleton').removeClass('d-none');
        
        $.ajax({
            url: '/parc-info/search/equipements',
            data: {
                q: $('#eq-search').val(),
                statut: $('#eq-filter-statut').val(),
                type: $('#eq-filter-type').val()
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
                
                // Clic sur ligne
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
            modalEq?.hide();
        }
    });

    // Soumission formulaire de consommation
    $formConsommer.on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btn-save-consommation');
        
        // Basic validation for selected target type
        const targetType = $('input[name="target_type"]:checked').val();
        if (targetType === 'EQUIPEMENT' && !$('#consommation-equipement-id').val()) {
            Swal.fire('Champs requis', 'Veuillez sélectionner un équipement.', 'warning');
            return;
        }
        if (targetType === 'EMPLOYE' && !$('#consommation-employe-id').val()) {
            Swal.fire('Champs requis', 'Veuillez sélectionner un employé.', 'warning');
            return;
        }
        if (targetType === 'SERVICE' && !$('#consommation-service-id').val()) {
            Swal.fire('Champs requis', 'Veuillez sélectionner un service.', 'warning');
            return;
        }
        if (targetType === 'UNITE' && !$('#consommation-unite-id').val()) {
            Swal.fire('Champs requis', 'Veuillez sélectionner une unité.', 'warning');
            return;
        }

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> validation...');

        $.ajax({
            url: `/parc-info/informatique/consommables/${consommableId}/consommer`,
            method: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                if (res.success) {
                    modalConsommer?.hide();
                    Swal.fire('Succès', res.message, 'success').then(() => location.reload());
                }
            },
            error: function (xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la sortie de stock', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-2"></i>Valider la sortie');
            }
        });
    });

    // Fix pour le focus des modales superposées
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length) {
            $('body').addClass('modal-open');
        }
    });
});
