{{-- ── MODAL CRÉATION / ÉDITION ────────────────────────────────────────── --}}
<div class="modal fade" id="magasinModal" tabindex="-1" aria-labelledby="magasinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-primary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="magasinModalLabel">
                    <i class="fas fa-store me-2 text-primary"></i><span id="magasin-modal-titre">Créer un magasin</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="magasin-form">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="magasin-id" id="magasin-id">

                    <div class="fw-semibold border-bottom pb-2 mb-3">Identification</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="magasin-code">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="magasin-code" name="code"
                                   maxlength="30" placeholder="MAG-XXXX" required>
                            <div class="form-text">Immuable après création.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="magasin-libelle">Libellé <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="magasin-libelle" name="libelle"
                                   minlength="3" maxlength="255" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="magasin-description">Description</label>
                        <textarea class="form-control" id="magasin-description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL RESPONSABLES ──────────────────────────────────────────────── --}}
<div class="modal fade" id="responsablesModal" tabindex="-1" aria-labelledby="responsablesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-secondary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="responsablesModalLabel">
                    <i class="fas fa-users me-2 text-secondary"></i>Responsables — <span id="responsables-magasin-libelle"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">

                <div class="fw-semibold border-bottom pb-2 mb-3">Responsables actuels</div>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Employé</th>
                                <th>Rôle</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="responsables-liste">
                            <tr><td colspan="5" class="text-center text-muted py-3">Aucun responsable.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="fw-semibold border-bottom pb-2 mb-3">Ajouter un responsable</div>
                <form id="responsable-form">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label" for="responsable-employe">Employé <span class="text-danger">*</span></label>
                            <select class="form-select" id="responsable-employe" name="employe_id" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="responsable-role">Rôle <span class="text-danger">*</span></label>
                            <select class="form-select" id="responsable-role" name="role" required>
                                <option value="adjoint">Adjoint</option>
                                <option value="principal">Principal</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="responsable-date-debut">Date de début</label>
                            <input type="date" class="form-control" id="responsable-date-debut" name="date_debut">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary rounded-1 w-100"
                                    data-bs-toggle="tooltip" title="Ajouter">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
