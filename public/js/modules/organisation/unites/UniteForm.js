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
        $('#major_id').empty();
        this.initSelect2();
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this.resetForm();
        $('#createUniteModalLabel').text('Modifier Unité');
        $('#unite_id').val(data.id);
        $('#code').val(data.code);
        $('#libelle').val(data.libelle);
        
        $('#major_id').empty();
        if (data.major) {
            const opt = new Option(`${data.major.full_name} (${data.major.matricule})`, data.major.id, true, true);
            $('#major_id').append(opt);
        }
        this.initSelect2();

        // Gestion de la cascade en modification
        if (data.service && data.service.direction) {
            const siteId = data.service.direction.site_id;
            $('#c_site_id').val(siteId);
            this.loadDirections(siteId, data.service.direction_id, (directionId) => {
                this.loadServices(directionId, data.service_id);
            });
        }

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
        $('#c_site_id').on('change', (e) => {
            this.loadDirections($(e.target).val());
        });

        $('#c_direction_id').on('change', (e) => {
            this.loadServices($(e.target).val());
        });
    }

    loadDirections(siteId, selectedId = null, callback = null) {
        const $direction = $('#c_direction_id');
        const $service = $('#c_service_id');
        
        if (!siteId) {
            $direction.html('<option value="">Sélectionner d\'abord un site</option>').prop('disabled', true);
            $service.html('<option value="">Sélectionner d\'abord une direction</option>').prop('disabled', true);
            return;
        }

        $direction.prop('disabled', true).html('<option value="">Chargement...</option>');
        
        $.get(window.uniteRoutes.directionsBySite.replace(':siteId', siteId), (data) => {
            let html = '<option value="">Sélectionner une direction</option>';
            data.forEach(item => {
                const selected = selectedId == item.id ? 'selected' : '';
                html += `<option value="${item.id}" ${selected}>${item.libelle}</option>`;
            });
            $direction.html(html).prop('disabled', false);
            
            if (callback && selectedId) {
                callback(selectedId);
            }
        });
    }

    loadServices(directionId, selectedId = null) {
        const $service = $('#c_service_id');
        
        if (!directionId) {
            $service.html('<option value="">Sélectionner d\'abord une direction</option>').prop('disabled', true);
            return;
        }

        $service.prop('disabled', true).html('<option value="">Chargement...</option>');
        
        $.get(window.uniteRoutes.servicesByDirection.replace(':directionId', directionId), (data) => {
            let html = '<option value="">Sélectionner un service</option>';
            data.forEach(item => {
                const selected = selectedId == item.id ? 'selected' : '';
                html += `<option value="${item.id}" ${selected}>${item.libelle}</option>`;
            });
            $service.html(html).prop('disabled', false);
        });
    }

    initSelect2() {
        if (!$.fn.select2) return;
        
        if ($.fn.select2 && $('#major_id').data('select2')) {
            $('#major_id').select2('destroy');
        }

        $('#major_id').select2({
            dropdownParent: this.$modal,
            placeholder: 'Rechercher un major...',
            allowClear: true,
            theme: 'bootstrap-5',
            ajax: {
                url: window.uniteRoutes.majors,
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
            $field.addClass('is-invalid');
            
            // Pour Select2, insérer après le container
            if ($field.next('.select2-container').length) {
                $field.next('.select2-container').after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
            } else {
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
    }
}
