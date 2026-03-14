/**
 * ServiceForm.js
 * Handles Modal, Form Validation, and Submission for Services.
 */
export class ServiceForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.table = tableInstance;
        this.init();
    }

    init() {
        this.initValidation();
        this.initSubmission();
        this.initCascading();
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

    initCascading() {
        $('#c_site_id').on('change', () => {
            this.loadDirectionsForModal();
        });
    }

    loadDirectionsForModal(selectedDirectionId = null) {
        const siteId = $('#c_site_id').val();
        if (!siteId) {
            $('#c_direction_id').html('<option value="">Sélectionner d\'abord un site</option>').prop('disabled', true);
            return;
        }
        const url = window.serviceRoutes.directionsBySite.replace(':siteId', siteId);
        $.get(url, (data) => {
            let options = '<option value="">Sélectionner une direction</option>';
            data.forEach((dir) => {
                let selected = selectedDirectionId == dir.id ? 'selected' : '';
                options += `<option value="${dir.id}" ${selected}>${dir.libelle}</option>`;
            });
            $('#c_direction_id').html(options).prop('disabled', false);
        });
    }

    openForAdd() {
        this.resetForm();
        $('#createServiceModalLabel').text('Nouveau Service');
        $('#service_id').val('');
        this.$modal.modal('show');
    }

    openForEdit(data) {
        $('#createServiceModalLabel').text('Modifier le Service');
        $('#service_id').val(data.id);
        $('#c_site_id').val(data.site_id);
        $('#code').val(data.code);
        $('#libelle').val(data.libelle);
        $('#type_service').val(data.type_service);
        $('#chef_service_id').val(data.chef_service_id);

        this.loadDirectionsForModal(data.direction_id);
        this.$modal.modal('show');
    }

    initSubmission() {
        $('#serviceForm').on('submit', (e) => {
            e.preventDefault();

            if (!this.validateForm()) {
                return false;
            }

            const serviceId = $('#service_id').val();
            const url = serviceId
                ? route('organisation.services.update', serviceId)
                : route('organisation.services.store');
            const method = serviceId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save-service').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
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
                    $('#btn-save-service').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
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

        if (!checkEmpty('#c_direction_id', 'direction_id', 'La direction est obligatoire')) isValid = false;
        if (!checkEmpty('#code', 'code', 'Le code est obligatoire')) isValid = false;
        if (!checkEmpty('#libelle', 'libelle', 'Le libellé est obligatoire')) isValid = false;
        if (!checkEmpty('#type_service', 'type_service', 'Le type de service est obligatoire')) isValid = false;

        if (!isValid) {
            this.displayErrors(errors);
        }

        return isValid;
    }

    displayErrors(errors) {
        this.clearErrors();
        $.each(errors, (field, messages) => {
            const $field = $(`#${field}`);
            if ($field.length) {
                $field.addClass('is-invalid');
                $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
            }
        });
    }

    clearErrors() {
        $('.is-invalid', this.$form).removeClass('is-invalid');
        $('.invalid-feedback', this.$form).remove();
    }

    resetForm() {
        this.$form[0].reset();
        this.clearErrors();
        $('#c_direction_id').html('<option value="">Sélectionner d\'abord un site</option>').prop('disabled', true);
    }
}
