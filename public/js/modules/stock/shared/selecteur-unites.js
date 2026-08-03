/**
 * Sélecteur d'unités (UX §0.5) — table à cocher, recherche débouncée, champ
 * scan S6 (un scan = coche l'unité + toast discret), double-clic = choisir.
 *
 * const selecteur = new SelecteurUnites({
 *     url: route('stock.equipements.disponibles'),   // surchargable par ouverture
 *     onChoisis: (unites) => { ... },                // [{id, code_inventaire, numero_serie, modele}]
 * });
 * selecteur.ouvrir({ dejaChoisies: [ids...], params: {magasin_id: …} });
 */
export class SelecteurUnites {
    constructor(options = {}) {
        this.url = options.url;
        this.onChoisis = options.onChoisis ?? (() => {});
        this.$modal = $('#selecteurUnitesModal');
        this.selection = new Map();
        this.unites = [];
        this.exclues = new Set();
        this.params = {};
        this._initEvenements();
        this._initScan();
    }

    ouvrir({ dejaChoisies = [], params = {}, url = null } = {}) {
        this.selection = new Map();
        this.exclues = new Set(dejaChoisies);
        this.params = params;
        if (url) this.url = url;
        $('#unites-recherche').val('');
        this._charger();
        this.$modal.modal('show');
    }

    _initEvenements() {
        let minuterie = null;
        $('#unites-recherche').on('input', () => {
            clearTimeout(minuterie);
            minuterie = setTimeout(() => this._charger(), 300);
        });

        $('#unites-liste').on('change', 'input[type=checkbox]', (e) => {
            this._basculer(Number(e.target.value), e.target.checked);
        });

        $('#unites-liste').on('dblclick', 'tr', (e) => {
            const id = Number($(e.currentTarget).data('id'));
            this._basculer(id, true);
            this._confirmer();
        });

        $('#unites-confirmer').on('click', () => this._confirmer());
    }

    _initScan() {
        // Le champ scan coche l'unité au n° de série correspondant (S6)
        this.$modal.on('shown.bs.modal', () => {
            if (this.scan) { this.scan.refocus(); return; }
            this.scan = new StockScanField('#scan-selecteur-unites', {
                onScan: async (code) => {
                    const unite = this.unites.find(
                        (u) => (u.numero_serie ?? '').toLowerCase() === code.toLowerCase()
                    );
                    if (!unite) {
                        return { ok: false, libelle: 'Introuvable parmi les unités disponibles' };
                    }
                    this._basculer(unite.id, true);
                    this._rendre();
                    return { ok: true, libelle: `${unite.code_inventaire} pointé` };
                },
            });
        });
    }

    _basculer(id, coche) {
        const unite = this.unites.find((u) => u.id === id);
        if (!unite) return;
        if (coche) this.selection.set(id, unite);
        else this.selection.delete(id);
        $('#unites-compteur').text(this.selection.size);
        $('#unites-confirmer').prop('disabled', this.selection.size === 0);
    }

    _confirmer() {
        if (!this.selection.size) return;
        this.onChoisis([...this.selection.values()]);
        this.$modal.modal('hide');
    }

    _charger() {
        $.getJSON(this.url, { ...this.params, q: $('#unites-recherche').val() }, (res) => {
            this.unites = (res.rows ?? []).filter((u) => !this.exclues.has(u.id));
            this._rendre();
        });
    }

    _rendre() {
        const $liste = $('#unites-liste').empty();
        $('#unites-vide').prop('hidden', this.unites.length > 0);

        const echapper = (t) => $('<span>').text(t ?? '—').html();

        this.unites.forEach((unite) => {
            $liste.append(`
                <tr data-id="${unite.id}">
                    <td><input type="checkbox" class="form-check-input" value="${unite.id}" ${this.selection.has(unite.id) ? 'checked' : ''}></td>
                    <td>${echapper(unite.code_inventaire)}</td>
                    <td>${echapper(unite.modele)}</td>
                    <td class="font-monospace">${echapper(unite.numero_serie)}</td>
                    <td>${echapper(unite.etat)}</td>
                    <td>${echapper(unite.statut)}</td>
                </tr>`);
        });
    }
}
