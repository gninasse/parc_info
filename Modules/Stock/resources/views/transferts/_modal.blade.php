{{-- ── MODAL NOUVEAU TRANSFERT (RG-F5-01→07) ───────────────────────────── --}}
<div class="modal fade" id="transfertModal" tabindex="-1" aria-labelledby="transfertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-primary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="transfertModalLabel">
                    <i class="fas fa-exchange-alt me-2 text-primary"></i>Créer un transfert inter-magasins
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="transfert-form">
                @csrf
                <div class="modal-body">
                    <div class="fw-semibold border-bottom pb-2 mb-3">Magasins</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="transfert-source">Source <span class="text-danger">*</span></label>
                            <select class="form-select" id="transfert-source" name="magasin_source_id" required>
                                <option value="">Choisir…</option>
                                @foreach($magasins->where('statut', 'actif') as $magasin)
                                    <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="transfert-destination">Destination <span class="text-danger">*</span></label>
                            <select class="form-select" id="transfert-destination" name="magasin_destination_id" required>
                                <option value="">Choisir…</option>
                                @foreach($magasins->where('statut', 'actif') as $magasin)
                                    <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="fw-semibold border-bottom pb-2 mb-3">Article et quantité</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label" for="transfert-article">Article <span class="text-danger">*</span></label>
                            <select class="form-select" id="transfert-article" name="article_id" required>
                                <option value="">Chargement…</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="transfert-quantite">Quantité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="transfert-quantite" name="quantite" min="1" required>
                            <div class="form-text">Le stock est contrôlé à la validation (RG-F5-03).</div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="transfert-motif">Motif <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="transfert-motif" name="motif_creation" rows="2" minlength="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="fas fa-save me-1"></i>Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL DÉTAIL ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="transfertDetailModal" tabindex="-1" aria-labelledby="transfertDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-info bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="transfertDetailModalLabel">
                    <i class="fas fa-file-lines me-2 text-info"></i>Transfert <span id="detail-numero" class="font-monospace"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 small" id="detail-contenu"></dl>
            </div>
        </div>
    </div>
</div>
