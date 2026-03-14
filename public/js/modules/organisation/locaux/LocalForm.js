/**
 * LocalForm.js
 * Handles Modal, Form Validation, and Submission for Locaux.
 */
export class LocalForm {
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
            $('#c_etage_id').html('<option value="">Sélectionner d\'abord un bâtiment</option>').prop('disabled', true);
        });

        $('#c_batiment_id').on('change', () => {
            this.loadEtagesForModal();
        });
    }

    loadBatimentsForModal(selectedBatimentId = null) {
        const siteId = $('#c_site_id').val();
        if (!siteId) {
            $('#c_batiment_id').html('<option value="">Sélectionner d\'abord un site</option>').prop('disabled', true);
            return;
        }
        const url = window.localRoutes.batimentsBySite.replace(':siteId', siteId);
        $.get(url, (data) => {
            let options = '<option value="">Sélectionner un bâtiment</option>';
            data.forEach((bat) => {
                let selected = selectedBatimentId == bat.id ? 'selected' : '';
                options += `<option value="${bat.id}" ${selected}>${bat.libelle}</option>`;
            });
            $('#c_batiment_id').html(options).prop('disabled', false);
        });
    }

    loadEtagesForModal(selectedEtageId = null) {
        const batimentId = $('#c_batiment_id').val();
        if (!batimentId) {
            $('#c_etage_id').html('<option value="">Sélectionner d\'abord un bâtiment</option>').prop('disabled', true);
            return;
        }
        const url = window.localRoutes.etagesByBatiment.replace(':batimentId', batimentId);
        $.get(url, (data) => {
            let options = '<option value="">Sélectionner un étage</option>';
            data.forEach((etg) => {
                let selected = selectedEtageId == etg.id ? 'selected' : '';
                options += `<option value="${etg.id}" ${selected}>${etg.libelle}</option>`;
            });
            $('#c_etage_id').html(options).prop('disabled', false);
        });
    }

    openForAdd() {
        this.resetForm();
        $('#createLocalModalLabel').text('Nouveau Local');
        $('#local_id').val('');
        this.$modal.modal('show');
    }

    openForEdit(data) {
        $('#createLocalModalLabel').text('Modifier le Local');
        $('#local_id').val(data.id);
        $('#code').val(data.code);
        $('#libelle').val(data.libelle);
        $('#type_local').val(data.type_local);
        $('#superficie_m2').val(data.superficie_m2);

        // Set cascading selects
        if (data.etage && data.etage.batiment) {
            $('#c_site_id').val(data.etage.batiment.site_id);
            this.loadBatimentsForModal(data.etage.batiment_id);

            const self = this;
            setTimeout(() => {
                self.loadEtagesForModal(data.etage_id);
            }, 200);
        }

        this.$modal.modal('show');
    }

    initSubmission() {
        $('#localForm').on('submit', (e) => {
            e.preventDefault();

            if (!this.validateForm()) {
                return false;
            }

            const localId = $('#local_id').val();
            const url = localId
                ? route('organisation.locaux.update', localId)
                : route('organisation.locaux.store');
            const method = localId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save-local').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
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
                    $('#btn-save-local').prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
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

        if (!checkEmpty('#c_etage_id', 'etage_id', 'L\'étage est obligatoire')) isValid = false;
        if (!checkEmpty('#code', 'code', 'Le code est obligatoire')) isValid = false;
        if (!checkEmpty('#libelle', 'libelle', 'Le libellé est obligatoire')) isValid = false;
        if (!checkEmpty('#type_local', 'type_local', 'Le type de local est obligatoire')) isValid = false;

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
        $('#c_etage_id').html('<option value="">Sélectionner d\'abord un bâtiment</option>').prop('disabled', true);
    }
}
