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
        this.initCascading();
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

    initCascading() {
        $('#c_site_id').on('change', (e) => {
            this.loadDirectionsForModal($(e.target).val());
        });
    }

    loadDirectionsForModal(siteId, selectedDirectionId = null) {
        const $direction = $('#c_direction_id');
        
        if (!siteId) {
            $direction.html('<option value="">Sélectionner d\'abord un site</option>').prop('disabled', true);
            return;
        }

        $direction.prop('disabled', true).html('<option value="">Chargement...</option>');
        
        const url = window.serviceRoutes.directionsBySite.replace(':siteId', siteId);
        $.get(url, (data) => {
            let options = '<option value="">Sélectionner une direction</option>';
            data.forEach((dir) => {
                const selected = selectedDirectionId == dir.id ? 'selected' : '';
                options += `<option value="${dir.id}" ${selected}>${dir.libelle}</option>`;
            });
            $direction.html(options).prop('disabled', false);
        });
    }

    openForAdd() {
        this.resetForm();
        $('#createServiceModalLabel').text('Nouveau Service');
        $('#service_id').val('');
        $('#chef_service_id').empty();
        this.initSelect2();
        this.$modal.modal('show');
    }

    openForEdit(data) {
        $('#createServiceModalLabel').text('Modifier le Service');
        $('#service_id').val(data.id);
        $('#c_site_id').val(data.site_id);
        $('#code').val(data.code);
        $('#libelle').val(data.libelle);
        $('#type_service').val(data.type_service);
        
        $('#chef_service_id').empty();
        if (data.chef_service) {
            const opt = new Option(`${data.chef_service.full_name} (${data.chef_service.matricule})`, data.chef_service.id, true, true);
            $('#chef_service_id').append(opt);
        }
        this.initSelect2();

        $('#c_site_id').val(data.site_id);
        this.loadDirectionsForModal(data.site_id, data.direction_id);
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

    initSelect2() {
        if (!$.fn.select2) return;
        
        if ($.fn.select2 && $('#chef_service_id').data('select2')) {
            $('#chef_service_id').select2('destroy');
        }

        $('#chef_service_id').select2({
            dropdownParent: this.$modal,
            placeholder: 'Rechercher un chef de service...',
            allowClear: true,
            theme: 'bootstrap-5',
            ajax: {
                url: window.serviceRoutes.chefsService,
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
        });
    }

    displayErrors(errors) {
        this.clearErrors();
        $.each(errors, (field, messages) => {
            const $field = $(`#${field}`);
            if ($field.length) {
                $field.addClass('is-invalid');
                
                // Pour Select2, insérer après le container
                if ($field.next('.select2-container').length) {
                    $field.next('.select2-container').after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
                } else {
                    $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
                }
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
