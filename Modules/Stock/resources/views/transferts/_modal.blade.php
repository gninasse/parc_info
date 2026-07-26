{{-- _modal.blade.php --}}

<!-- Modal Créer un Transfert -->
<div class="modal fade" id="transfertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exchange-alt me-2 text-primary"></i>Demande de transfert inter-magasins
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transfert-form" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="f-source" class="form-label small fw-semibold">Magasin Source <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="f-source" name="magasin_source_id" required>
                                <option value="">Sélectionner le magasin source...</option>
                                @foreach($magasins as $m)
                                    <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="f-dest" class="form-label small fw-semibold">Magasin Destination <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="f-dest" name="magasin_destination_id" required>
                                <option value="">Sélectionner le magasin destination...</option>
                                @foreach($magasins as $m)
                                    <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="f-article" class="form-label small fw-semibold">Article <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="f-article" name="article_id" required>
                                <option value="">Sélectionner un article...</option>
                                @foreach($articles as $a)
                                    <option value="{{ $a->id }}">{{ $a->designation }} ({{ $a->code_article }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="f-quantite" class="form-label small fw-semibold">Quantité à transférer <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="f-quantite" name="quantite" required min="1">
                        </div>
                        <div class="col-12">
                            <label for="f-motif" class="form-label small fw-semibold">Motif du transfert <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="f-motif" name="motif_creation" rows="3" required placeholder="Expliquez la raison de ce transfert..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-1" id="btn-save-transfert-submit">
                        <i class="fas fa-save me-1"></i> Soumettre la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Consulter/Valider un Transfert -->
<div class="modal fade" id="viewTransfertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-file-invoice me-2 text-secondary"></i>Bon de transfert : <span id="v-numero" class="text-primary fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body small">
                <!-- Statut Header -->
                <div class="d-flex justify-content-between align-items-center bg-light p-3 mb-4 rounded-1 border">
                    <div>
                        <span class="text-uppercase fw-semibold text-secondary">Statut :</span>
                        <span id="v-statut-badge" class="ms-1"></span>
                    </div>
                    <div class="text-end">
                        <span class="text-uppercase fw-semibold text-secondary">Créé le :</span>
                        <span id="v-created-at" class="fw-semibold"></span>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Magasins impliqués -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100 p-3 rounded-1">
                            <h6 class="fw-bold mb-2 text-danger"><i class="fas fa-arrow-circle-up me-1"></i>Magasin Expéditeur (Source)</h6>
                            <div class="fs-6 fw-bold" id="v-source-nom"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100 p-3 rounded-1">
                            <h6 class="fw-bold mb-2 text-success"><i class="fas fa-arrow-circle-down me-1"></i>Magasin Destinataire (Destination)</h6>
                            <div class="fs-6 fw-bold" id="v-dest-nom"></div>
                        </div>
                    </div>

                    <!-- Article & Quantité -->
                    <div class="col-12">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Article</th>
                                    <th class="text-end" style="width: 150px;">Quantité demandée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong id="v-article-nom"></strong></td>
                                    <td class="text-end fs-6 fw-bold" id="v-quantite"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Motifs -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary text-uppercase mb-1">Motif de la demande :</label>
                            <div class="p-2 border rounded bg-white text-muted" id="v-motif-creation" style="min-height: 50px; white-space: pre-wrap;"></div>
                        </div>

                        <div class="mb-3 d-none" id="v-motif-rejet-wrapper">
                            <label class="form-label fw-bold text-danger text-uppercase mb-1">Motif du rejet :</label>
                            <div class="p-2 border border-danger-subtle rounded bg-danger-subtle text-danger" id="v-motif-rejet" style="min-height: 50px; white-space: pre-wrap;"></div>
                        </div>
                    </div>

                    <!-- Traçabilité / Signatures -->
                    <div class="col-12 border-top pt-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <span class="text-muted">Demandé par :</span> <strong id="v-creator-nom"></strong>
                            </div>
                            <div class="col-md-6" id="v-validator-wrapper">
                                <span class="text-muted">Validé/Rejeté par :</span> <strong id="v-validator-nom"></strong> <span class="text-muted" id="v-validation-date-wrapper">(le <span id="v-validation-date"></span>)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0">
                <input type="hidden" id="v-id">
                <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Fermer</button>

                @can('stock.transferts.admin')
                    <button type="button" class="btn btn-danger btn-sm text-white rounded-1 btn-action-admin d-none" id="v-btn-reject">
                        <i class="fas fa-times me-1"></i> Rejeter
                    </button>
                    <button type="button" class="btn btn-success btn-sm text-white rounded-1 btn-action-admin d-none" id="v-btn-approve">
                        <i class="fas fa-check me-1"></i> Valider & Transférer
                    </button>
                @endcan
            </div>
        </div>
    </div>
</div>
