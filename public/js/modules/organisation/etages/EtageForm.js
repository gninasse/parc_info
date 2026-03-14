/**
 * EtageForm.js
 * Handles Modal, Form Validation, and Submission for Etages.
 */
export class EtageForm {
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
            this.loadBatimentsForModal();
        });
    }

    loadBatimentsForModal(selectedBatimentId = null) {
        const siteId = $('#c_site_id').val();
        if (!siteId) {
            $('#c_batiment_id').html('<option value="">Sélectionner d\'abord un site</option>').prop('disabled', true);
            return;
        }
        const url = window.etageRoutes.batimentsBySite.replace(':siteId', siteId);
        $.get(url, (data) => {
            let options = '<option value="">Sélectionner un bâtiment</option>';
            data.forEach((bat) => {
                let selected = selectedBatimentId == bat.id ? 'selected' : '';
                options += `<option value="${bat.id}" ${selected}>${bat.libelle}</option>`;
            });
            $('#c_batiment_id').html(options).prop('disabled', false);
        });
    }

    openForAdd() {
        this.resetForm();
        $('#createEtageModalLabel').text('Nouvel Étage');
        $('#etage_id').val('');
        this.$modal.modal('show');
    }

    openForEdit(data) {
        $('#createEtageModalLabel').text('Modifier l\'Étage');
        $('#etage_id').val(data.id);
        $('#numero').val(data.numero);
        $('#libelle').val(data.libelle);

        // Set site and load batiments
        if (data.batiment && data.batiment.site_id) {
            $('#c_site_id').val(data.batiment.site_id);
            this.loadBatimentsForModal(data.batiment_id);
        }

        this.$modal.modal('show');
    }

    initSubmission() {
        $('#etageForm').on('submit', (e) => {
            e.preventDefault();

            if (!this.validateForm()) {
                return false;
            }

            const etageId = $('#etage_id').val();
            const url = etageId
                ? route('organisation.etages.update', etageId)
                : route('organisation.etages.store');
            const method = etageId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save-etage').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
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
                        if (xhr.responseJSON.errors) {
                            this.displayErrors(xhr.responseJSON.errors);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: xhr.responseJSON.message || 'Erreur de validation'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: xhr.responseJSON.message || 'Une erreur est survenue'
                        });
                    }
                },
                complete: () => {
                    $('#btn-save-etage').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
                }
            });
        });
    }

    validateForm() {
        this.clearErrors();
        let isValid = true;
        const errors = {};

        const checkEmpty = (selector, field, msg) => {
            if ($(selector).val() === null || $(selector).val().toString().trim() === '') {
                errors[field] = [msg];
                return false;
            }
            return true;
        };

        if (!checkEmpty('#c_batiment_id', 'batiment_id', 'Le bâtiment est obligatoire')) isValid = false;
        if (!checkEmpty('#numero', 'numero', 'Le numéro est obligatoire')) isValid = false;
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
        $('#c_batiment_id').html('<option value="">Sélectionner d\'abord un site</option>').prop('disabled', true);
    }
}
