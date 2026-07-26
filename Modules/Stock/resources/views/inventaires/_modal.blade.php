{{-- _modal.blade.php --}}

<!-- Modal Initialiser une Campagne d'Inventaire -->
<div class="modal fade" id="inventaireModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-clipboard-list me-2 text-info"></i>Nouvel inventaire de stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="inventaire-form" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="i-magasin" class="form-label small fw-semibold">Magasin à inventorier <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="i-magasin" name="magasin_id" required>
                            <option value="">Sélectionner le magasin...</option>
                            @foreach($magasins as $m)
                                <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">
                            L'initialisation de l'inventaire va "geler" la situation théorique actuelle du stock du magasin choisi.
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info btn-sm text-white rounded-1" id="btn-save-inventaire-submit">
                        <i class="fas fa-play me-1"></i> Démarrer la campagne
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Consulter les Détails d'un Inventaire -->
<div class="modal fade" id="viewInventaireModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-clipboard-check me-2 text-secondary"></i>Inventaire de Stock : <span id="v-numero" class="text-info fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body small">
                <!-- Header Stats -->
                <div class="row g-2 mb-3 bg-light p-3 border rounded-1 mx-0 align-items-center">
                    <div class="col-md-3">
                        <span class="text-uppercase fw-semibold text-secondary">Statut :</span>
                        <span id="v-statut-badge" class="ms-1"></span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-uppercase fw-semibold text-secondary">Magasin :</span>
                        <span id="v-magasin-nom" class="fw-bold text-dark"></span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-uppercase fw-semibold text-secondary">Date Figeage :</span>
                        <span id="v-date-figeage" class="fw-semibold"></span>
                    </div>
                    <div class="col-md-3 text-end" id="v-cloture-wrapper">
                        <span class="text-uppercase fw-semibold text-secondary">Clôturé le :</span>
                        <span id="v-date-cloture" class="fw-semibold"></span>
                    </div>
                </div>

                <!-- Tableau des Lignes d'Inventaire -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover align-middle border">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Code Article</th>
                                <th>Désignation Article</th>
                                <th class="text-end" style="width: 130px;">Qte Théorique</th>
                                <th class="text-end" style="width: 130px;">Qte Réelle (Saisie)</th>
                                <th class="text-end" style="width: 100px;">Écart</th>
                                <th class="text-end" style="width: 150px;">Coût Réf</th>
                                <th class="text-end" style="width: 150px;">Valorisation Écart</th>
                            </tr>
                        </thead>
                        <tbody id="v-lignes-list">
                            <!-- Rempli en JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Traçabilité -->
                <div class="row g-2 border-top pt-3 mt-3">
                    <div class="col-md-6">
                        <span class="text-muted">Créé par :</span> <strong id="v-creator-nom"></strong>
                    </div>
                    <div class="col-md-6" id="v-validator-wrapper">
                        <span class="text-muted">Validé & Clôturé par :</span> <strong id="v-validator-nom"></strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0">
                <input type="hidden" id="v-id">
                <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
