/**
 * beneficiaire.js — sélection du bénéficiaire à 6 types (D5, UX §0.5) via
 * les modales partagées copiées de ParcInfo (S11). Les listes sont servies
 * par /stock/beneficiaires?type=&q= ; les filtres en cascade des modales
 * (direction → service…) restent inertes en v1, la recherche texte suffit —
 * choix documenté, à câbler si l'usage le réclame.
 */
const MODALES = {
    employe: { modal: '#employeSelectionModal', liste: '#emp-list', recherche: '#emp-search', confirmer: '#emp-confirm', colonnes: 7 },
    poste: { modal: '#posteSelectionModal', liste: '#poste-list', recherche: '#poste-search', confirmer: '#poste-confirm', colonnes: 8 },
    local: { modal: '#localSelectionModal', liste: '#local-list', recherche: '#local-search', confirmer: '#local-confirm', colonnes: 9 },
    direction: { modal: '#directionSelectionModal', liste: '#dir-list', recherche: '#dir-search', confirmer: '#dir-confirm', colonnes: 5 },
    service: { modal: '#serviceSelectionModal', liste: '#srv-list', recherche: '#srv-search', confirmer: '#srv-confirm', colonnes: 5 },
    unite: { modal: '#uniteSelectionModal', liste: '#unt-list', recherche: '#unt-search', confirmer: '#unt-confirm', colonnes: 6 },
};

export class SelecteurBeneficiaire {
    /** @param {{onChoisi: function({type, id, libelle}): void}} options */
    constructor(options = {}) {
        this.onChoisi = options.onChoisi ?? (() => {});
        this.selection = null;
        Object.entries(MODALES).forEach(([type, config]) => this._cabler(type, config));
    }

    ouvrir(type) {
        const config = MODALES[type];
        if (!config) return;
        this.selection = null;
        $(config.confirmer).prop('disabled', true);
        $(config.recherche).val('');
        this._charger(type, config);
        $(config.modal).modal('show');
    }

    _cabler(type, config) {
        let minuterie = null;
        $(config.recherche).on('input', () => {
            clearTimeout(minuterie);
            minuterie = setTimeout(() => this._charger(type, config), 300);
        });

        $(config.liste).on('click', 'tr', (e) => {
            const $tr = $(e.currentTarget);
            $(config.liste).find('tr').removeClass('table-primary');
            $tr.addClass('table-primary').find('input[type=radio]').prop('checked', true);
            this.selection = { type, id: Number($tr.data('id')), libelle: $tr.data('libelle') };
            $(config.confirmer).prop('disabled', false);
        });

        $(config.liste).on('dblclick', 'tr', () => this._confirmer(config));
        $(config.confirmer).on('click', () => this._confirmer(config));
    }

    _confirmer(config) {
        if (!this.selection) return;
        this.onChoisi(this.selection);
        $(config.modal).modal('hide');
    }

    _charger(type, config) {
        $.getJSON(route('stock.beneficiaires.data'), { type, q: $(config.recherche).val() }, (res) => {
            const $liste = $(config.liste).empty();
            const echapper = (t) => $('<span>').text(t ?? '—').html();

            res.data.forEach((ligne) => {
                const cellules = [
                    '<td><input type="radio" class="form-check-input" name="benef-radio"></td>',
                    `<td>${echapper(ligne.code)}</td>`,
                    `<td>${echapper(ligne.libelle)}</td>`,
                    `<td>${echapper(ligne.contexte)}</td>`,
                ];
                while (cellules.length < config.colonnes) cellules.push('<td>—</td>');

                $liste.append(
                    `<tr data-id="${ligne.id}" data-libelle="${echapper(ligne.libelle)}">${cellules.slice(0, config.colonnes).join('')}</tr>`
                );
            });
        });
    }
}
