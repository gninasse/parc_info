{{-- Modale de sélection de direction --}}
<div class="modal fade" id="directionSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sélectionner une direction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3 justify-content-end">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" id="dir-search" placeholder="Code ou libellé...">
                    </div>
                </div>
                <div id="dir-skeleton" class="d-none">
                    @for($i = 0; $i < 5; $i++)
                    <div class="placeholder-glow mb-2">
                        <div class="placeholder col-12" style="height:50px"></div>
                    </div>
                    @endfor
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="50"></th>
                                <th>Code</th>
                                <th>Libellé</th>
                                <th>Site</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="dir-list" style="cursor: pointer;">
                            <style>.dir-cell { cursor: pointer; display: block; }</style>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="dir-confirm" disabled>Confirmer</button>
            </div>
        </div>
    </div>
</div>
