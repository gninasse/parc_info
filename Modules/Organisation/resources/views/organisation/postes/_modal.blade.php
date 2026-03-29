<div class="modal fade px-0" id="posteModal" tabindex="-1" aria-labelledby="posteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="fas fa-desktop text-primary fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-dark" id="posteModalLabel">Nouveau Poste de Travail</h5>
                        <small class="text-muted">Enregistrement d'une unité de travail dans le système</small>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="posteForm">
                @csrf
                <input type="hidden" id="poste_id" name="id">
                <div class="modal-body p-4">
                    <div class="row g-4">

                        {{-- COLONNE GAUCHE --}}
                        <div class="col-lg-6">

                            {{-- 01. STRUCTURE ADMINISTRATIVE --}}
                            <div class="mb-4">
                                <h6 class="section-title">
                                    <span class="section-num">01</span> Structure Administrative
                                </h6>

                                <div class="mb-3">
                                    <label for="niveau_rattachement" class="form-label field-label">Niveau de rattachement <span class="text-danger">*</span></label>
                                    <select class="form-select field-input" id="niveau_rattachement" name="niveau_rattachement" required>
                                        <option value="">Choisir un niveau...</option>
                                        <option value="direction">Direction</option>
                                        <option value="service">Service</option>
                                        <option value="unite">Unité</option>
                                    </select>
                                </div>

                                {{-- Direction --}}
                                <div class="mb-3 cascade-field d-none" id="field-direction">
                                    <label for="direction_id" class="form-label field-label">
                                        <i class="fas fa-building text-primary me-1 small"></i> Direction <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select field-input" id="direction_id" name="direction_id">
                                        <option value="">Sélectionner une direction...</option>
                                        @foreach($directions as $direction)
                                            <option value="{{ $direction->id }}">{{ $direction->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Service --}}
                                <div class="mb-3 cascade-field d-none" id="field-service">
                                    <label for="service_id" class="form-label field-label">
                                        <i class="fas fa-layer-group text-primary me-1 small"></i> Service <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <select class="form-select field-input" id="service_id" name="service_id" disabled>
                                            <option value="">— Sélectionner d'abord une direction —</option>
                                        </select>
                                        <div class="cascade-spinner d-none" id="spinner-service">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Unité --}}
                                <div class="mb-0 cascade-field d-none" id="field-unite">
                                    <label for="unite_id" class="form-label field-label">
                                        <i class="fas fa-sitemap text-primary me-1 small"></i> Unité <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <select class="form-select field-input" id="unite_id" name="unite_id" disabled>
                                            <option value="">— Sélectionner d'abord un service —</option>
                                        </select>
                                        <div class="cascade-spinner d-none" id="spinner-unite">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 02. INFORMATIONS DU POSTE --}}
                            <div>
                                <h6 class="section-title">
                                    <span class="section-num">02</span> Informations du Poste
                                </h6>
                                <div class="row g-3">
                                    <div class="col-5">
                                        <label for="code" class="form-label field-label">Code Poste</label>
                                        <input type="text" class="form-control field-input fw-bold text-primary" id="code" name="code"
                                               placeholder="Auto-généré" readonly>
                                    </div>
                                    <div class="col-7">
                                        <label for="libelle" class="form-label field-label">Libellé <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control field-input" id="libelle" name="libelle"
                                               placeholder="Ex: Station Diagnostic 01" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="description" class="form-label field-label">Description</label>
                                        <textarea class="form-control field-input" id="description" name="description"
                                                  rows="2" placeholder="Description optionnelle..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label field-label d-block">Statut</label>
                                        <div class="d-flex gap-3 mt-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="statut" id="statut_actif" value="actif" checked>
                                                <label class="form-check-label small fw-medium" for="statut_actif">Actif</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="statut" id="statut_inactif" value="inactif">
                                                <label class="form-check-label small fw-medium" for="statut_inactif">Inactif</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="statut" id="statut_renovation" value="en_renovation">
                                                <label class="form-check-label small fw-medium" for="statut_renovation">En rénovation</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- COLONNE DROITE --}}
                        <div class="col-lg-6">

                            {{-- 03. EMPLACEMENT PHYSIQUE --}}
                            <div class="mb-4">
                                <h6 class="section-title">
                                    <span class="section-num">03</span> Emplacement Physique
                                </h6>

                                {{-- Site --}}
                                <div class="mb-3">
                                    <label for="site_id" class="form-label field-label">
                                        <i class="fas fa-map-marker-alt text-danger me-1 small"></i> Site
                                    </label>
                                    <select class="form-select field-input" id="site_id" name="site_id">
                                        <option value="">Sélectionner un site...</option>
                                        @foreach($sites as $site)
                                            <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Bâtiment --}}
                                <div class="mb-3">
                                    <label for="batiment_id" class="form-label field-label">
                                        <i class="fas fa-building text-secondary me-1 small"></i> Bâtiment
                                    </label>
                                    <div class="position-relative">
                                        <select class="form-select field-input" id="batiment_id" name="batiment_id" disabled>
                                            <option value="">— Sélectionner d'abord un site —</option>
                                        </select>
                                        <div class="cascade-spinner d-none" id="spinner-batiment">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Étage --}}
                                <div class="mb-3">
                                    <label for="etage_id" class="form-label field-label">
                                        <i class="fas fa-layer-group text-secondary me-1 small"></i> Étage
                                    </label>
                                    <div class="position-relative">
                                        <select class="form-select field-input" id="etage_id" name="etage_id" disabled>
                                            <option value="">— Sélectionner d'abord un bâtiment —</option>
                                        </select>
                                        <div class="cascade-spinner d-none" id="spinner-etage">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Local --}}
                                <div class="mb-0">
                                    <label for="local_id" class="form-label field-label">
                                        <i class="fas fa-door-open text-secondary me-1 small"></i> Local
                                    </label>
                                    <div class="position-relative">
                                        <select class="form-select field-input" id="local_id" name="local_id" disabled>
                                            <option value="">— Sélectionner d'abord un étage —</option>
                                        </select>
                                        <div class="cascade-spinner d-none" id="spinner-local">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 04. AFFECTATION --}}
                            <div>
                                <h6 class="section-title">
                                    <span class="section-num">04</span> Affectation
                                </h6>
                                <div>
                                    <label for="dossier_employe_id" class="form-label field-label">
                                        <i class="fas fa-user-tie text-secondary me-1 small"></i> Employé affecté
                                        <span class="badge bg-light text-muted border ms-1" style="font-size: 0.65rem;">Optionnel</span>
                                    </label>
                                    <select class="form-select field-input select2-employe" id="dossier_employe_id" name="dossier_employe_id">
                                        <option value="">Rechercher un employé...</option>
                                    </select>
                                    <div class="form-text text-muted small mt-1">
                                        <i class="fas fa-info-circle me-1"></i> Laissez vide pour un poste vacant.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end align-items-center">
                    <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium me-2 shadow-none" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold rounded-2" id="btn-save-poste">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="btn-save-label">Créer le Poste</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#posteModal .section-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #344054;
    border-left: 3px solid #0d6efd;
    padding-left: 10px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
#posteModal .section-num {
    background: #0d6efd;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 4px;
    padding: 1px 6px;
}
#posteModal .field-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #475467;
    margin-bottom: 4px;
}
#posteModal .field-input {
    font-size: 0.875rem;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.45rem 0.75rem;
    transition: border-color 0.15s, box-shadow 0.15s;
}
#posteModal .field-input:focus {
    background-color: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}
#posteModal .field-input:disabled {
    background-color: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}
#posteModal .cascade-field {
    animation: fadeSlideIn 0.2s ease;
}
#posteModal .cascade-spinner {
    position: absolute;
    right: 36px;
    top: 50%;
    transform: translateY(-50%);
}
#posteModal .select2-container--bootstrap-5 .select2-selection {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    min-height: 38px;
}
#posteModal .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    font-size: 0.875rem;
    color: #344054;
    padding-top: 4px;
}
#posteModal .select2-container--bootstrap-5 .select2-selection:focus,
#posteModal .select2-container--bootstrap-5.select2-container--focus .select2-selection {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

{{-- Toute la logique JS est gérée par PosteTravailForm.js --}}
