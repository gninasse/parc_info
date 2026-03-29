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
        this.initChainedSelectors();
        this.initLevelRattachement();
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

    initLevelRattachement() {
        $('#niveau_rattachement').on('change', () => {
            const level = $('#niveau_rattachement').val();
            this.toggleOrganizationalFields(level);
        });
    }

    toggleOrganizationalFields(level) {
        // Reset and hide
        $('#div_service, #div_unite').hide();
        $('#service_id, #unite_id').val('').prop('required', false);

        if (level === 'service' || level === 'unite') {
            $('#div_service').show();
            $('#service_id').prop('required', true);
        }

        if (level === 'unite') {
            $('#div_unite').show();
            $('#unite_id').prop('required', true);
        }
    }

    initChainedSelectors() {
        // Org Hierarchy
        $('#direction_id').on('change', (e) => {
            const directionId = $(e.target).val();
            this.loadServices(directionId);
        });

        $('#service_id').on('change', (e) => {
            const serviceId = $(e.target).val();
            this.loadUnites(serviceId);
        });

        // Location Hierarchy
        $('#site_id').on('change', (e) => {
            const siteId = $(e.target).val();
            this.loadBatiments(siteId);
        });

        $('#batiment_id').on('change', (e) => {
            const batimentId = $(e.target).val();
            this.loadEtages(batimentId);
        });

        $('#etage_id').on('change', (e) => {
            const etageId = $(e.target).val();
            this.loadLocaux(etageId);
        });
    }

    loadServices(directionId, callback) {
        const $el = $('#service_id');
        $el.empty().append('<option value="">Chargement...</option>');
        $('#unite_id').empty().append('<option value="">Sélectionner une unité</option>');

        if (!directionId) {
            $el.empty().append('<option value="">Sélectionner un service</option>');
            return;
        }

        $.get(route('organisation.unites.services-by-direction', directionId), (data) => {
            $el.empty().append('<option value="">Sélectionner un service</option>');
            data.forEach(item => {
                $el.append(`<option value="${item.id}">${item.libelle}</option>`);
            });
            if (callback) callback();
        });
    }

    loadUnites(serviceId, callback) {
        const $el = $('#unite_id');
        $el.empty().append('<option value="">Chargement...</option>');

        if (!serviceId) {
            $el.empty().append('<option value="">Sélectionner une unité</option>');
            return;
        }

        $.get(route('organisation.unites.by-service', serviceId), (data) => {
            $el.empty().append('<option value="">Sélectionner une unité</option>');
            data.forEach(item => {
                $el.append(`<option value="${item.id}">${item.libelle}</option>`);
            });
            if (callback) callback();
        });
    }

    loadBatiments(siteId, callback) {
        const $el = $('#batiment_id');
        $el.empty().append('<option value="">Chargement...</option>');
        $('#etage_id, #local_id').empty().append('<option value="">Sélectionner...</option>');

        if (!siteId) {
            $el.empty().append('<option value="">Sélectionner un bâtiment</option>');
            return;
        }

        $.get(route('organisation.batiments.by-site', siteId), (data) => {
            $el.empty().append('<option value="">Sélectionner un bâtiment</option>');
            data.forEach(item => {
                $el.append(`<option value="${item.id}">${item.libelle}</option>`);
            });
            if (callback) callback();
        });
    }

    loadEtages(batimentId, callback) {
        const $el = $('#etage_id');
        $el.empty().append('<option value="">Chargement...</option>');
        $('#local_id').empty().append('<option value="">Sélectionner...</option>');

        if (!batimentId) {
            $el.empty().append('<option value="">Sélectionner un étage</option>');
            return;
        }

        $.get(route('organisation.etages.by-batiment', batimentId), (data) => {
            $el.empty().append('<option value="">Sélectionner un étage</option>');
            data.forEach(item => {
                $el.append(`<option value="${item.id}">${item.libelle}</option>`);
            });
            if (callback) callback();
        });
    }

    loadLocaux(etageId, callback) {
        const $el = $('#local_id');
        $el.empty().append('<option value="">Chargement...</option>');

        if (!etageId) {
            $el.empty().append('<option value="">Sélectionner un local</option>');
            return;
        }

        $.get(route('organisation.locaux.by-etage', etageId), (data) => {
            $el.empty().append('<option value="">Sélectionner un local</option>');
            data.forEach(item => {
                $el.append(`<option value="${item.id}">${item.libelle}</option>`);
            });
            if (callback) callback();
        });
    }

    openForAdd() {
        this.resetForm();
        $('#posteModalLabel').text('Nouvelle Poste de travail');
        $('#poste_id').val('');
        $('#statut').val('actif');
        $('#niveau_rattachement').val('direction').trigger('change');
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this.resetForm();
        $('#posteModalLabel').text('Modifier la Poste de travail');
        $('#poste_id').val(data.id);

        // Determine level
        let level = 'direction';
        if (data.unite_id) level = 'unite';
        else if (data.service_id) level = 'service';

        $('#niveau_rattachement').val(level).trigger('change');
        $('#direction_id').val(data.direction_id);

        const chain = [];

        if (data.service_id) {
            chain.push(() => this.loadServices(data.direction_id, () => {
                $('#service_id').val(data.service_id);
                if (data.unite_id) {
                    this.loadUnites(data.service_id, () => {
                        $('#unite_id').val(data.unite_id);
                    });
                }
            }));
        }

        // Location chain
        if (data.local && data.local.etage && data.local.etage.batiment) {
            const siteId = data.local.etage.batiment.site_id;
            const batimentId = data.local.etage.batiment_id;
            const etageId = data.local.etage_id;
            const localId = data.local_id;

            $('#site_id').val(siteId);
            chain.push(() => this.loadBatiments(siteId, () => {
                $('#batiment_id').val(batimentId);
                this.loadEtages(batimentId, () => {
                    $('#etage_id').val(etageId);
                    this.loadLocaux(etageId, () => {
                        $('#local_id').val(localId);
                    });
                });
            }));
        }

        chain.forEach(fn => fn());

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
            if ($(selector).is(':visible') && $(selector).val().trim() === '') {
                errors[field] = [msg];
                return false;
            }
            return true;
        };

        if (!checkEmpty('#niveau_rattachement', 'niveau_rattachement', 'Le niveau est obligatoire')) isValid = false;
        if (!checkEmpty('#direction_id', 'direction_id', 'La direction est obligatoire')) isValid = false;
        if (!checkEmpty('#service_id', 'service_id', 'Le service est obligatoire')) isValid = false;
        if (!checkEmpty('#unite_id', 'unite_id', 'L\'unité est obligatoire')) isValid = false;
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
            if ($field.next('.invalid-feedback').length === 0) {
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
        $('#div_service, #div_unite').hide();
        $('#service_id, #unite_id, #batiment_id, #etage_id, #local_id').empty().append('<option value="">Sélectionner...</option>');
    }
}
