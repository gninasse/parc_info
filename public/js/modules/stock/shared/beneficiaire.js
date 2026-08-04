/**
 * beneficiaire.js — sélection du bénéficiaire à 6 types (D5, UX §0.5) via
 * les modales partagées.
 *
 * Diligence 6 : les selects de filtre sont réellement alimentés et câblés en
 * cascade (direction → service ; site → bâtiment → étage), et toute
 * modification — select, statut ou recherche débouncée — rafraîchit la liste
 * côté serveur (/stock/beneficiaires).
 */
const MODALES = {
    employe: {
        modal: '#employeSelectionModal', liste: '#emp-list', recherche: '#emp-search',
        confirmer: '#emp-confirm', colonnes: 7,
        filtres: [
            { selecteur: '#emp-filter-direction', param: 'direction_id', niveau: 'directions', enfant: '#emp-filter-service' },
            { selecteur: '#emp-filter-service', param: 'service_id', niveau: 'services', parentDe: '#emp-filter-direction' },
            { selecteur: '#emp-filter-statut', param: 'statut' },
        ],
    },
    poste: {
        modal: '#posteSelectionModal', liste: '#poste-list', recherche: '#poste-search',
        confirmer: '#poste-confirm', colonnes: 8,
        filtres: [
            { selecteur: '#poste-filter-direction', param: 'direction_id', niveau: 'directions', enfant: '#poste-filter-service' },
            { selecteur: '#poste-filter-service', param: 'service_id', niveau: 'services', parentDe: '#poste-filter-direction' },
            { selecteur: '#poste-filter-statut', param: 'statut' },
        ],
    },
    local: {
        modal: '#localSelectionModal', liste: '#local-list', recherche: '#local-search',
        confirmer: '#local-confirm', colonnes: 9,
        filtres: [
            { selecteur: '#local-filter-site', param: 'site_id', niveau: 'sites', enfant: '#local-filter-batiment' },
            { selecteur: '#local-filter-batiment', param: 'batiment_id', niveau: 'batiments', parentDe: '#local-filter-site', enfant: '#local-filter-etage' },
            { selecteur: '#local-filter-etage', param: 'etage_id', niveau: 'etages', parentDe: '#local-filter-batiment' },
        ],
    },
    direction: {
        modal: '#directionSelectionModal', liste: '#dir-list', recherche: '#dir-search',
        confirmer: '#dir-confirm', colonnes: 5, filtres: [],
    },
    service: {
        modal: '#serviceSelectionModal', liste: '#srv-list', recherche: '#srv-search',
        confirmer: '#srv-confirm', colonnes: 5,
        filtres: [
            { selecteur: '#srv-filter-direction', param: 'direction_id', niveau: 'directions' },
        ],
    },
    unite: {
        modal: '#uniteSelectionModal', liste: '#unt-list', recherche: '#unt-search',
        confirmer: '#unt-confirm', colonnes: 6,
        filtres: [
            { selecteur: '#unt-filter-direction', param: 'direction_id', niveau: 'directions', enfant: '#unt-filter-service' },
            { selecteur: '#unt-filter-service', param: 'service_id', niveau: 'services', parentDe: '#unt-filter-direction' },
        ],
    },
};

const LIBELLES_VIDES = {
    '#emp-filter-direction': 'Toutes les directions', '#emp-filter-service': 'Tous les services',
    '#poste-filter-direction': 'Toutes les directions', '#poste-filter-service': 'Tous les services',
    '#local-filter-site': 'Tous les sites', '#local-filter-batiment': 'Tous les bâtiments',
    '#local-filter-etage': 'Tous les étages',
    '#srv-filter-direction': 'Toutes les directions',
    '#unt-filter-direction': 'Toutes les directions', '#unt-filter-service': 'Tous les services',
};

export class SelecteurBeneficiaire {
    /** @param {{onChoisi: function({type, id, libelle}): void}} options */
    constructor(options = {}) {
        this.onChoisi = options.onChoisi ?? (() => {});
        this.selection = null;
        this.optionsChargees = new Set();
        Object.entries(MODALES).forEach(([type, config]) => this._cabler(type, config));
    }

    ouvrir(type) {
        const config = MODALES[type];
        if (!config) return;

        this.selection = null;
        $(config.confirmer).prop('disabled', true);
        $(config.recherche).val('');
        config.filtres.forEach((filtre) => $(filtre.selecteur).val(''));

        this._chargerOptionsRacines(type, config);
        this._charger(type, config);
        $(config.modal).modal('show');
        $(config.modal).one('shown.bs.modal', () => $(config.recherche).trigger('focus'));
    }

    _cabler(type, config) {
        let minuterie = null;
        $(config.recherche).on('input', () => {
            clearTimeout(minuterie);
            minuterie = setTimeout(() => this._charger(type, config), 300);
        });

        // Chaque filtre rafraîchit la liste ; ceux qui ont un enfant le rechargent
        config.filtres.forEach((filtre) => {
            $(filtre.selecteur).on('change', () => {
                if (filtre.enfant) {
                    const enfant = config.filtres.find((f) => f.selecteur === filtre.enfant);
                    this._remplirSelect(filtre.enfant, enfant?.niveau, $(filtre.selecteur).val() || null);
                    // La cascade repart de zéro sous l'enfant
                    const petitEnfant = config.filtres.find((f) => f.parentDe === filtre.enfant);
                    if (petitEnfant) this._remplirSelect(petitEnfant.selecteur, petitEnfant.niveau, null);
                }
                this._charger(type, config);
            });
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

    /** Les selects de premier niveau (directions, sites) une seule fois par page. */
    _chargerOptionsRacines(type, config) {
        if (this.optionsChargees.has(type)) return;
        this.optionsChargees.add(type);

        config.filtres
            .filter((filtre) => filtre.niveau && !filtre.parentDe)
            .forEach((filtre) => this._remplirSelect(filtre.selecteur, filtre.niveau, null));
    }

    _remplirSelect(selecteur, niveau, parentId) {
        const $select = $(selecteur);
        if (!$select.length || !niveau) return;

        const vide = LIBELLES_VIDES[selecteur] ?? 'Tous';
        $select.empty().append(new Option(vide, ''));

        // Sans parent choisi, un niveau enfant reste vide (cascade réelle)
        if (parentId === null && ['services', 'batiments', 'etages'].includes(niveau)) {
            $select.val('');
            return;
        }

        $.getJSON(route('stock.beneficiaires.cascade'), { niveau, parent_id: parentId }, (res) => {
            (res.data ?? []).forEach((option) => $select.append(new Option(option.libelle, option.id)));
        });
    }

    _charger(type, config) {
        const params = { type, q: $(config.recherche).val() };

        config.filtres.forEach((filtre) => {
            const valeur = $(filtre.selecteur).val();
            if (valeur) params[filtre.param] = valeur;
        });

        $.getJSON(route('stock.beneficiaires.data'), params, (res) => {
            const $liste = $(config.liste).empty();
            const echapper = (t) => $('<span>').text(t ?? '—').html();

            if (!res.data.length) {
                $liste.append(`<tr><td colspan="${config.colonnes}" class="text-center text-muted py-3">Aucun résultat pour ces filtres.</td></tr>`);
                return;
            }

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
