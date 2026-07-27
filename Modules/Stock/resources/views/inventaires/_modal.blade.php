{{-- ── MODAL OUVERTURE (RG-F6-01/02) ───────────────────────────────────── --}}
<div class="modal fade" id="inventaireModal" tabindex="-1" aria-labelledby="inventaireModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-primary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="inventaireModalLabel">
                    <i class="fas fa-clipboard-check me-2 text-primary"></i>Ouvrir un inventaire
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="inventaire-form">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="inventaire-magasin">Magasin <span class="text-danger">*</span></label>
                        <select class="form-select" id="inventaire-magasin" name="magasin_id" required>
                            <option value="">Choisir…</option>
                            @foreach($magasins->where('statut', 'actif') as $magasin)
                                <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Le stock théorique est figé à l'ouverture (RG-F6-02).</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="inventaire-date">Date d'inventaire</label>
                        <input type="date" class="form-control" id="inventaire-date" name="date_inventaire">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="fas fa-save me-1"></i>Ouvrir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL SAISIE DES COMPTAGES (RG-F6-03/04) ────────────────────────── --}}
<div class="modal fade" id="saisieModal" tabindex="-1" aria-labelledby="saisieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-info bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="saisieModalLabel">
                    <i class="fas fa-pen me-2 text-info"></i>Comptages — <span id="saisie-numero" class="font-monospace"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="saisie-form">
                @csrf
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Article</th>
                                    <th class="text-end">Théorique</th>
                                    <th class="text-end" style="width: 15%;">Compté</th>
                                    <th class="text-end">Écart</th>
                                </tr>
                            </thead>
                            <tbody id="saisie-lignes"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="fas fa-save me-1"></i>Enregistrer les comptages
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
