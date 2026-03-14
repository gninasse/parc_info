/**
 * UniteForm.js
 * Handles Modal, Form Validation, and Submission for Unités de Mesure.
 */
export class UniteForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.table = tableInstance;
        this.init();
    }

    init() {
        this.initValidation();
        this.initSubmission();
        this.initCascadingSelects();
    }

    initValidation() {
        $('input[required]', this.$form).on('invalid', function (e) {
            e.preventDefault();
            this.setCustomValidity('');
            if (this.validity.valueMissing) {
                this.setCustomValidity('Veuillez remplir ce champ.');
            }
        });

        $('input[required]', this.$form).on('input change', function () {
            this.setCustomValidity('');
        });

        $('input', this.$form).on('input', function () {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });
    }

    openForAdd() {
        this.resetForm();
        $('#createUniteModalLabel').text('Nouvelle Unité');
        $('#unite_id').val('');
        this.loadMajors();
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this.resetForm();
        $('#createUniteModalLabel').text('Modifier Unité');
        $('#unite_id').val(data.id);
        $('#code').val(data.code);
        $('#libelle').val(data.libelle);
        
        // Handle cascading selects for edit
        if (data.site_id) {
            $('#c_site_id').val(data.site_id).trigger('change');
            
            // We need to wait for directions to load before setting direction
            setTimeout(() => {
                if (data.service && data.service.direction_id) {
                    $('#c_direction_id').val(data.service.direction_id).trigger('change');
                    
                    setTimeout(() => {
                        if (data.service_id) {
                            $('#c_service_id').val(data.service_id);
                        }
                    }, 500);
                }
            }, 500);
        }

        this.loadMajors(data.major_id);
        this.$modal.modal('show');
    }

    initSubmission() {
        this.$form.submit((e) => {
            e.preventDefault();

            if (!this.validateForm()) {
                return false;
            }

            const uniteId = $('#unite_id').val();
            const url = uniteId ? route('organisation.unites.update', uniteId) : route('organisation.unites.store');
            const method = uniteId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
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
                    $('#btn-save-unite').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
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

        if (!checkEmpty('#code', 'code', 'Le code est obligatoire')) isValid = false;
        if (!checkEmpty('#libelle', 'libelle', 'Le libellé est obligatoire')) isValid = false;
        if (!checkEmpty('#c_service_id', 'service_id', 'Le service est obligatoire')) isValid = false;

        if (!isValid) {
            this.displayErrors(errors);
        }

        return isValid;
    }

    initCascadingSelects() {
        const $site = $('#c_site_id');
        const $direction = $('#c_direction_id');
        const $service = $('#c_service_id');

        $site.on('change', function() {
            const siteId = $(this).val();
            $direction.prop('disabled', true).html('<option value="">Chargement...</option>');
            $service.prop('disabled', true).html('<option value="">Sélectionner d\'abord une direction</option>');

            if (siteId) {
                $.get(window.uniteRoutes.directionsBySite.replace(':siteId', siteId), function(data) {
                    let html = '<option value="">Sélectionner une direction</option>';
                    data.forEach(item => {
                        html += `<option value="${item.id}">${item.libelle}</option>`;
                    });
                    $direction.html(html).prop('disabled', false);
                });
            } else {
                $direction.html('<option value="">Sélectionner d\'abord un site</option>');
            }
        });

        $direction.on('change', function() {
            const directionId = $(this).val();
            $service.prop('disabled', true).html('<option value="">Chargement...</option>');

            if (directionId) {
                $.get(window.uniteRoutes.servicesByDirection.replace(':directionId', directionId), function(data) {
                    let html = '<option value="">Sélectionner un service</option>';
                    data.forEach(item => {
                        html += `<option value="${item.id}">${item.libelle}</option>`;
                    });
                    $service.html(html).prop('disabled', false);
                });
            } else {
                $service.html('<option value="">Sélectionner d\'abord une direction</option>');
            }
        });
    }

    loadMajors(selectedId = null) {
        const $major = $('#major_id');
        $major.html('<option value="">Chargement...</option>');

        $.get(window.uniteRoutes.majors, function(data) {
            let html = '<option value="">Sélectionner un major</option>';
            data.forEach(user => {
                const selected = selectedId == user.id ? 'selected' : '';
                html += `<option value="${user.id}" ${selected}>${user.name}</option>`;
            });
            $major.html(html);
        });
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
