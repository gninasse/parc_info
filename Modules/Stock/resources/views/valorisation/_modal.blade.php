{{-- _modal.blade.php --}}

<!-- Modal Consulter les Détails d'un Snapshot de Valorisation -->
<div class="modal fade" id="viewSnapshotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-file-invoice-dollar me-2 text-success"></i>Rapport de Valorisation : <span id="v-reference" class="text-success fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body small">
                <!-- Header Stats -->
                <div class="row g-2 mb-3 bg-light p-3 border rounded-1 mx-0 align-items-center">
                    <div class="col-md-3">
                        <span class="text-uppercase fw-semibold text-secondary">Type :</span>
                        <span id="v-type-badge" class="ms-1"></span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-uppercase fw-semibold text-secondary">Date calcul :</span>
                        <span id="v-date-snapshot" class="fw-bold text-dark"></span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-uppercase fw-semibold text-secondary">Généré par :</span>
                        <span id="v-creator" class="fw-semibold text-dark"></span>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="text-uppercase fw-semibold text-secondary">Valeur Totale Globale :</span>
                        <strong class="text-success fs-5 ms-1" id="v-valeur-globale"></strong>
                    </div>
                </div>

                <!-- Tableau des Lignes de Snapshot -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover align-middle border">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Magasin</th>
                                <th>Code Article</th>
                                <th>Désignation Article</th>
                                <th class="text-end" style="width: 120px;">Quantité</th>
                                <th class="text-end" style="width: 150px;">Cout Unit Moyen (FIFO)</th>
                                <th class="text-end" style="width: 180px;">Valeur Totale</th>
                            </tr>
                        </thead>
                        <tbody id="v-lignes-list">
                            <!-- Rempli en JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
