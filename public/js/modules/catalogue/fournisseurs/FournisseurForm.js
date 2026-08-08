/**
 * FournisseurForm.js — modale duale création/édition + validation + soumission AJAX
 */
export class FournisseurForm {
    /**
     * @param {string} modalSelector
     * @param {string} formSelector
     * @param {{onSaved: function}} options callback après enregistrement réussi
     */
    constructor(modalSelector, formSelector, options = {}) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.onSaved = options.onSaved ?? (() => {});
        this._initSubmission();
        this._clearErrorsOnInput();
    }

    openForAdd() {
        this._reset();
        $('#modal-title-text').text('Nouveau fournisseur');
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this._reset();
        $('#fournisseur-id').val(data.id);
        $('#modal-title-text').text('Modifier le fournisseur');
        $('#f-raison-sociale').val(data.raison_sociale);
        $('#f-code').val(data.code);
        $('#f-contact').val(data.contact ?? '');
        $('#f-telephone').val(data.telephone ?? '');
        $('#f-email').val(data.email ?? '');
        $('#f-adresse').val(data.adresse ?? '');
        $('#f-notes').val(data.notes ?? '');
        this.$modal.modal('show');
    }

    _reset() {
        this.$form[0].reset();
        this._clearErrors();
        $('#fournisseur-id').val('');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const id = $('#fournisseur-id').val();
            const url = id
                ? route('catalogue.fournisseurs.update', id)
                : route('catalogue.fournisseurs.store');
            const method = id ? 'PUT' : 'POST';

            const $btn = $('#btn-save');
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
