{{--
    M-02 — « Sélectionner une commande » (RACCORDEMENT §2.2).

    Même gabarit que « Sélectionner un article » : filtres, liste radio,
    double-clic = choisir, pied informatif. Alimentée par l'API Achat
    (GET /achat/api/bons-commande/a-livrer) — seuls les BC VALIDÉ/PARTIEL
    apparaissent. Pilotée par js/modules/stock/shared/selecteur-commande.js.
--}}
<div class="modal fade" id="selecteurCommandeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sélectionner une commande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-sm" id="sc-recherche"
                               placeholder="N° de BC ou article commandé…" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" id="sc-filtre-fournisseur" aria-label="Filtrer par fournisseur">
                            <option value="">Tous les fournisseurs</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}">{{ $fournisseur->raison_sociale }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;"></th>
                                <th>N° BC</th>
                                <th>Fournisseur</th>
                                <th>Validé le</th>
                                <th class="text-end">Lignes restantes</th>
                                <th class="text-end">Montant TTC</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="sc-liste" style="cursor:pointer;"></tbody>
                    </table>
                </div>
                <div class="text-muted small" id="sc-vide" hidden>Aucune commande à livrer pour cette recherche.</div>
                <div class="alert alert-warning small d-none mb-0" id="sc-erreur" role="alert"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="small text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Le bon d'entrée sera pré-rempli du reste à livrer ; les quantités reçues restent modifiables à la baisse.
                </span>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="sc-confirmer" disabled>Choisir</button>
                </div>
            </div>
        </div>
    </div>
</div>
