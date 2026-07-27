{{-- ── MODAL NOUVELLE ENTRÉE (RG-F3-01→07) ─────────────────────────────── --}}
<div class="modal fade" id="entreeModal" tabindex="-1" aria-labelledby="entreeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-primary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="entreeModalLabel">
                    <i class="fas fa-arrow-circle-down me-2 text-primary"></i>Enregistrer une entrée de stock
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="entree-form">
                @csrf
                <div class="modal-body">
                    <div class="fw-semibold border-bottom pb-2 mb-3">Nature de l'entrée</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="entree-type">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="entree-type" name="type_mouvement" required>
                                <option value="ENTREE">Entrée (approvisionnement / retour)</option>
                                <option value="REGULARISATION_PLUS">Régularisation positive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="entree-magasin">Magasin <span class="text-danger">*</span></label>
                            <select class="form-select" id="entree-magasin" name="magasin_id" required>
                                <option value="">Choisir…</option>
                                @foreach($magasins->where('statut', 'actif') as $magasin)
                                    <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="fw-semibold border-bottom pb-2 mb-3">Article et quantités</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="entree-article">Article <span class="text-danger">*</span></label>
                            <select class="form-select" id="entree-article" name="article_id" required>
                                <option value="">Chargement…</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="entree-quantite">Quantité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="entree-quantite" name="quantite" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="entree-cout">Coût unitaire <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="entree-cout" name="cout_unitaire" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="fw-semibold border-bottom pb-2 mb-3">Références</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="entree-reference">Référence document</label>
                            <input type="text" class="form-control" id="entree-reference" name="reference_document"
                                   maxlength="255" placeholder="Facture, bon de retour…">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="entree-motif">
                                Motif <span class="text-danger d-none" id="entree-motif-obligatoire">*</span>
                            </label>
                            <textarea class="form-control" id="entree-motif" name="motif" rows="1"
                                      placeholder="Obligatoire pour une régularisation"></textarea>
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

{{-- ── MODAL DÉTAIL ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="entreeDetailModal" tabindex="-1" aria-labelledby="entreeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-info bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="entreeDetailModalLabel">
                    <i class="fas fa-file-lines me-2 text-info"></i>Entrée <span id="detail-numero" class="font-monospace"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 small" id="detail-contenu"></dl>
            </div>
        </div>
    </div>
</div>
