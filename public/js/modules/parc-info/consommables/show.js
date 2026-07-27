/**
 * Gestion du détail des Consommables - Module Parc Info
 * Pattern: Inline Editing
 */

$(function () {
    let isEditMode = false;
    const $formFiche = $('#ficheForm');
    const $btnEditToggle = $('#btn-edit-toggle');
    const $ficheActions = $('#fiche-actions');
    const $fields = $formFiche.find('.field-input');

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
            text: 'Cette action est irréversible.',
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

    // Fix pour le focus des modales superposées
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length) {
            $('body').addClass('modal-open');
        }
    });
});
