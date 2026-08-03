{{-- Modale de sélection de service --}}
<div class="modal fade" id="serviceSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sélectionner un service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" id="srv-filter-direction">
                            <option value="">Toutes les directions</option>
                            @foreach($directions ?? [] as $d)
                            <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 ms-auto">
                        <input type="text" class="form-control form-control-sm" id="srv-search" placeholder="Code ou libellé...">
                    </div>
                </div>
                <div id="srv-skeleton" class="d-none">
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
                                <th>Direction</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="srv-list" style="cursor: pointer;">
                            <style>.srv-cell { cursor: pointer; display: block; }</style>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="srv-confirm" disabled>Confirmer</button>
            </div>
        </div>
    </div>
</div>
