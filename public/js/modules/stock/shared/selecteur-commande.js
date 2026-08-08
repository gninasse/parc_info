/**
 * selecteur-commande.js — M-02, sélection d'un bon de commande à livrer
 * (RACCORDEMENT §2.2). Même patron que SelecteurArticle : recherche
 * débouncée, liste radio, double-clic = choisir.
 *
 * Source : GET /achat/api/bons-commande/a-livrer (permission achat.api.view,
 * accordée aux rôles Stock par le seeder Achat). Si l'API échoue (module
 * Achat coupé, droits manquants), la modale AFFICHE l'erreur au lieu de se
 * vider en silence : le magasinier sait pourquoi il ne voit rien.
 *
 * const selecteur = new SelecteurCommande({ onChoisi: (bon) => { ... } });
 * selecteur.ouvrir();
 */
export class SelecteurCommande {
    constructor(options = {}) {
        this.onChoisi = options.onChoisi ?? (() => {});
        this.url = options.url;
        this.$modal = $('#selecteurCommandeModal');
        this.bons = [];
        this.selection = null;
        this._cabler();
    }

    ouvrir() {
        this.selection = null;
        $('#sc-confirmer').prop('disabled', true);
        $('#sc-recherche').val('');
        $('#sc-filtre-fournisseur').val('');
        this._charger();
        this.$modal.modal('show');
        this.$modal.one('shown.bs.modal', () => $('#sc-recherche').trigger('focus'));
    }

    _cabler() {
        let minuterie = null;
        $('#sc-recherche').on('input', () => {
            clearTimeout(minuterie);
            minuterie = setTimeout(() => this._charger(), 300);
        });
        $('#sc-filtre-fournisseur').on('change', () => this._charger());

        $('#sc-liste').on('click', 'tr', (e) => {
            const $tr = $(e.currentTarget);
            $('#sc-liste tr').removeClass('table-primary');
            $tr.addClass('table-primary').find('input[type=radio]').prop('checked', true);
            this.selection = this.bons.find((b) => b.id === Number($tr.data('id')));
            $('#sc-confirmer').prop('disabled', !this.selection);
        });

        $('#sc-liste').on('dblclick', 'tr', (e) => {
            this.selection = this.bons.find((b) => b.id === Number($(e.currentTarget).data('id')));
            this._confirmer();
        });

        $('#sc-confirmer').on('click', () => this._confirmer());
    }

    _confirmer() {
        if (!this.selection) return;
        this.onChoisi(this.selection);
        this.$modal.modal('hide');
    }

    _charger() {
        const params = { q: $('#sc-recherche').val() };
        const fournisseur = $('#sc-filtre-fournisseur').val();
        if (fournisseur) params.fournisseur_id = fournisseur;

        $('#sc-erreur').addClass('d-none');

        $.getJSON(this.url, params)
            .done((res) => {
                this.bons = res.data ?? [];
                this._rendre();
            })
            .fail((xhr) => {
                this.bons = [];
                this._rendre();
                $('#sc-erreur').removeClass('d-none').text(
                    xhr.status === 403
                        ? 'Votre profil n\'a pas accès à l\'API du module Achat (permission achat.api.view).'
                        : 'Le module Achat ne répond pas — réessayez, ou saisissez le bon sans liaison.'
                );
            });
    }

    _rendre() {
        const $liste = $('#sc-liste').empty();
        $('#sc-vide').prop('hidden', this.bons.length > 0);
        const e = (t) => $('<span>').text(t ?? '—').html();

        this.bons.forEach((bon) => {
            const statut = bon.statut === 'PARTIEL'
                ? '<span class="badge bg-warning text-dark">Partiel</span>'
                : '<span class="badge bg-primary">Validé</span>';

            $liste.append(`
                <tr data-id="${bon.id}">
                    <td><input type="radio" class="form-check-input" name="sc-radio" aria-label="Choisir ${e(bon.numero)}"></td>
                    <td class="font-monospace">${e(bon.numero)}</td>
                    <td>${e(bon.fournisseur?.nom)}</td>
                    <td>${e(bon.valide_le)}</td>
                    <td class="text-end">${bon.lignes_restantes} <small class="text-muted">(${Number(bon.unites_restantes).toLocaleString('fr-FR')} u)</small></td>
                    <td class="text-end">${Number(bon.montant_ttc).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} <small class="text-muted">FCFA</small></td>
                    <td>${statut}</td>
                </tr>`);
        });
    }
}
