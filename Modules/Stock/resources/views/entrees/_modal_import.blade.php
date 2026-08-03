{{-- Modale MD-IMPORT (UX §3.3) : collage / CSV → rapport → « Appliquer les N acceptés ». --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importer une liste de numéros de série</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="import-contenu">Collez les numéros (un par ligne)</label>
                    <textarea class="form-control font-monospace" id="import-contenu" rows="6"
                              placeholder="SN-0001&#10;SN-0002&#10;…"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="import-fichier">ou joignez un fichier CSV</label>
                    <input type="file" class="form-control" id="import-fichier" accept=".csv,.txt">
                </div>

                <div id="import-rapport" class="d-none">
                    <hr>
                    <h6 class="fw-bold">Rapport d'analyse</h6>
                    <table class="table table-sm align-middle">
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success">✓ Acceptés</span></td>
                                <td class="text-end fw-bold" id="rapport-acceptes">0</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">⚠ Doublons tampon</span></td>
                                <td class="text-end fw-bold" id="rapport-doublons">0</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">⛔ Déjà connus ParcInfo</span></td>
                                <td class="text-end fw-bold" id="rapport-connus">0</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-secondary">— En trop</span></td>
                                <td class="text-end fw-bold" id="rapport-en-trop">0</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="small" id="rapport-details" style="max-height: 160px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-outline-primary" id="btn-import-analyser">
                    <i class="bi bi-search me-1"></i>Analyser
                </button>
                <button type="button" class="btn btn-primary d-none" id="btn-import-appliquer"></button>
            </div>
        </div>
    </div>
</div>
