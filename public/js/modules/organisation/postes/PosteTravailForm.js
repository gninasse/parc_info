/**
 * PosteTravailForm.js
 * Gestion de la modale création / édition des postes de travail.
 */
export class PosteTravailForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form  = $(formSelector);
        this.table  = tableInstance;
        this._init();
    }

    /* ─────────────────────────────────────────────────────────
     * INIT
     * ───────────────────────────────────────────────────────── */
    _init() {
        this._initSelect2();
        this._initNiveauRattachement();
        this._initAdminCascade();
        this._initLocationCascade();
        this._initSubmission();
    }

    /* ─────────────────────────────────────────────────────────
     * SELECT2 — Employés
     * ───────────────────────────────────────────────────────── */
    _initSelect2() {
        if (!$.fn.select2) return;
        $('#dossier_employe_id').select2({
            dropdownParent : this.$modal,
            placeholder    : 'Rechercher un employé...',
            allowClear     : true,
            theme          : 'bootstrap-5',
            ajax: {
                url        : route('organisation.postes.search-employes'),
                dataType   : 'json',
                delay      : 250,
                data       : (p) => ({ q: p.term }),
                processResults: (d) => ({ results: d }),
                cache      : true,
            },
        });
    }

    /* ─────────────────────────────────────────────────────────
     * NIVEAU DE RATTACHEMENT → affichage conditionnel
     * ───────────────────────────────────────────────────────── */
    _initNiveauRattachement() {
        $('#niveau_rattachement').on('change', (e) => this._applyNiveau(e.target.value));
    }

    _applyNiveau(val) {
        const showDir = !!val;
        const showSrv = val === 'service' || val === 'unite';
        const showUnt = val === 'unite';

        $('#field-direction').toggleClass('d-none', !showDir);
        $('#field-service').toggleClass('d-none', !showSrv);
        $('#field-unite').toggleClass('d-none', !showUnt);

        $('#direction_id').prop('required', showDir);
        $('#service_id').prop('required', showSrv);
        $('#unite_id').prop('required', showUnt);

        if (!showSrv) this._resetSelect('#service_id', '— Sélectionner d\'abord une direction —');
        if (!showUnt) this._resetSelect('#unite_id',   '— Sélectionner d\'abord un service —');
    }

    /* ─────────────────────────────────────────────────────────
     * CASCADE ADMINISTRATIVE  Direction → Service → Unité
     * ───────────────────────────────────────────────────────── */
    _initAdminCascade() {
        $('#direction_id').on('change', (e) => {
            this._resetSelect('#service_id', '— Sélectionner d\'abord une direction —');
            this._resetSelect('#unite_id',   '— Sélectionner d\'abord un service —');
            const id = e.target.value;
            if (!id) return;
            const niveau = $('#niveau_rattachement').val();
            if (niveau === 'service' || niveau === 'unite') {
                this._loadOptions(
                    route('organisation.postes.services-by-direction', id),
                    '#service_id', '#spinner-service',
                    'Sélectionner un service...'
                );
            }
        });

        $('#service_id').on('change', (e) => {
            this._resetSelect('#unite_id', '— Sélectionner d\'abord un service —');
            const id = e.target.value;
            if (!id || $('#niveau_rattachement').val() !== 'unite') return;
            this._loadOptions(
                route('organisation.postes.unites-by-service', id),
                '#unite_id', '#spinner-unite',
                'Sélectionner une unité...'
            );
        });
    }

    /* ─────────────────────────────────────────────────────────
     * CASCADE EMPLACEMENT  Site → Bâtiment → Étage → Local
     * ───────────────────────────────────────────────────────── */
    _initLocationCascade() {
        $('#site_id').on('change', (e) => {
            this._resetSelect('#batiment_id', '— Sélectionner d\'abord un site —');
            this._resetSelect('#etage_id',    '— Sélectionner d\'abord un bâtiment —');
            this._resetSelect('#local_id',    '— Sélectionner d\'abord un étage —');
            const id = e.target.value;
            if (!id) return;
            this._loadOptions(
                route('organisation.batiments.by-site', id),
                '#batiment_id', '#spinner-batiment',
                'Sélectionner un bâtiment...'
            );
        });

        $('#batiment_id').on('change', (e) => {
            this._resetSelect('#etage_id', '— Sélectionner d\'abord un bâtiment —');
            this._resetSelect('#local_id', '— Sélectionner d\'abord un étage —');
            const id = e.target.value;
            if (!id) return;
            this._loadOptions(
                route('organisation.etages.by-batiment', id),
                '#etage_id', '#spinner-etage',
                'Sélectionner un étage...'
            );
        });

        $('#etage_id').on('change', (e) => {
            this._resetSelect('#local_id', '— Sélectionner d\'abord un étage —');
            const id = e.target.value;
            if (!id) return;
            this._loadOptions(
                route('organisation.locaux.by-etage', id),
                '#local_id', '#spinner-local',
                'Sélectionner un local...'
            );
        });
    }

    /* ─────────────────────────────────────────────────────────
     * SOUMISSION DU FORMULAIRE
     * ───────────────────────────────────────────────────────── */
    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const posteId = $('#poste_id').val();
            const url     = posteId ? route('organisation.postes.update', posteId) : route('organisation.postes.store');
            const method  = posteId ? 'PUT' : 'POST';
            const $btn    = $('#btn-save-poste');
            const $label  = $('#btn-save-label');
            const origLabel = $label.text();

            $.ajax({
                url, method,
                data: this.$form.serialize(),
                beforeSend: () => {
                    $btn.prop('disabled', true);
                    $label.text('Enregistrement...');
                },
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message || 'Une erreur est survenue' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false);
                    $label.text(origLabel);
                },
            });
        });
    }

    /* ─────────────────────────────────────────────────────────
     * OUVERTURE — Ajout
     * ───────────────────────────────────────────────────────── */
    openForAdd() {
        this._resetForm();
        $('#posteModalLabel').text('Nouveau Poste de Travail');
        $('#btn-save-label').text('Créer le Poste');
        $('#statut_actif').prop('checked', true);
        this.$modal.modal('show');
    }

    /* ─────────────────────────────────────────────────────────
     * OUVERTURE — Édition
     * ───────────────────────────────────────────────────────── */
    async openForEdit(data) {
        this._resetForm();
        $('#posteModalLabel').text('Modifier le Poste de Travail');
        $('#btn-save-label').text('Enregistrer');

        try {
            const res   = await $.get(route('organisation.postes.show', data.id));
            const poste = res.data;

            $('#poste_id').val(poste.id);
            $('#code').val(poste.code);
            $('#libelle').val(poste.libelle);
            $('#description').val(poste.description ?? '');
            $(`input[name="statut"][value="${poste.statut}"]`).prop('checked', true);

            // ── Structure administrative ──────────────────────
            $('#niveau_rattachement').val(poste.niveau_rattachement ?? '');
            this._applyNiveau(poste.niveau_rattachement ?? '');

            if (poste.direction_id) {
                $('#direction_id').val(poste.direction_id);

                if (poste.service_id) {
                    await this._loadOptions(
                        route('organisation.postes.services-by-direction', poste.direction_id),
                        '#service_id', '#spinner-service', 'Sélectionner un service...'
                    );
                    $('#service_id').val(poste.service_id);

                    if (poste.unite_id) {
                        await this._loadOptions(
                            route('organisation.postes.unites-by-service', poste.service_id),
                            '#unite_id', '#spinner-unite', 'Sélectionner une unité...'
                        );
                        $('#unite_id').val(poste.unite_id);
                    }
                }
            }

            // ── Emplacement physique ──────────────────────────
            const siteId    = poste.local?.etage?.batiment?.site_id ?? null;
            const batId     = poste.batiment_id;
            const etgId     = poste.etage_id;
            const localId   = poste.local_id;

            if (siteId) {
                $('#site_id').val(siteId);
                await this._loadOptions(route('organisation.batiments.by-site', siteId),    '#batiment_id', '#spinner-batiment', 'Sélectionner un bâtiment...');
                $('#batiment_id').val(batId);
            }
            if (batId) {
                await this._loadOptions(route('organisation.etages.by-batiment', batId),    '#etage_id',    '#spinner-etage',    'Sélectionner un étage...');
                $('#etage_id').val(etgId);
            }
            if (etgId) {
                await this._loadOptions(route('organisation.locaux.by-etage', etgId),       '#local_id',    '#spinner-local',    'Sélectionner un local...');
                $('#local_id').val(localId);
            }

            // ── Employé ───────────────────────────────────────
            if (poste.agent) {
                const opt = new Option(`${poste.agent.full_name} (${poste.agent.matricule})`, poste.agent.id, true, true);
                $('#dossier_employe_id').append(opt).trigger('change');
            }

            this.$modal.modal('show');
        } catch (err) {
            console.error(err);
            Swal.fire('Erreur', 'Impossible de charger les données du poste', 'error');
        }
    }

    /* ─────────────────────────────────────────────────────────
     * HELPERS
     * ───────────────────────────────────────────────────────── */

    /**
     * Charge des options dans un <select> via AJAX.
     * Retourne une Promise résolue quand le select est peuplé.
     */
    _loadOptions(url, selSelector, spinnerId, placeholder) {
        const $sel     = $(selSelector);
        const $spinner = $(spinnerId);

        $spinner.removeClass('d-none');
        $sel.prop('disabled', true).html(`<option value="">Chargement...</option>`);

        return $.get(url)
            .then((data) => {
                let html = `<option value="">${placeholder}</option>`;
                data.forEach(item => {
                    html += `<option value="${item.id}">${item.libelle ?? item.nom}</option>`;
                });
                $sel.html(html).prop('disabled', false);
            })
            .catch(() => {
                $sel.html(`<option value="">— Erreur de chargement —</option>`);
            })
            .always(() => {
                $spinner.addClass('d-none');
            });
    }

    _resetSelect(selSelector, placeholder) {
        $(selSelector).html(`<option value="">${placeholder}</option>`).prop('disabled', true);
    }

    _resetForm() {
        this.$form[0].reset();
        this._applyNiveau('');
        this._resetSelect('#service_id',  '— Sélectionner d\'abord une direction —');
        this._resetSelect('#unite_id',    '— Sélectionner d\'abord un service —');
        this._resetSelect('#batiment_id', '— Sélectionner d\'abord un site —');
        this._resetSelect('#etage_id',    '— Sélectionner d\'abord un bâtiment —');
        this._resetSelect('#local_id',    '— Sélectionner d\'abord un étage —');
        $('#dossier_employe_id').val(null).trigger('change');
        this._clearErrors();
    }

    _displayErrors(errors) {
        this._clearErrors();
        $.each(errors, (field, messages) => {
            const $el = $(`#${field}`, this.$form);
            if (!$el.length) return;
            $el.addClass('is-invalid');
            const $feedback = $(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
            // Pour Select2, insérer après le container généré
            if ($el.next('.select2-container').length) {
                $el.next('.select2-container').after($feedback);
            } else {
                $el.after($feedback);
            }
        });
    }

    _clearErrors() {
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
    }
}
