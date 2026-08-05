{{--
    Modale de sélection du fournisseur (pattern du projet : une sélection se
    fait dans une MODALE, jamais dans un simple select — même gabarit que la
    modale article, en mode radio).

    Alimentée par l'API Catalogue (GET /catalogue/api/fournisseurs) : recherche
    bornée, sélection unique, pied informatif.
--}}
<div class="modal fade" id="modal-fournisseur" tabindex="-1" aria-labelledby="titre-modal-fournisseur" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titre-modal-fournisseur">
                    <i class="bi bi-building me-2"></i>Sélectionner un fournisseur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="mf-recherche"
                           placeholder="Raison sociale ou code…" autocomplete="off"
                           aria-label="Rechercher un fournisseur">
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="mf-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:3rem;"></th>
                                <th style="width:90px;">Code</th>
                                <th>Raison sociale</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody id="mf-corps"></tbody>
                    </table>
                </div>

                <div id="mf-vide" class="text-center text-muted py-4 d-none">
                    <i class="bi bi-search fs-3"></i>
                    <p class="mb-0 mt-2">Aucun fournisseur ne correspond à cette recherche.</p>
                </div>

                <div id="mf-chargement" class="text-center text-muted py-4 d-none">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <span class="ms-2">Chargement…</span>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <div class="small text-muted">
                    Référentiel du module Catalogue —
                    <a href="{{ route('catalogue.fournisseurs.index') }}" target="_blank" rel="noopener">
                        gérer les fournisseurs →
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary btn-sm" id="mf-choisir" disabled>
                        Choisir ce fournisseur
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
