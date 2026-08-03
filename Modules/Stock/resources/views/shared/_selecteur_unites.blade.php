{{--
    Sélecteur d'unités (UX §0.5) — table à cocher + champ scan autofocus.
    Source par défaut : /stock/equipements/disponibles (unités « en stock »
    non rattachées) ; surchargée via data-url au moment de l'ouverture
    (pointage sortie/transfert : unités du magasin).
    Piloté par js/modules/stock/shared/selecteur-unites.js.
--}}
<div class="modal fade" id="selecteurUnitesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sélectionner des unités</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Champ scan S6 : un scan = coche l'unité correspondante --}}
                @include('stock::shared._scan_field', [
                    'id' => 'scan-selecteur-unites',
                    'placeholder' => 'Scannez un n° de série pour cocher l\'unité…',
                ])

                <div class="row g-2 my-2 justify-content-end">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" id="unites-recherche"
                               placeholder="N° de série, n° d'inventaire ou code…">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;"></th>
                                <th>Code équipement</th>
                                <th>Modèle</th>
                                <th>N° de série</th>
                                <th>État</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="unites-liste" style="cursor:pointer;"></tbody>
                    </table>
                </div>
                <div class="text-muted small" id="unites-vide" hidden>Aucune unité disponible pour cette recherche.</div>
            </div>
            <div class="modal-footer">
                <span class="me-auto small text-muted"><span id="unites-compteur">0</span> unité(s) sélectionnée(s)</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="unites-confirmer" disabled>Choisir</button>
            </div>
        </div>
    </div>
</div>
