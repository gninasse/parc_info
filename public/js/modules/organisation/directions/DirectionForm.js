/**
 * DirectionForm.js
 * Handles Modal, Form Validation, and Submission for Directions.
 */
export class DirectionForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.table = tableInstance;
        this.init();
    }

    init() {
        this.initValidation();
        this.initSubmission();
    }

    initValidation() {
        $('input[required], select[required]', this.$form).on('invalid', function (e) {
            e.preventDefault();
            this.setCustomValidity('');
            if (this.validity.valueMissing) {
                this.setCustomValidity('Veuillez remplir ce champ.');
            }
        });

        $('input, select', this.$form).on('input change', function () {
            this.setCustomValidity('');
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });
    }

    openForAdd() {
        this.resetForm();
        $('#createDirectionModalLabel').text('Nouvelle Direction');
        $('#direction_id').val('');
        this.$modal.modal('show');
    }

    openForEdit(data) {
        $('#createDirectionModalLabel').text('Modifier la Direction');
        $('#direction_id').val(data.id);
        $('#site_id').val(data.site_id);
        $('#code').val(data.code);
        $('#libelle').val(data.libelle);
        $('#responsable_id').val(data.responsable_id);
        $('#description').val(data.description);
        this.$modal.modal('show');
    }

    initSubmission() {
        $('#directionForm').on('submit', (e) => {
            e.preventDefault();

            if (!this.validateForm()) {
                return false;
            }

            const directionId = $('#direction_id').val();
            const url = directionId
                ? route('organisation.directions.update', directionId)
                : route('organisation.directions.store');
            const method = directionId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save-direction').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
                },
                success: (response) => {
                    if (response.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: response.message,
                            timer: 2000
                        });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        this.displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: xhr.responseJSON.message || 'Une erreur est survenue'
                        });
                    }
                },
                complete: () => {
                    $('#btn-save-direction').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
                }
            });
        });
    }

    validateForm() {
        this.clearErrors();
        let isValid = true;
        const errors = {};

        const checkEmpty = (selector, field, msg) => {
            if ($(selector).val().trim() === '') {
                errors[field] = [msg];
                return false;
            }
            return true;
        };

        if (!checkEmpty('#site_id', 'site_id', 'Le site est obligatoire')) isValid = false;
        if (!checkEmpty('#code', 'code', 'Le code est obligatoire')) isValid = false;
        if (!checkEmpty('#libelle', 'libelle', 'Le libellé est obligatoire')) isValid = false;

        if (!isValid) {
            this.displayErrors(errors);
        }

        return isValid;
    }

    displayErrors(errors) {
        this.clearErrors();
        $.each(errors, (field, messages) => {
            const $field = $(`#${field}`);
            $field.addClass('is-invalid');
            $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
        });
    }

    clearErrors() {
        $('.is-invalid', this.$form).removeClass('is-invalid');
        $('.invalid-feedback', this.$form).remove();
    }

    resetForm() {
        this.$form[0].reset();
        this.clearErrors();
    }
}
