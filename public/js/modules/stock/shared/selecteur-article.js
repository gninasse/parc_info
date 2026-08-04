/**
 * selecteur-article.js — sélection d'article en MODALE (remplace le Select2
 * de ligne, arbitrage UX). Recherche débouncée sur /catalogue/api/articles
 * (S11), natures E incluses (lignes « modèle × N ») ; les licences, non
 * stockables, sont toujours exclues.
 *
 * const selecteur = new SelecteurArticle({ onChoisi: (article) => { ... } });
 * selecteur.ouvrir({ nature: 'equipement' });   // filtre optionnel
 */
export class SelecteurArticle {
    constructor(options = {}) {
        this.onChoisi = options.onChoisi ?? (() => {});
        this.$modal = $('#selecteurArticleModal');
        this.articles = [];
        this.selection = null;
        this._cabler();
    }

    ouvrir({ nature = '' } = {}) {
        this.selection = null;
        $('#sa-confirmer').prop('disabled', true);
        $('#sa-recherche').val('');
        $('#sa-filtre-nature').val(nature);
        this._charger();
        this.$modal.modal('show');
        this.$modal.one('shown.bs.modal', () => $('#sa-recherche').trigger('focus'));
    }

    _cabler() {
        let minuterie = null;
        $('#sa-recherche').on('input', () => {
            clearTimeout(minuterie);
            minuterie = setTimeout(() => this._charger(), 300);
        });
        $('#sa-filtre-nature').on('change', () => this._charger());

        $('#sa-liste').on('click', 'tr', (e) => {
            const $tr = $(e.currentTarget);
            $('#sa-liste tr').removeClass('table-primary');
            $tr.addClass('table-primary').find('input[type=radio]').prop('checked', true);
            this.selection = this.articles.find((a) => a.id === Number($tr.data('id')));
            $('#sa-confirmer').prop('disabled', !this.selection);
        });

        $('#sa-liste').on('dblclick', 'tr', (e) => {
            this.selection = this.articles.find((a) => a.id === Number($(e.currentTarget).data('id')));
            this._confirmer();
        });

        $('#sa-confirmer').on('click', () => this._confirmer());
    }

    _confirmer() {
        if (!this.selection) return;
        this.onChoisi(this.selection);
        this.$modal.modal('hide');
    }

    _charger() {
        const params = { q: $('#sa-recherche').val() };
        const nature = $('#sa-filtre-nature').val();
        if (nature) params.nature = nature;

        $.getJSON('/catalogue/api/articles', params, (res) => {
            this.articles = (res.data ?? []).filter((a) => a.nature !== 'licence');
            this._rendre();
        });
    }

    _rendre() {
        const $liste = $('#sa-liste').empty();
        $('#sa-vide').prop('hidden', this.articles.length > 0);
        const e = (t) => $('<span>').text(t ?? '—').html();

        this.articles.forEach((article) => {
            $liste.append(`
                <tr data-id="${article.id}">
                    <td><input type="radio" class="form-check-input" name="sa-radio"></td>
                    <td class="text-center">${window.natureBadgeFormatter(article.nature)}</td>
                    <td class="font-monospace">${e(article.code)}</td>
                    <td>${e(article.nom)}</td>
                    <td>${e(article.unite_stock)}</td>
                    <td class="text-end">${article.prix_indicatif ? Number(article.prix_indicatif).toLocaleString('fr-FR') + ' FCFA' : '—'}</td>
                </tr>`);
        });
    }
}
