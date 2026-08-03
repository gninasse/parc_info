{{-- Modale de sélection d'unité --}}
<div class="modal fade" id="uniteSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sélectionner une unité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="unt-filter-direction">
                            <option value="">Toutes les directions</option>
                            @foreach($directions ?? [] as $d)
                            <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="unt-filter-service">
                            <option value="">Tous les services</option>
                        </select>
                    </div>
                    <div class="col-md-4 ms-auto">
                        <input type="text" class="form-control form-control-sm" id="unt-search" placeholder="Code ou libellé...">
                    </div>
                </div>
                <div id="unt-skeleton" class="d-none">
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
                                <th>Service</th>
                                <th>Direction</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="unt-list" style="cursor: pointer;">
                            <style>.unt-cell { cursor: pointer; display: block; }</style>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="unt-confirm" disabled>Confirmer</button>
            </div>
        </div>
    </div>
</div>
