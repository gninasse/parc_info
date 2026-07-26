{{-- entrees/_modal.blade.php --}}

<!-- Modal Créer un Bon d'Entrée (Manuel) -->
<div class="modal fade" id="entreeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-arrow-circle-left me-2 text-success"></i>Nouveau bon d'entrée (Manuel)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="entree-form" novalidate>
                @csrf
                <div class="modal-body small">
                    <div class="row g-3">
                        <!-- Magasin & Article -->
                        <div class="col-12">
                            <label for="e-magasin" class="form-label fw-semibold">Magasin de destination <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="e-magasin" name="magasin_id" required>
                                <option value="">Sélectionner le magasin...</option>
                                @foreach($magasins as $m)
                                    <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="e-article" class="form-label fw-semibold">Article <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="e-article" name="article_id" required>
                                <option value="">Sélectionner un article...</option>
                                @foreach($articles as $a)
                                    <option value="{{ $a->id }}">{{ $a->designation }} ({{ $a->code_article }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quantité & Coût Unitaire -->
                        <div class="col-md-6">
                            <label for="e-quantite" class="form-label fw-semibold">Quantité entrée <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="e-quantite" name="quantite" required min="1" value="1">
                        </div>
                        <div class="col-md-6">
                            <label for="e-cout" class="form-label fw-semibold">Coût Unitaire (F CFA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="e-cout" name="cout_unitaire" required min="0" step="0.01">
                        </div>

                        <!-- Référence document -->
                        <div class="col-12">
                            <label for="e-ref-doc" class="form-label fw-semibold">Référence document d'origine <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="e-ref-doc" name="reference_document" required placeholder="Ex: BL-MANUEL-001, Facture #123...">
                        </div>

                        <!-- Motif -->
                        <div class="col-12">
                            <label for="e-motif" class="form-label fw-semibold">Motif / Commentaire</label>
                            <textarea class="form-control form-control-sm" id="e-motif" name="motif" rows="3" placeholder="Saisir un motif d'entrée..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success btn-sm text-white rounded-1" id="btn-save-entree-submit">
                        <i class="fas fa-save me-1"></i> Valider l'entrée
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
