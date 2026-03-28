/**
 * PosteTravailForm.js
 * Handles Modal, Form Validation, and Submission for PosteTravails.
 */
export class PosteTravailForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.table = tableInstance;

        // Éléments UI
        this.$niveauRattachement = $('#niveau_rattachement');
        this.$dirSelect = $('#direction_id');
        this.$srvSelect = $('#service_id');
        this.$untSelect = $('#unite_id');
        this.$siteSelect = $('#site_id');
        this.$batSelect = $('#batiment_id');
        this.$etgSelect = $('#etage_id');
        this.$locSelect = $('#local_id');

        // Conteneurs
        this.$dirContainer = $('#dir-select-container');
        this.$srvContainer = $('#srv-select-container');
        this.$untContainer = $('#unt-select-container');
        this.$batContainer = $('#bat-select-container');
        this.$etgContainer = $('#etg-select-container');
        this.$locContainer = $('#loc-select-container');

        this.init();
    }

    init() {
        this.initSelect2();
        this.initEvents();
        this.initSubmission();
    }

    initSelect2() {
        if ($.fn.select2) {
            $('#dossier_employe_id').select2({
                dropdownParent: this.$modal,
                theme: 'bootstrap-5',
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

    initEvents() {
        // --- Rattachement Administratif ---
        this.$niveauRattachement.on('change', () => {
            const niveau = this.$niveauRattachement.val();
            this.hideStructureSelectors();
            
            if (niveau) {
                this.$dirContainer.removeClass('d-none');
                this.$dirSelect.prop('required', true);
            }
            
            if (niveau === 'service' || niveau === 'unite') {
                this.$srvContainer.removeClass('d-none');
                this.$srvSelect.prop('required', true);
            }
            
            if (niveau === 'unite') {
                this.$untContainer.removeClass('d-none');
                this.$untSelect.prop('required', true);
            }
        });

        this.$dirSelect.on('change', () => {
            const dirId = this.$dirSelect.val();
            const niveau = this.$niveauRattachement.val();
            
            this.$srvSelect.empty().append('<option value="">Sélectionner un service</option>');
            this.$untSelect.empty().append('<option value="">Sélectionner une unité</option>');

            if (dirId && (niveau === 'service' || niveau === 'unite')) {
                this.loadServices(dirId);
            }
        });

        this.$srvSelect.on('change', () => {
            const srvId = this.$srvSelect.val();
            const niveau = this.$niveauRattachement.val();
            
            this.$untSelect.empty().append('<option value="">Sélectionner une unité</option>');

            if (srvId && niveau === 'unite') {
                this.loadUnites(srvId);
            }
        });

        // --- Localisation Physique ---
        this.$siteSelect.on('change', () => {
            const siteId = this.$siteSelect.val();
            this.resetPhysicalSelectors();
            
            if (siteId) {
                this.$batContainer.removeClass('d-none');
                this.loadBatiments(siteId);
            }
        });

        this.$batSelect.on('change', () => {
            const batId = this.$batSelect.val();
            this.$etgSelect.empty().append('<option value="">Sélectionner...</option>');
            this.$locSelect.empty().append('<option value="">Sélectionner...</option>');
            this.$etgContainer.addClass('d-none');
            this.$locContainer.addClass('d-none');

            if (batId) {
                this.$etgContainer.removeClass('d-none');
                this.loadEtages(batId);
            }
        });

        this.$etgSelect.on('change', () => {
            const etgId = this.$etgSelect.val();
            this.$locSelect.empty().append('<option value="">Sélectionner...</option>');
            this.$locContainer.addClass('d-none');

            if (etgId) {
                this.$locContainer.removeClass('d-none');
                this.loadLocaux(etgId);
            }
        });

        // Force uppercase for labels
        $('#libelle').on('input', function() {
            // No auto-uppercase for libelle unless requested, but screenshot looks standard.
        });
    }

    // --- Data Loading Functions ---
    loadServices(dirId, selectedId = null) {
        this.$srvSelect.html('<option value="">Chargement...</option>');
        $.get(route('organisation.unites.services-by-direction', dirId), (services) => {
            this.$srvSelect.empty().append('<option value="">Sélectionner un service</option>');
            services.forEach(item => {
                const selected = selectedId == item.id ? 'selected' : '';
                this.$srvSelect.append(`<option value="${item.id}" ${selected}>${item.libelle}</option>`);
            });
            if (selectedId) this.$srvSelect.trigger('change');
        });
    }

    loadUnites(srvId, selectedId = null) {
        this.$untSelect.html('<option value="">Chargement...</option>');
        $.get(route('organisation.unites.unites-by-service', srvId), (unites) => {
            this.$untSelect.empty().append('<option value="">Sélectionner une unité</option>');
            unites.forEach(item => {
                const selected = selectedId == item.id ? 'selected' : '';
                this.$untSelect.append(`<option value="${item.id}" ${selected}>${item.libelle}</option>`);
            });
        });
    }

    loadBatiments(siteId, selectedId = null) {
        this.$batSelect.html('<option value="">Chargement...</option>');
        $.get(route('organisation.batiments.by-site', siteId), (data) => {
            this.$batSelect.empty().append('<option value="">Sélectionner...</option>');
            data.forEach(item => {
                const selected = selectedId == item.id ? 'selected' : '';
                this.$batSelect.append(`<option value="${item.id}" ${selected}>${item.libelle}</option>`);
            });
            if (selectedId) this.$batSelect.trigger('change');
        });
    }

    loadEtages(batId, selectedId = null) {
        this.$etgSelect.html('<option value="">Chargement...</option>');
        $.get(route('organisation.etages.by-batiment', batId), (data) => {
            this.$etgSelect.empty().append('<option value="">Sélectionner...</option>');
            data.forEach(item => {
                const selected = selectedId == item.id ? 'selected' : '';
                this.$etgSelect.append(`<option value="${item.id}" ${selected}>${item.libelle}</option>`);
            });
            if (selectedId) this.$etgSelect.trigger('change');
        });
    }

    loadLocaux(etgId, selectedId = null) {
        this.$locSelect.html('<option value="">Chargement...</option>');
        $.get(route('organisation.locaux.by-etage', etgId), (data) => {
            this.$locSelect.empty().append('<option value="">Sélectionner...</option>');
            data.forEach(item => {
                const selected = selectedId == item.id ? 'selected' : '';
                this.$locSelect.append(`<option value="${item.id}" ${selected}>${item.libelle}</option>`);
            });
        });
    }

    // --- UI Helpers ---
    hideStructureSelectors() {
        this.$dirContainer.addClass('d-none');
        this.$srvContainer.addClass('d-none');
        this.$untContainer.addClass('d-none');
        this.$dirSelect.val('').prop('required', false);
        this.$srvSelect.empty().append('<option value="">Sélectionner un service</option>').prop('required', false);
        this.$untSelect.empty().append('<option value="">Sélectionner une unité</option>').prop('required', false);
    }

    resetPhysicalSelectors() {
        this.$batSelect.empty().append('<option value="">Sélectionner...</option>');
        this.$etgSelect.empty().append('<option value="">Sélectionner...</option>');
        this.$locSelect.empty().append('<option value="">Sélectionner...</option>');
        this.$batContainer.addClass('d-none');
        this.$etgContainer.addClass('d-none');
        this.$locContainer.addClass('d-none');
    }

    openForAdd() {
        this.$form[0].reset();
        this.hideStructureSelectors();
        this.resetPhysicalSelectors();
        $('#poste_id').val('');
        $('#posteModalLabel').text('Nouveau Poste de Travail');
        $('#btn-save-poste span').text('Créer le Poste');
        $('#dossier_employe_id').val(null).trigger('change');
        this.$modal.modal('show');
    }

    async openForEdit(data) {
        this.$form[0].reset();
        $('#posteModalLabel').text('Modifier le Poste de Travail');
        $('#btn-save-poste span').text('Mettre à jour');

        const response = await $.get(route('organisation.postes.show', data.id));
        const poste = response.data;

        $('#poste_id').val(poste.id);
        $('#libelle').val(poste.libelle);
        $('#code').val(poste.code);
        
        // Radio buttons for status
        $(`input[name="statut"][value="${poste.statut}"]`).prop('checked', true);

        // Administrative
        this.$niveauRattachement.val(poste.niveau_rattachement).trigger('change');
        if (poste.direction_id) {
            this.$dirSelect.val(poste.direction_id).trigger('change');
            if (poste.service_id) {
                this.loadServices(poste.direction_id, poste.service_id);
                if (poste.unite_id) {
                    this.loadUnites(poste.service_id, poste.unite_id);
                }
            }
        }

        // Physical
        if (poste.local) {
            const loc = poste.local;
            const etg = loc.etage;
            const bat = etg.batiment;
            const site = bat.site;

            this.$siteSelect.val(site.id).trigger('change');
            this.loadBatiments(site.id, bat.id);
            this.loadEtages(bat.id, etg.id);
            this.loadLocaux(etg.id, loc.id);
        }

        // Occupant
        if (poste.agent) {
            const option = new Option(poste.agent.full_name + ' (' + poste.agent.matricule + ')', poste.agent.id, true, true);
            $('#dossier_employe_id').append(option).trigger('change');
        } else {
            $('#dossier_employe_id').val(null).trigger('change');
        }

        this.$modal.modal('show');
    }

    initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const posteId = $('#poste_id').val();
            const url = posteId ? route('organisation.postes.update', posteId) : route('organisation.postes.store');
            const method = posteId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $('#btn-save-poste').prop('disabled', true).find('i').addClass('fa-spinner fa-spin').removeClass('fa-arrow-right');
                },
                success: (response) => {
                    if (response.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: (xhr) => {
                    Swal.fire('Erreur', xhr.responseJSON.message || 'Une erreur est survenue', 'error');
                },
                complete: () => {
                    $('#btn-save-poste').prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-arrow-right');
                }
            });
        });
    }
}
