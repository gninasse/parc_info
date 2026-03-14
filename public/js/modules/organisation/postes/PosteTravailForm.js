/**
 * PosteTravailForm.js
 * Handles Modal, Form Validation, and Submission for PosteTravails.
 */
export class PosteTravailForm {
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
        $('#posteModalLabel').text('Nouvelle Poste de travail');
        $('#poste_id').val('');
        $('#statut').val('actif');
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this.resetForm();
        $('#posteModalLabel').text('Modifier la Poste de travail');
        $('#poste_id').val(data.id);
        $('#direction_id').val(data.direction_id);
        $('#service_id').val(data.service_id);
        $('#unite_id').val(data.unite_id);
        $('#local_id').val(data.local_id);
        $('#code').val(data.code);
        $('#libelle').val(data.libelle);
        $('#agent_id').val(data.agent_id);
        $('#statut').val(data.statut);
        $('#description').val(data.description);
        this.$modal.modal('show');
    }

    initSubmission() {
        $('#posteForm').on('submit', (e) => {
            e.preventDefault();

            if (!this.validateForm()) {
                return false;
            }

            const posteId = $('#poste_id').val();
            const url = posteId 
                ? route('organisation.postes.update', posteId) 
                : route('organisation.postes.store');
            const method = posteId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save-poste').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
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
                    $('#btn-save-poste').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
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

        if (!checkEmpty('#direction_id', 'direction_id', 'Le site est obligatoire')) isValid = false;
        if (!checkEmpty('#service_id', 'service_id', 'Le service est obligatoire')) isValid = false;
        if (!checkEmpty('#libelle', 'libelle', 'Le libellé est obligatoire')) isValid = false;
        if (!checkEmpty('#statut', 'statut', 'Le statut est obligatoire')) isValid = false;

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
