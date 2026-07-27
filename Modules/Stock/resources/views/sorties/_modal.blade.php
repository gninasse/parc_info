{{-- ── MODAL NOUVELLE SORTIE (RG-F4-01→06) ─────────────────────────────── --}}
<div class="modal fade" id="sortieModal" tabindex="-1" aria-labelledby="sortieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-primary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="sortieModalLabel">
                    <i class="fas fa-arrow-circle-up me-2 text-primary"></i>Enregistrer une sortie de stock
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="sortie-form">
                @csrf
                <div class="modal-body">
                    <div class="fw-semibold border-bottom pb-2 mb-3">Article et quantité</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label" for="sortie-magasin">Magasin <span class="text-danger">*</span></label>
                            <select class="form-select" id="sortie-magasin" name="magasin_id" required>
                                <option value="">Choisir…</option>
                                @foreach($magasins->where('statut', 'actif') as $magasin)
                                    <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sortie-article">Article <span class="text-danger">*</span></label>
                            <select class="form-select" id="sortie-article" name="article_id" required>
                                <option value="">Chargement…</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="sortie-quantite">Quantité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="sortie-quantite" name="quantite" min="1" required>
                        </div>
                    </div>

                    <div class="fw-semibold border-bottom pb-2 mb-3">Affectation (obligatoire)</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="sortie-type-cible">Type de cible <span class="text-danger">*</span></label>
                            <select class="form-select" id="sortie-type-cible" name="type_cible" required>
                                <option value="EMPLOYE">Employé</option>
                                <option value="SERVICE">Service</option>
                                <option value="DIRECTION">Direction</option>
                                <option value="UNITE">Unité</option>
                                <option value="POSTE">Poste de travail</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="sortie-cible">Cible <span class="text-danger">*</span></label>
                            <select class="form-select" id="sortie-cible" name="cible_id" required>
                                <option value="">Chargement…</option>
                            </select>
                        </div>
                    </div>

                    <div class="fw-semibold border-bottom pb-2 mb-3">Références</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sortie-reference">Référence document</label>
                            <input type="text" class="form-control" id="sortie-reference" name="reference_document" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sortie-motif">Motif</label>
                            <textarea class="form-control" id="sortie-motif" name="motif" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL RÉGULARISATION NÉGATIVE (RG-F4-04) ────────────────────────── --}}
<div class="modal fade" id="regulModal" tabindex="-1" aria-labelledby="regulModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-warning bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="regulModalLabel">
                    <i class="fas fa-scale-unbalanced me-2 text-warning"></i>Régularisation négative
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="regul-form">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label" for="regul-magasin">Magasin <span class="text-danger">*</span></label>
                            <select class="form-select" id="regul-magasin" name="magasin_id" required>
                                <option value="">Choisir…</option>
                                @foreach($magasins->where('statut', 'actif') as $magasin)
                                    <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="regul-article">Article <span class="text-danger">*</span></label>
                            <select class="form-select" id="regul-article" name="article_id" required>
                                <option value="">Chargement…</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="regul-quantite">Quantité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="regul-quantite" name="quantite" min="1" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="regul-motif">Motif <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="regul-motif" name="motif" rows="2" minlength="5" required
                                  placeholder="Casse, perte, correction d'écart…"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-1">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL DÉTAIL ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="sortieDetailModal" tabindex="-1" aria-labelledby="sortieDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-info bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="sortieDetailModalLabel">
                    <i class="fas fa-file-lines me-2 text-info"></i>Sortie <span id="detail-numero" class="font-monospace"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 small" id="detail-contenu"></dl>
            </div>
        </div>
    </div>
</div>
