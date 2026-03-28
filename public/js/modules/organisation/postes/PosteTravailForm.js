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
        // Niveau de rattachement
        $('#niveau_rattachement').on('change', function () {
            const niveau = $(this).val();
            $('#col-service, #col-unite').addClass('d-none');
            $('#service_id, #unite_id').val('').prop('required', false);

            if (niveau === 'service' || niveau === 'unite') {
                $('#col-service').removeClass('d-none');
                $('#service_id').prop('required', true);
            }
            if (niveau === 'unite') {
                $('#col-unite').removeClass('d-none');
                $('#unite_id').prop('required', true);
            }
        });

        // Direction -> Service
        $('#direction_id', this.$form).on('change', function () {
            const directionId = $(this).val();
            const $serviceSelect = $('#service_id');
            const $uniteSelect = $('#unite_id');

            $serviceSelect.html('<option value="">Chargement...</option>');
            $uniteSelect.html('<option value="">Sélectionner...</option>');

            if (directionId) {
                $.get(route('grh.employes.services-by-direction', directionId), (data) => {
                    let options = '<option value="">Sélectionner...</option>';
                    data.forEach(item => options += `<option value="${item.id}">${item.libelle}</option>`);
                    $serviceSelect.html(options);
                });
            } else {
                $serviceSelect.html('<option value="">Sélectionner...</option>');
            }
        });

        // Service -> Unité
        $('#service_id', this.$form).on('change', function () {
            const serviceId = $(this).val();
            const $uniteSelect = $('#unite_id');

            $uniteSelect.html('<option value="">Chargement...</option>');

            if (serviceId) {
                $.get(route('grh.employes.unites-by-service', serviceId), (data) => {
                    let options = '<option value="">Sélectionner...</option>';
                    data.forEach(item => options += `<option value="${item.id}">${item.libelle}</option>`);
                    $uniteSelect.html(options);
                });
            } else {
                $uniteSelect.html('<option value="">Sélectionner...</option>');
            }
        });

        // Site -> Bâtiment
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

        // Bâtiment -> Étage
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

        // Étage -> Local
        $('#etage_id', this.$form).on('change', function () {
            const etageId = $(this).val();
            const $localSelect = $('#local_id');

            $localSelect.html('<option value="">Chargement...</option>');

            if (etageId) {
                // There is no getByEtage but getData can filter by etage_id
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
            $(this).next('.invalid-feedback').remove();
        });
    }

    openForAdd() {
        this.resetForm();
        $('#posteModalLabel').text('Nouveau Poste de travail');
        $('#poste_id').val('');
        $('#statut').val('actif');
        $('#niveau_rattachement').val('direction').trigger('change');
        $('#dossier_employe_id').val(null).trigger('change');
        this.$modal.modal('show');
    }

    async openForEdit(data) {
        this.resetForm();
        $('#posteModalLabel').text('Modifier le Poste de travail');

        // Fetch full data to have all IDs (site, batiment, etage)
        try {
            const response = await $.get(route('organisation.postes.show', data.id));
            const poste = response.data;

            $('#poste_id').val(poste.id);
            $('#niveau_rattachement').val(poste.niveau_rattachement).trigger('change');
            $('#direction_id').val(poste.direction_id);

            if (poste.direction_id) {
                const services = await $.get(route('grh.employes.services-by-direction', poste.direction_id));
                let options = '<option value="">Sélectionner...</option>';
                services.forEach(item => options += `<option value="${item.id}" ${item.id == poste.service_id ? 'selected' : ''}>${item.libelle}</option>`);
                $('#service_id').html(options);
            }

            if (poste.service_id) {
                const unites = await $.get(route('grh.employes.unites-by-service', poste.service_id));
                let options = '<option value="">Sélectionner...</option>';
                unites.forEach(item => options += `<option value="${item.id}" ${item.id == poste.unite_id ? 'selected' : ''}>${item.libelle}</option>`);
                $('#unite_id').html(options);
            }

            // Physical location
            if (poste.local) {
                const local = poste.local;
                const etage = local.etage;
                const batiment = etage.batiment;
                const site = batiment.site;

                $('#site_id').val(site.id);

                const batiments = await $.get(route('organisation.batiments.by-site', site.id));
                let batOptions = '<option value="">Sélectionner...</option>';
                batiments.forEach(item => batOptions += `<option value="${item.id}" ${item.id == batiment.id ? 'selected' : ''}>${item.libelle}</option>`);
                $('#batiment_id').html(batOptions);

                const etages = await $.get(route('organisation.etages.by-batiment', batiment.id));
                let etageOptions = '<option value="">Sélectionner...</option>';
                etages.forEach(item => etageOptions += `<option value="${item.id}" ${item.id == etage.id ? 'selected' : ''}>${item.libelle}</option>`);
                $('#etage_id').html(etageOptions);

                const locauxRes = await $.get(route('organisation.locaux.data'), { etage_id: etage.id, limit: 1000 });
                let localOptions = '<option value="">Sélectionner...</option>';
                locauxRes.rows.forEach(item => localOptions += `<option value="${item.id}" ${item.id == local.id ? 'selected' : ''}>${item.libelle}</option>`);
                $('#local_id').html(localOptions);
            }

            $('#code').val(poste.code);
            $('#libelle').val(poste.libelle);
            $('#statut').val(poste.statut);
            $('#description').val(poste.description);

            if (poste.agent) {
                const option = new Option(poste.agent.full_name + ' (' + poste.agent.matricule + ')', poste.agent.id, true, true);
                $('#dossier_employe_id').append(option).trigger('change');
            } else {
                $('#dossier_employe_id').val(null).trigger('change');
            }

            this.$modal.modal('show');
        } catch (error) {
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

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save-poste').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
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
                    $('#btn-save-poste').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Enregistrer');
                }
            });
        });
    }

    displayErrors(errors) {
        this.clearErrors();
        $.each(errors, (field, messages) => {
            const $field = $(`#${field}`);
            $field.addClass('is-invalid');
            $field.after(`<div class="invalid-feedback">${messages[0]}</div>`);
        });
    }

    clearErrors() {
        $('.is-invalid', this.$form).removeClass('is-invalid');
        $('.invalid-feedback', this.$form).remove();
    }

    resetForm() {
        this.$form[0].reset();
        $('#service_id, #unite_id, #batiment_id, #etage_id, #local_id').html('<option value="">Sélectionner...</option>');
        this.clearErrors();
    }
}
