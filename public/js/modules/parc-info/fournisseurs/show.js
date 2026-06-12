/**
 * Gestion du détail des Fournisseurs - Module Parc Info
 * Pattern: Inline Editing + Contacts CRUD (AJAX)
 */

$(function () {
    let isEditMode = false;
    const $formFiche = $('#ficheForm');
    const $btnEditToggle = $('#btn-edit-toggle');
    const $ficheActions = $('#fiche-actions');
    const contactModal = new bootstrap.Modal('#contactModal');
    const $contactForm = $('#contactForm');

    // Cache fields
    const $fields = $formFiche.find('.field-input');

    // ── MODE EDITION INLINE ──
    function setEditMode(on) {
        isEditMode = on;
        $fields.each(function () {
            // Do not enable code field if it shouldn't be edited (usually code remains immutable, but let's follow the standard rule)
            // if ($(this).attr('name') === 'code') {
            //     return;
            // }
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
            url: `/parc-info/informatique/fournisseurs/${fournisseurId}`,
            method: 'PUT',
            data: $formFiche.serialize(),
            success: function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });

                    // Update header display
                    $('#header-nom').text(res.data.nom);
                    $('#header-code').text(res.data.code);
                    $('#header-email').text(res.data.email || '—');
                    $('#header-telephone').text(res.data.telephone || '—');

                    if (res.data.type) {
                        $('#header-badge-type').text(res.data.type).show();
                    } else {
                        $('#header-badge-type').hide();
                    }

                    // Disable edit mode without resetting inputs
                    isEditMode = false;
                    $fields.prop('disabled', true);
                    $btnEditToggle.removeClass('btn-outline-secondary').addClass('btn-primary')
                        .html('<i class="bi bi-pencil me-1"></i> Modifier');
                    $ficheActions.removeClass('d-flex').addClass('d-none');
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
            url: `/parc-info/informatique/fournisseurs/${fournisseurId}/toggle`,
            method: 'PATCH',
            data: { _token: csrfToken },
            success: function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    const statusText = res.est_actif ? 'Actif' : 'Inactif';
                    const badgeClass = res.est_actif ? 'success' : 'danger';

                    $('#badge-status')
                        .text(statusText)
                        .removeClass('bg-success-subtle text-success border-success-subtle bg-danger-subtle text-danger border-danger-subtle')
                        .addClass(`bg-${badgeClass}-subtle text-${badgeClass} border-${badgeClass}-subtle`);
                }
            },
            error: function (xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Impossible de changer le statut', 'error');
            }
        });
    });

    // ── SUPPRESSION FOURNISSEUR ──
    $('#btn-delete').on('click', function () {
        Swal.fire({
            title: 'Supprimer ce fournisseur ?',
            text: "Cette action est irréversible et impossible si des licences y sont liées.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/parc-info/informatique/fournisseurs/${fournisseurId}`,
                    method: 'DELETE',
                    data: { _token: csrfToken },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire('Supprimé !', res.message, 'success').then(() => {
                                window.location.href = '/parc-info/informatique/fournisseurs';
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

    // ── CRUD CONTACTS ──

    // Ouvre la modale
    function openContactModal(contact = null) {
        $contactForm[0].reset();
        if (contact) {
            $('#modalTitle').find('span').text('Modifier le contact');
            $('#contact_id').val(contact.id);
            $('#c_nom').val(contact.nom);
            $('#c_prenom').val(contact.prenom);
            $('#c_fonction').val(contact.fonction);
            $('#c_email').val(contact.email);
            $('#c_telephone').val(contact.telephone);
        } else {
            $('#modalTitle').find('span').text('Ajouter un contact');
            $('#contact_id').val('');
        }
        contactModal.show();
    }

    $('#btn-add-contact').on('click', function () {
        openContactModal();
    });

    // Edit contact click
    $(document).on('click', '.btn-edit-contact', function () {
        const id = $(this).data('id');
        const $tr = $(`tr[data-contact-id="${id}"]`);

        const contact = {
            id: id,
            nom: $tr.find('.fw-bold').text().split(' ')[0] || '',
            prenom: $tr.find('.fw-bold').text().split(' ').slice(1).join(' ') || '',
            fonction: $tr.find('td:nth-child(2)').text().trim() === '—' ? '' : $tr.find('td:nth-child(2)').text().trim(),
            email: $tr.find('td:nth-child(3) a').text().trim(),
            telephone: $tr.find('td:nth-child(4)').text().trim() === '—' ? '' : $tr.find('td:nth-child(4)').text().trim()
        };

        openContactModal(contact);
    });

    // Save contact (Create or Update)
    $contactForm.on('submit', function (e) {
        e.preventDefault();
        const contactId = $('#contact_id').val();
        const isEdit = !!contactId;
        const url = isEdit
            ? `/parc-info/informatique/fournisseurs/${fournisseurId}/contacts/${contactId}`
            : `/parc-info/informatique/fournisseurs/${fournisseurId}/contacts`;
        const method = isEdit ? 'PUT' : 'POST';

        const $btn = $('#btn-save-contact');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>...');

        $.ajax({
            url: url,
            method: method,
            data: $contactForm.serialize(),
            success: function (res) {
                if (res.success) {
                    contactModal.hide();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });

                    const c = res.data;
                    const emailHtml = c.email
                        ? `<a href="mailto:${c.email}" class="text-decoration-none"><i class="bi bi-envelope me-1"></i>${c.email}</a>`
                        : '—';
                    const telText = c.telephone || '—';
                    const fonctionText = c.fonction || '—';
                    const nomComplet = `${c.nom} ${c.prenom || ''}`.trim();

                    const rowHtml = `
                        <tr data-contact-id="${c.id}">
                            <td><div class="fw-bold">${nomComplet}</div></td>
                            <td>${fonctionText}</td>
                            <td>${emailHtml}</td>
                            <td>${telText}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-info btn-sm btn-edit-contact" data-id="${c.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-contact" data-id="${c.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    if (isEdit) {
                        $(`tr[data-contact-id="${c.id}"]`).replaceWith(rowHtml);
                    } else {
                        $('#contacts-empty-row').remove();
                        $('#contacts-tbody').append(rowHtml);
                    }
                }
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Enregistrer');
            }
        });
    });

    // Delete contact
    $(document).on('click', '.btn-delete-contact', function () {
        const contactId = $(this).data('id');
        Swal.fire({
            title: 'Supprimer ce contact ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/parc-info/informatique/fournisseurs/${fournisseurId}/contacts/${contactId}`,
                    method: 'DELETE',
                    data: { _token: csrfToken },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire('Supprimé !', res.message, 'success');
                            $(`tr[data-contact-id="${contactId}"]`).remove();

                            if ($('#contacts-tbody tr').length === 0) {
                                $('#contacts-tbody').html(`
                                    <tr id="contacts-empty-row">
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-person-x fs-1 opacity-50 d-block mb-2"></i>
                                            Aucun contact enregistré
                                        </td>
                                    </tr>
                                `);
                            }
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                    }
                });
            }
        });
    });
});
