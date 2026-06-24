<!-- Modal Sélection Fournisseur -->
<div class="modal fade shadow" id="modal-select-fournisseur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-1">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fs-6 fw-bold"><i class="fas fa-truck me-2 text-success"></i>Sélectionner un Fournisseur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="search-fournisseur" class="form-control bg-light border-start-0" placeholder="Rechercher par code, nom, ville...">
                </div>
                
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0 border-top" id="table-modal-fournisseurs">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 20%;">Code</th>
                                <th style="width: 50%;">Nom</th>
                                <th style="width: 30%;">Ville</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fournisseurs as $f)
                                <tr class="supplier-row cursor-pointer" 
                                    data-id="{{ $f->id }}" 
                                    data-nom="{{ $f->nom }}" 
                                    data-code="{{ $f->code }}"
                                    data-search="{{ strtolower($f->code . ' ' . $f->nom . ' ' . ($f->ville ?? '')) }}">
                                    <td><span class="badge bg-secondary font-monospace">{{ $f->code }}</span></td>
                                    <td class="fw-bold text-dark">{{ $f->nom }}</td>
                                    <td class="text-muted small">{{ $f->ville ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle me-1"></i> Aucun fournisseur actif trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sélection Article -->
<div class="modal fade shadow" id="modal-select-article" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-1">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fs-6 fw-bold"><i class="fas fa-box me-2 text-success"></i>Sélectionner un Article</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="search-article" class="form-control bg-light border-start-0" placeholder="Rechercher par code, désignation...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="filter-article-category" class="form-select form-select-sm bg-light">
                            <option value="">Toutes les catégories</option>
                            <option value="equipement">Équipement</option>
                            <option value="consommable">Consommable</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0 border-top" id="table-modal-articles">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 25%;">Code Article</th>
                                <th style="width: 45%;">Désignation</th>
                                <th style="width: 15%;">Catégorie</th>
                                <th style="width: 15%;" class="text-end">Prix Indicatif</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Lignes injectées dynamiquement via JS à l'ouverture pour cibler la bonne ligne de BC --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
