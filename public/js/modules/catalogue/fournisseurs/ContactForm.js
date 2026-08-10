/**
 * ContactForm.js — modale duale création/édition d'un contact fournisseur.
 *
 * Calquée sur FournisseurForm : un seul formulaire pour les deux sens, les
 * erreurs 422 rendues champ par champ, et l'état du bouton pendant l'envoi.
 * Les routes sont IMBRIQUÉES sous le fournisseur, d'où l'identifiant de
 * celui-ci passé au constructeur.
 */
export class ContactForm {
    constructor(fournisseurId, options = {}) {
        this.fournisseurId = fournisseurId;
        this.$modal = $('#contactModal');
        this.$form = $('#contact-form');
        this.onSaved = options.onSaved ?? (() => {});
        this._initSubmission();
        this._clearErrorsOnInput();
    }

    openForAdd() {
        this._reset();
        $('#contact-modal-title-text').text('Nouveau contact');
        // Un contact ajouté est actif par défaut : c'est le cas courant.
        $('#c-est-actif').prop('checked', true);
        this.$modal.modal('show');
    }

    openForEdit(id) {
        $.ajax({
            url: route('catalogue.contacts.show', [this.fournisseurId, id]),
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                if (!res.success) return;

                const data = res.data;
                this._reset();

                $('#contact-id').val(data.id);
                $('#contact-modal-title-text').text('Modifier le contact');
                $('#c-nom').val(data.nom ?? '');
                $('#c-prenom').val(data.prenom ?? '');
                $('#c-fonction').val(data.fonction ?? '');
                $('#c-telephone').val(data.telephone ?? '');
                $('#c-email').val(data.email ?? '');
                $('#c-notes').val(data.notes ?? '');
                $('#c-est-principal').prop('checked', !!data.est_principal);
                $('#c-est-actif').prop('checked', !!data.est_actif);

                this.$modal.modal('show');
            },
            error: () => Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Impossible de charger ce contact.',
            }),
        });
    }

    _reset() {
        this.$form[0].reset();
        this._clearErrors();
        $('#contact-id').val('');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();

            const id = $('#contact-id').val();
            const url = id
                ? route('catalogue.contacts.update', [this.fournisseurId, id])
                : route('catalogue.contacts.store', this.fournisseurId);
            const method = id ? 'PUT' : 'POST';

            const $btn = $('#btn-contact-save');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url,
                method,
                data: this.$form.serialize(),
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                        this.onSaved(res);
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
                },
            });
        });
    }

    _displayErrors(errors) {
        this._clearErrors();
        $.each(errors, (field, messages) => {
            const $field = this.$form.find(`[name="${field}"]`);
            $field.addClass('is-invalid');
            $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
        });
    }

    _clearErrors() {
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
    }

    _clearErrorsOnInput() {
        this.$form.on('input change', '.is-invalid', function () {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });
    }
}
