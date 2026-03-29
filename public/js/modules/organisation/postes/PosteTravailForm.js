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
        this.initSelect2();
        this.initChainedSelectors();
        this.initValidation();
        this.initSubmission();
    }

    initSelect2() {
        if ($.fn.select2) {
            $('#dossier_employe_id').select2({
                dropdownParent: this.$modal,
                placeholder: 'Rechercher un employé...',
                allowClear: true,
                theme: 'bootstrap-5',
                ajax: {
                    url: route('organisation.postes.search-employes'),
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term }),
                    processResults: (data) => ({ results: data }),
                    cache: true
                }
            });
        }
    }

    initChainedSelectors() {
        const self = this;

        // 3a. Conditional Visibility for Administrative Structure
        $('#niveau_rattachement').on('change', function () {
            const niveau = $(this).val();
            $('#col-direction, #col-service, #col-unite').addClass('d-none');
            $('#direction_id, #service_id, #unite_id').val('').prop('required', false);

            if (niveau === 'direction' || niveau === 'service' || niveau === 'unite') {
                $('#col-direction').removeClass('d-none');
                $('#direction_id').prop('required', true);
            }
            if (niveau === 'service' || niveau === 'unite') {
                $('#col-service').removeClass('d-none');
                $('#service_id').prop('required', true);
            }
            if (niveau === 'unite') {
                $('#col-unite').removeClass('d-none');
                $('#unite_id').prop('required', true);
            }

            // Re-adjust columns if needed (they are col-md-4)
            if (niveau === 'direction') {
                $('#col-direction').removeClass('col-md-4').addClass('col-md-12');
            } else if (niveau === 'service') {
                $('#col-direction, #col-service').removeClass('col-md-4 col-md-12').addClass('col-md-6');
            } else {
                $('#col-direction, #col-service, #col-unite').removeClass('col-md-6 col-md-12').addClass('col-md-4');
            }
        });

        // 3b. Administrative Cascading: Direction -> Service
        $('#direction_id', this.$form).on('change', function () {
            const directionId = $(this).val();
            const $serviceSelect = $('#service_id');
            const $uniteSelect = $('#unite_id');

            $serviceSelect.html('<option value="">Chargement...</option>');
            $uniteSelect.html('<option value="">Sélectionner une unité</option>');

            if (directionId) {
                $.get(route('grh.employes.services-by-direction', directionId), (data) => {
                    let options = '<option value="">Sélectionner un service</option>';
                    data.forEach(item => options += `<option value="${item.id}">${item.libelle}</option>`);
                    $serviceSelect.html(options);
                });
            } else {
                $serviceSelect.html('<option value="">Sélectionner un service</option>');
            }
        });

        // 3b. Administrative Cascading: Service -> Unité
        $('#service_id', this.$form).on('change', function () {
            const serviceId = $(this).val();
            const $uniteSelect = $('#unite_id');

            $uniteSelect.html('<option value="">Chargement...</option>');

            if (serviceId) {
                $.get(route('grh.employes.unites-by-service', serviceId), (data) => {
                    let options = '<option value="">Sélectionner une unité</option>';
                    data.forEach(item => options += `<option value="${item.id}">${item.libelle}</option>`);
                    $uniteSelect.html(options);
                });
            } else {
                $uniteSelect.html('<option value="">Sélectionner une unité</option>');
            }
        });

        // 3c. Location Cascading: Site -> Bâtiment
        $('#site_id', this.$form).on('change', function () {
            const siteId = $(this).val();
            const $batimentSelect = $('#batiment_id');
            const $etageSelect = $('#etage_id');
            const $localSelect = $('#local_id');

            $batimentSelect.html('<option value="">Chargement...</option>');
            $etageSelect.html('<option value="">Sélectionner...</option>');
            $localSelect.html('<option value="">Sélectionner...</option>');

            if (siteId) {
                $.get(route('organisation.batiments.by-site', siteId), (data) => {
                    let options = '<option value="">Sélectionner...</option>';
                    data.forEach(item => options += `<option value="${item.id}">${item.libelle}</option>`);
                    $batimentSelect.html(options);
                });
            } else {
                $batimentSelect.html('<option value="">Sélectionner...</option>');
            }
        });

        // 3c. Location Cascading: Bâtiment -> Étage
        $('#batiment_id', this.$form).on('change', function () {
            const batimentId = $(this).val();
            const $etageSelect = $('#etage_id');
            const $localSelect = $('#local_id');

            $etageSelect.html('<option value="">Chargement...</option>');
            $localSelect.html('<option value="">Sélectionner...</option>');

            if (batimentId) {
                $.get(route('organisation.etages.by-batiment', batimentId), (data) => {
                    let options = '<option value="">Sélectionner...</option>';
                    data.forEach(item => options += `<option value="${item.id}">${item.libelle}</option>`);
                    $etageSelect.html(options);
                });
            } else {
                $etageSelect.html('<option value="">Sélectionner...</option>');
            }
        });

        // 3c. Location Cascading: Étage -> Local
        $('#etage_id', this.$form).on('change', function () {
            const etageId = $(this).val();
            const $localSelect = $('#local_id');

            $localSelect.html('<option value="">Chargement...</option>');

            if (etageId) {
                $.get(route('organisation.locaux.data'), { etage_id: etageId, limit: 1000 }, (response) => {
                    let options = '<option value="">Sélectionner...</option>';
                    response.rows.forEach(item => options += `<option value="${item.id}">${item.libelle}</option>`);
                    $localSelect.html(options);
                });
            } else {
                $localSelect.html('<option value="">Sélectionner...</option>');
            }
        });
    }

    initValidation() {
        $('input, select', this.$form).on('input change', function () {
            $(this).removeClass('is-invalid');
            $(this).closest('.col-md-6, .col-md-3, .col-md-4, .col-md-12').find('.invalid-feedback').remove();
            if ($(this).attr('name') === 'statut') {
                $('#statut_container').find('.invalid-feedback').remove();
            }
        });
    }

    openForAdd() {
        this.resetForm();
        $('#posteModalLabel').text('Nouveau Poste de Travail');
        $('#poste_id').val('');
        $('#statut_actif').prop('checked', true);
        $('#btn-save-poste span').text('Créer le Poste');
        $('#niveau_rattachement').val('direction').trigger('change');
        $('#dossier_employe_id').val(null).trigger('change');
        this.$modal.modal('show');
    }

    // 3d. Form State Management: Populate all hierarchical fields in openForEdit
    async openForEdit(data) {
        this.resetForm();
        $('#posteModalLabel').text('Modifier le Poste de Travail');
        $('#btn-save-poste span').text('Mettre à jour');

        try {
            const response = await $.get(route('organisation.postes.show', data.id));
            const poste = response.data;

            $('#poste_id').val(poste.id);
            $('#niveau_rattachement').val(poste.niveau_rattachement).trigger('change');

            // Population of administrative fields
            $('#direction_id').val(poste.direction_id);

            if (poste.direction_id) {
                const services = await $.get(route('grh.employes.services-by-direction', poste.direction_id));
                let options = '<option value="">Sélectionner un service</option>';
                services.forEach(item => options += `<option value="${item.id}" ${item.id == poste.service_id ? 'selected' : ''}>${item.libelle}</option>`);
                $('#service_id').html(options);
            }

            if (poste.service_id) {
                const unites = await $.get(route('grh.employes.unites-by-service', poste.service_id));
                let options = '<option value="">Sélectionner une unité</option>';
                unites.forEach(item => options += `<option value="${item.id}" ${item.id == poste.unite_id ? 'selected' : ''}>${item.libelle}</option>`);
                $('#unite_id').html(options);
            }

            // Population of physical location fields
            if (poste.local_id || poste.etage_id || poste.batiment_id) {
                // If the model has site_id, we'd use it. Otherwise, we might need to get it from local.
                // Assuming we can get the chain from the poste object if correctly loaded
                let siteId = null;
                let batimentId = poste.batiment_id;
                let etageId = poste.etage_id;
                let localId = poste.local_id;

                if (poste.local && poste.local.etage && poste.local.etage.batiment) {
                    siteId = poste.local.etage.batiment.site_id;
                    batimentId = batimentId || poste.local.etage.batiment_id;
                    etageId = etageId || poste.local.etage_id;
                } else if (poste.etage && poste.etage.batiment) {
                    siteId = poste.etage.batiment.site_id;
                    batimentId = batimentId || poste.etage.batiment_id;
                } else if (poste.batiment) {
                    siteId = poste.batiment.site_id;
                }

                if (siteId) {
                    $('#site_id').val(siteId);
                    const batiments = await $.get(route('organisation.batiments.by-site', siteId));
                    let batOptions = '<option value="">Sélectionner...</option>';
                    batiments.forEach(item => batOptions += `<option value="${item.id}" ${item.id == batimentId ? 'selected' : ''}>${item.libelle}</option>`);
                    $('#batiment_id').html(batOptions);
                }

                if (batimentId) {
                    const etages = await $.get(route('organisation.etages.by-batiment', batimentId));
                    let etageOptions = '<option value="">Sélectionner...</option>';
                    etages.forEach(item => etageOptions += `<option value="${item.id}" ${item.id == etageId ? 'selected' : ''}>${item.libelle}</option>`);
                    $('#etage_id').html(etageOptions);
                }

                if (etageId) {
                    const locauxRes = await $.get(route('organisation.locaux.data'), { etage_id: etageId, limit: 1000 });
                    let localOptions = '<option value="">Sélectionner...</option>';
                    locauxRes.rows.forEach(item => localOptions += `<option value="${item.id}" ${item.id == localId ? 'selected' : ''}>${item.libelle}</option>`);
                    $('#local_id').html(localOptions);
                }
            }

            $('#code').val(poste.code);
            $('#libelle').val(poste.libelle);

            // Status radio buttons
            $(`input[name="statut"][value="${poste.statut}"]`).prop('checked', true);

            if (poste.agent) {
                const option = new Option(poste.agent.full_name + ' (' + poste.agent.matricule + ')', poste.agent.id, true, true);
                $('#dossier_employe_id').append(option).trigger('change');
            } else {
                $('#dossier_employe_id').val(null).trigger('change');
            }

            this.$modal.modal('show');
        } catch (error) {
            console.error(error);
            Swal.fire('Erreur', 'Impossible de charger les données du poste', 'error');
        }
    }

    initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();

            const posteId = $('#poste_id').val();
            const url = posteId
                ? route('organisation.postes.update', posteId)
                : route('organisation.postes.store');
            const method = posteId ? 'PUT' : 'POST';

            const $btn = $('#btn-save-poste');
            const originalText = $btn.find('span').text();

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $btn.prop('disabled', true);
                    $btn.find('span').text('Enregistrement...');
                    $btn.find('i').attr('class', 'fas fa-spinner fa-spin ms-2');
                },
                success: (response) => {
                    if (response.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: response.message, timer: 2000 });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        this.displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON.message || 'Une erreur est survenue' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false);
                    $btn.find('span').text(originalText);
                    $btn.find('i').attr('class', 'fas fa-arrow-right ms-2 fs-7');
                }
            });
        });
    }

    displayErrors(errors) {
        this.clearErrors();
        $.each(errors, (field, messages) => {
            let $field = $(`#${field}`);

            if (field === 'statut') {
                $('#statut_container').append(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
            } else if (field === 'dossier_employe_id') {
                $('#dossier_employe_id').closest('.input-group').after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
            } else {
                $field.addClass('is-invalid');
                if ($field.next('.select2-container').length) {
                    $field.next('.select2-container').after(`<div class="invalid-feedback">${messages[0]}</div>`);
                } else {
                    $field.after(`<div class="invalid-feedback">${messages[0]}</div>`);
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
        $('#service_id, #unite_id, #batiment_id, #etage_id, #local_id').html('<option value="">Sélectionner...</option>');
        $('#dossier_employe_id').val(null).trigger('change');
        this.clearErrors();
    }
}
