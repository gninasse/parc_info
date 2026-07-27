{{-- ── MODAL INITIALISATION (RG-F2-01→03) ──────────────────────────────── --}}
<div class="modal fade" id="initModal" tabindex="-1" aria-labelledby="initModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-primary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="initModalLabel">
                    <i class="fas fa-box-open me-2 text-primary"></i>Initialiser un article en stock
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="init-form">
                @csrf
                <div class="modal-body">
                    <div class="fw-semibold border-bottom pb-2 mb-3">Article et magasin</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="init-magasin">Magasin <span class="text-danger">*</span></label>
                            <select class="form-select" id="init-magasin" name="magasin_id" required>
                                <option value="">Choisir…</option>
                                @foreach($magasins->where('statut', 'actif') as $magasin)
                                    <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="init-article">Article <span class="text-danger">*</span></label>
                            <select class="form-select" id="init-article" name="article_id" required disabled>
                                <option value="">Choisir d'abord un magasin…</option>
                            </select>
                        </div>
                    </div>

                    <div class="fw-semibold border-bottom pb-2 mb-3">Stock de départ (facultatif)</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="init-quantite">Quantité initiale</label>
                            <input type="number" class="form-control" id="init-quantite" name="quantite_initiale" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="init-cout">Coût unitaire (FCFA)</label>
                            <input type="number" class="form-control" id="init-cout" name="cout_unitaire" min="0" step="0.01">
                            <div class="form-text">Obligatoire si une quantité de départ est saisie (lot FIFO).</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="fas fa-save me-1"></i>Initialiser
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL DÉTAIL (stock par magasin + lots FIFO) ────────────────────── --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-info bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="detailModalLabel">
                    <i class="fas fa-boxes me-2 text-info"></i>Détail — <span id="detail-article-designation"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="fw-semibold border-bottom pb-2 mb-3">Stock par magasin</div>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Magasin</th>
                                <th class="text-end">Quantité</th>
                                <th class="text-end">Valeur FIFO</th>
                                <th class="text-end">Dernière entrée</th>
                                <th class="text-end">Dernière sortie</th>
                            </tr>
                        </thead>
                        <tbody id="detail-magasins"></tbody>
                    </table>
                </div>

                <div class="fw-semibold border-bottom pb-2 mb-3">Lots FIFO disponibles</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Magasin</th>
                                <th class="text-end">Date d'entrée</th>
                                <th class="text-end">Qté initiale</th>
                                <th class="text-end">Qté restante</th>
                                <th class="text-end">Coût unitaire</th>
                                <th class="text-end">Valeur restante</th>
                            </tr>
                        </thead>
                        <tbody id="detail-lots"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
