/**
 * MagasinForm.js — modale MD-MAGASIN duale (UX §9) : site restreint aux sites
 * sans magasin et verrouillé en édition, cascade des locaux, responsable Grh.
 */
export class MagasinForm {
    constructor(modalSelector, formSelector, options = {}) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.onSaved = options.onSaved ?? (() => {});
        this._initSelect2();
        this._initCascade();
        this._initSubmission();
    }

    openForAdd() {
        this._reset();
        $('#modal-title-text').text('Nouveau magasin');
        this._verrouillerSite(false);
        this._chargerSites();
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this._reset();
        $('#magasin-id').val(data.id);
        $('#modal-title-text').text('Modifier le magasin');
        $('#m-libelle').val(data.libelle);
        $('#m-code').val(data.code);

        this._verrouillerSite(true);
        this._chargerSites(data.id, data.site_id);
        this._chargerLocaux(data.site_id, data.local_id);

        if (data.responsable) {
            const libelle = `${data.responsable.prenom ?? ''} ${data.responsable.nom ?? ''} (${data.responsable.matricule ?? ''})`.trim();
            $('#m-responsable').append(new Option(libelle, data.responsable.id, true, true)).trigger('change');
        }

        this.$modal.modal('show');
    }

    _verrouillerSite(verrouille) {
        $('#m-site').prop('disabled', verrouille);
        $('#m-site-cadenas').toggleClass('d-none', !verrouille);
    }

    _initSelect2() {
        $('#m-site, #m-local').select2({
            dropdownParent: this.$modal,
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: (el) => $(el.element ?? el).data('placeholder') ?? '',
        });

        $('#m-responsable').select2({
            dropdownParent: this.$modal,
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Rechercher un employé…',
            minimumInputLength: 0,
            ajax: {
                url: route('stock.magasins.responsables'),
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (res) => ({ results: res.data }),
            },
        });
    }

    _initCascade() {
        $('#m-site').on('change', () => {
            const siteId = $('#m-site').val();
            $('#m-local').empty().trigger('change');
            if (siteId) this._chargerLocaux(siteId, null);
        });
    }

    _chargerSites(magasinId = null, selection = null) {
        $.getJSON(route('stock.magasins.sites-disponibles'), magasinId ? { inclure: magasinId } : {}, (res) => {
            const $site = $('#m-site');
            $site.empty().append(new Option('', ''));
            res.data.forEach((site) => $site.append(new Option(site.text, site.id, false, site.id === selection)));
            $site.trigger('change.select2');
        });
    }

    _chargerLocaux(siteId, selection = null) {
        if (!siteId) return;
        $.getJSON(route('stock.magasins.locaux'), { site_id: siteId }, (res) => {
            const $local = $('#m-local');
            $local.empty().append(new Option('', ''));
            res.data.forEach((local) => {
                const texte = local.type_local === 'magasin' ? `🏬 ${local.text}` : local.text;
                $local.append(new Option(texte, local.id, false, local.id === selection));
            });
            $local.trigger('change.select2');
        });
    }

    _reset() {
        this.$form[0].reset();
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
        $('#magasin-id').val('');
        $('#m-code').val('');
        $('#m-site, #m-local, #m-responsable').empty().trigger('change');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const id = $('#magasin-id').val();
            const url = id ? route('stock.magasins.update', id) : route('stock.magasins.store');

            const $btn = $('#btn-save');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url,
                method: id ? 'PUT' : 'POST',
                data: this.$form.serialize(),
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                        this.onSaved(res);
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
                },
            });
        });
    }

    _displayErrors(errors) {
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
        $.each(errors, (field, messages) => {
            const $field = this.$form.find(`[name="${field}"]`);
            $field.addClass('is-invalid');
            const $cible = $field.hasClass('select2-hidden-accessible') ? $field.next('.select2') : $field;
            $cible.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
        });
    }
}
