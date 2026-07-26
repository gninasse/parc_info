export class MagasinForm {
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
        $('#magasin-id').val('');
        $('#modal-title-text').text('Nouveau magasin');
        $('#f-code').prop('disabled', false);
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this.$form[0].reset();
        this._clearErrors();
        $('#magasin-id').val(data.id);
        $('#modal-title-text').text('Modifier le magasin');
        $('#f-code').val(data.code).prop('disabled', true); // Code immutable
        $('#f-nom').val(data.nom);
        $('#f-type').val(data.type);
        $('#f-actif').prop('checked', data.est_actif);
        $('#f-description').val(data.description ?? '');
        this.$modal.modal('show');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const id     = $('#magasin-id').val();
            const url    = id ? route('stock.magasins.update', id) : route('stock.magasins.store');
            const method = id ? 'PUT' : 'POST';

            const $btn = $('#btn-save');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url, method,
                data: this.$form.serialize(),
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000 });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
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
