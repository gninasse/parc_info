{{-- Modale de sélection de local --}}
<div class="modal fade" id="localSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sélectionner un local</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="local-filter-site">
                            <option value="">Tous les sites</option>
                            @foreach($sites ?? [] as $s)
                            <option value="{{ $s->id }}">{{ $s->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="local-filter-batiment">
                            <option value="">Tous les bâtiments</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="local-filter-etage">
                            <option value="">Tous les étages</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" id="local-search" placeholder="Code ou libellé...">
                    </div>
                </div>
                <div id="local-skeleton" class="d-none">
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
                                <th>Type</th>
                                <th>Superficie</th>
                                <th>Étage</th>
                                <th>Bâtiment</th>
                                <th>Site</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="local-list" style="cursor: pointer;">
                            <style>.local-cell { cursor: pointer; display: block; }</style>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="local-confirm" disabled>Confirmer</button>
            </div>
        </div>
    </div>
</div>
