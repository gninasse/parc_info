{{-- Modale de sélection d'équipement (Générique) --}}
<div class="modal fade" id="equipementSelectionModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="bi bi-pc-display me-2"></i>Sélectionner un équipement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="eq-filter-type">
                            <option value="">Tous les types</option>
                            <option value="ordinateur">Poste de travail (Ordinateur)</option>
                            <option value="serveur">Serveur</option>
                            <option value="imprimante">Imprimante</option>
                            <option value="scanner">Scanner</option>
                            <option value="telephone">Téléphone</option>
                            <option value="camera">Caméra IP</option>
                            <option value="mobile">Mobile / Tablette</option>
                            <option value="reseau">Équipement Réseau</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="eq-filter-statut">
                            <option value="">Tous les statuts</option>
                            <option value="en_service" selected>En service</option>
                            <option value="en_stock">En stock</option>
                            <option value="en_reparation">En réparation</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" id="eq-search" placeholder="Rechercher par code, modèle, marque ou n° série...">
                        </div>
                    </div>
                </div>

                <div id="eq-skeleton" class="d-none">
                    @for($i = 0; $i < 5; $i++)
                    <div class="placeholder-glow mb-2">
                        <div class="placeholder col-12 rounded" style="height:50px"></div>
                    </div>
                    @endfor
                </div>

                <div class="table-responsive" style="min-height: 300px;">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40"></th>
                                <th>Code Inventaire</th>
                                <th>Modèle / Marque</th>
                                <th>Emplacement</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="eq-list" style="cursor: pointer;">
                            {{-- Injecté par JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary px-4" id="eq-confirm" disabled>
                    <i class="bi bi-check-circle me-2"></i>Confirmer la sélection
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .eq-row-selected { background-color: rgba(13, 110, 253, 0.1) !important; }
    #eq-list tr:hover { background-color: rgba(0, 0, 0, 0.02); }
</style>
