import { showAlert, confirmAction } from '../../../components/utilities.js';

$(function() {
    let editMode = false;

    // Toggle mode édition de la fiche technique
    function setEditMode(enable) {
        editMode = enable;
        const form = $('#ficheForm');

        // Champs autorisés
        form.find('.editable-field').prop('disabled', !enable);

        // Champs interdits (identifiants immuables)
        $('#f_code_inventaire').prop('disabled', true);

        // Boutons QuickAdd
        $('.btn-add-ref').toggleClass('d-none', !enable);

        // Actions
        $('#fiche-actions').toggleClass('d-none', !enable);
        $('#btn-edit-toggle').toggleClass('active', enable);

        if (enable) {
            $('#btn-edit-toggle').html('<i class="bi bi-x-lg me-1"></i> Quitter').removeClass('btn-outline-primary').addClass('btn-outline-danger');
        } else {
            $('#btn-edit-toggle').html('<i class="bi bi-pencil me-1"></i> Éditer').removeClass('btn-outline-danger').addClass('btn-outline-primary');
        }
    }

    $('#btn-edit-toggle').on('click', () => {
        setEditMode(!editMode);
    });

    $('#btn-cancel-edit').on('click', () => {
        location.reload(); // Annule en rechargeant
    });

    $('#ficheForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btn-save-fiche');
        const originalHtml = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...').prop('disabled', true);

        $.ajax({
            url: $(this).attr('action'),
            method: 'PUT',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    setEditMode(false);
                    showAlert('Succès', res.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            },
            error: function(xhr) {
                btn.html(originalHtml).prop('disabled', false);
                if (xhr.status === 422) {
                    showAlert('Erreur', 'Veuillez vérifier les champs.', 'error');
                } else {
                    showAlert('Erreur', 'Une erreur est survenue.', 'error');
                }
            }
        });
    });

    // Modification du statut
    $('.btn-statut').on('click', function(e) {
        e.preventDefault();
        const statut = $(this).data('statut');

        Swal.fire({
            title: 'Changement de statut',
            html: `Vous allez passer l'équipement au statut <strong>${statut}</strong>.<br><br>Veuillez indiquer un motif :`,
            input: 'textarea',
            inputPlaceholder: 'Motif du changement...',
            showCancelButton: true,
            confirmButtonText: 'Confirmer',
            cancelButtonText: 'Annuler',
            inputValidator: (value) => {
                if (!value) return 'Le motif est obligatoire';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.routes.updateStatut,
                    method: 'PATCH',
                    data: {
                        statut: statut,
                        motif: result.value,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            showAlert('Succès', res.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        }
                    }
                });
            }
        });
    });

    // Désaffectation
    $('#btn-desaffecter, .btn-desaffecter-trigger').on('click', function() {
        confirmAction({
            title: 'Désaffecter cet équipement ?',
            text: 'Il sera retiré de son emplacement actuel et retournera "en stock".',
            confirmButtonText: 'Oui, désaffecter',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.routes.desaffecter,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            showAlert('Succès', res.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        }
                    }
                });
            }
        });
    });

    // Nouvelle Affectation (Modale)
    $('#btn-nouvelle-affectation, #btn-nouvelle-affectation-2').on('click', function() {
        $('#affectationModal').modal('show');
    });

    $('.aff-type-card').on('click', function() {
        const type = $(this).data('type');
        $('#aff_type_cible').val(type);

        $('.aff-type-card').removeClass('border-primary bg-primary bg-opacity-10');
        $(this).addClass('border-primary bg-primary bg-opacity-10');

        $('#aff-summary-employe, #aff-summary-poste, #aff-summary-local').addClass('d-none');
        $('#aff_employe_id, #aff_poste_id, #aff_local_id').val('');
        $('#btn-submit-affectation').prop('disabled', true);

        if (type === 'EMPLOYE') $('#employeSelectionModal').modal('show');
        else if (type === 'POSTE') $('#posteSelectionModal').modal('show');
        else if (type === 'LOCAL') $('#localSelectionModal').modal('show');
    });

    $('.aff-reselect').on('click', function() {
        const type = $('#aff_type_cible').val();
        if (type === 'EMPLOYE') $('#employeSelectionModal').modal('show');
        else if (type === 'POSTE') $('#posteSelectionModal').modal('show');
        else if (type === 'LOCAL') $('#localSelectionModal').modal('show');
    });

    // Écoute des sélections (venant de selection_modals.js)
    $(document).on('employe:selected', (e, data) => {
        if (!$('#affectationModal').is(':visible')) return;
        $('#aff_employe_id').val(data.id);
        $('#aff-employe-name').text(data.text);
        $('#aff-employe-sub').text(data.sub);
        $('#aff-summary-employe').removeClass('d-none');
        $('#btn-submit-affectation').prop('disabled', false);
    });

    $(document).on('poste:selected', (e, data) => {
        if (!$('#affectationModal').is(':visible')) return;
        $('#aff_poste_id').val(data.id);
        $('#aff-poste-name').text(data.text);
        $('#aff-poste-sub').text(data.sub);
        $('#aff-summary-poste').removeClass('d-none');
        $('#btn-submit-affectation').prop('disabled', false);
    });

    $(document).on('local:selected', (e, data) => {
        if (!$('#affectationModal').is(':visible')) return;
        $('#aff_local_id').val(data.id);
        $('#aff-local-name').text(data.text);
        $('#aff-local-sub').text(data.sub);
        $('#aff-summary-local').removeClass('d-none');
        $('#btn-submit-affectation').prop('disabled', false);
    });

    // Soumission Affectation
    $('#btn-submit-affectation').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...').prop('disabled', true);

        $.ajax({
            url: window.routes.storeAffectation,
            method: 'POST',
            data: $('#affectationForm').serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
            success: function(res) {
                if (res.success) {
                    $('#affectationModal').modal('hide');
                    showAlert('Succès', res.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            },
            error: function(xhr) {
                btn.html(originalHtml).prop('disabled', false);
                showAlert('Erreur', 'Impossible d\'enregistrer l\'affectation.', 'error');
            }
        });
    });

    // Champs conditionnels Fiche Technique
    $('#f_support_poe').on('change', function() {
        $('#div_f_poe_budget').toggle(this.checked);
    });
    $('#f_support_snmp').on('change', function() {
        $('#div_f_snmp_config').toggle(this.checked);
    });
});
