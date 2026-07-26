export class EntreeForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form  = $(formSelector);
        this.table  = tableInstance;
        this._initSubmission();
        this._clearErrorsOnInput();
    }

    openForAdd() {
        this.$form[0].reset();
        this._clearErrors();
        this.$modal.modal('show');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const $btn = $('#btn-save-entree-submit');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');

            $.ajax({
                url: route('stock.entrees.store'),
                method: 'POST',
                data: this.$form.serialize(),
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 3000 });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible de valider l\'entrée.' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Valider l\'entrée');
                }
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
