/**
 * SeuilModal.js — modale MD-SEUIL (UX §2), deux modes :
 *  - ajuster le seuil local d'une ligne existante (PATCH /niveaux/{id}/seuil) ;
 *  - poser un seuil sur un article jamais reçu (POST /niveaux/seuil-article,
 *    crée la ligne à 0).
 */
export class SeuilModal {
    constructor(options = {}) {
        this.$modal = $('#seuilModal');
        this.$form = $('#seuil-form');
        this.onSaved = options.onSaved ?? (() => {});
        this.magasins = options.magasins ?? [];
        this.mode = 'ligne';
        this._initSelect2();
        this._initSubmission();
    }

    /** Mode ligne existante : « Ajuster le seuil local — {article} / {magasin} ». */
    openForNiveau(row) {
        this.mode = 'ligne';
        this._reset();
        $('#seuil-niveau-id').val(row.id);
        $('#seuil-modal-title').text(`Ajuster le seuil local — ${row.article_nom} / ${row.magasin}`);
        $('#seuil-bloc-article').addClass('d-none');
        $('#seuil-valeur').val(row.seuil_origine === 'local' ? row.seuil_effectif : '');
        $('#seuil-article-defaut').text(
            row.seuil_origine === 'article' || row.seuil_origine === null
                ? (row.seuil_effectif ?? 'aucun')
                : 'voir la fiche article'
        );
        this.$modal.modal('show');
    }

    /** Mode article jamais reçu : sélecteurs magasin + article. */
    openForArticle() {
        this.mode = 'article';
        this._reset();
        $('#seuil-modal-title').text('Poser un seuil sur un article');
        $('#seuil-bloc-article').removeClass('d-none');
        $('#seuil-article-defaut').text('aucun');
        this.$modal.modal('show');
    }

    _initSelect2() {
        const $magasin = $('#seuil-magasin');
        this.magasins.forEach((magasin) => $magasin.append(new Option(magasin.libelle, magasin.id)));
        $magasin.select2({ dropdownParent: this.$modal, theme: 'bootstrap-5' });

        // Articles stockables actifs via l'API Catalogue (S11)
        $('#seuil-article').select2({
            dropdownParent: this.$modal,
            theme: 'bootstrap-5',
            placeholder: 'Rechercher au catalogue…',
            minimumInputLength: 1,
            ajax: {
                url: '/catalogue/api/articles',
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (res) => ({
                    results: res.data
                        .filter((article) => article.est_stockable !== false)
                        .map((article) => ({ id: article.id, text: `${article.code} — ${article.nom}` })),
                }),
            },
        });
    }

    _reset() {
        this.$form[0].reset();
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
        $('#seuil-niveau-id').val('');
        $('#seuil-article').empty().trigger('change');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();

            const requete = this.mode === 'ligne'
                ? { url: route('stock.niveaux.seuil', $('#seuil-niveau-id').val()), method: 'PATCH' }
                : { url: route('stock.niveaux.seuil-article'), method: 'POST' };

            const $btn = $('#btn-seuil-save');
            $btn.prop('disabled', true);

            $.ajax({
                ...requete,
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
                complete: () => $btn.prop('disabled', false),
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
