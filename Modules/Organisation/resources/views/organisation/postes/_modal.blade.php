<div class="modal fade" id="posteModal" tabindex="-1" aria-labelledby="posteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="posteForm" class="needs-validation" novalidate>
            @csrf
            <input type="hidden" id="poste_id" name="id">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <!-- Header -->
                <div class="modal-header border-0 p-4 pb-0 align-items-start">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3" style="width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-desktop text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold mb-0" id="posteModalLabel" style="color: #333;">Nouveau Poste de Travail</h4>
                            <p class="text-muted small mb-0">Enregistrement d'une unité de travail dans le système</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.8rem;"></button>
                </div>

                <div class="modal-body p-4 pt-5">
                    <!-- SECTION 1: STRUCTURE ADMINISTRATIVE -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4 border-start border-primary border-4 ps-3">
                            <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px; font-size: 0.75rem; color: #444;">STRUCTURE ADMINISTRATIVE</h6>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="niveau_rattachement" class="form-label small fw-bold text-dark mb-1">Niveau de rattachement</label>
                                <select class="form-select border-0 shadow-none py-2 px-3" id="niveau_rattachement" name="niveau_rattachement" required style="background-color: #f0f2f5; border-radius: 6px;">
                                    <option value="">Choisir...</option>
                                    <option value="direction">Direction</option>
                                    <option value="service">Service</option>
                                    <option value="unite">Unité</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div id="dir-select-container" class="d-none">
                                    <label for="direction_id" class="form-label small fw-bold text-dark mb-1">Direction</label>
                                    <select class="form-select border-0 shadow-none py-2 px-3" id="direction_id" name="direction_id" style="background-color: #f0f2f5; border-radius: 6px;">
                                        <option value="">Sélectionner une direction</option>
                                        @foreach($directions as $dir)
                                            <option value="{{ $dir->id }}">{{ $dir->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="srv-select-container" class="d-none">
                                    <label for="service_id" class="form-label small fw-bold text-dark mb-1">Service</label>
                                    <select class="form-select border-0 shadow-none py-2 px-3" id="service_id" name="service_id" style="background-color: #f0f2f5; border-radius: 6px;">
                                        <option value="">Sélectionner un service</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="unt-select-container" class="d-none">
                                    <label for="unite_id" class="form-label small fw-bold text-dark mb-1">Unité</label>
                                    <select class="form-select border-0 shadow-none py-2 px-3" id="unite_id" name="unite_id" style="background-color: #f0f2f5; border-radius: 6px;">
                                        <option value="">Sélectionner une unité</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: EMPLACEMENT PHYSIQUE -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4 border-start border-primary border-4 ps-3">
                            <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px; font-size: 0.75rem; color: #444;">EMPLACEMENT PHYSIQUE</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="site_id" class="form-label small fw-bold text-dark mb-1">Site</label>
                                <select class="form-select border-0 shadow-none py-2 px-3" id="site_id" name="site_id" style="background-color: #f0f2f5; border-radius: 6px;">
                                    <option value="">Choisir...</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div id="bat-select-container" class="d-none">
                                    <label for="batiment_id" class="form-label small fw-bold text-dark mb-1">Bâtiment</label>
                                    <select class="form-select border-0 shadow-none py-2 px-3" id="batiment_id" name="batiment_id" style="background-color: #f0f2f5; border-radius: 6px;">
                                        <option value="">Sélectionner...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div id="etg-select-container" class="d-none">
                                    <label for="etage_id" class="form-label small fw-bold text-dark mb-1">Étage</label>
                                    <select class="form-select border-0 shadow-none py-2 px-3" id="etage_id" name="etage_id" style="background-color: #f0f2f5; border-radius: 6px;">
                                        <option value="">Sélectionner...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div id="loc-select-container" class="d-none">
                                    <label for="local_id" class="form-label small fw-bold text-dark mb-1">Local</label>
                                    <select class="form-select border-0 shadow-none py-2 px-3" id="local_id" name="local_id" style="background-color: #f0f2f5; border-radius: 6px;">
                                        <option value="">Sélectionner...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: INFORMATIONS DU POSTE -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-4 border-start border-primary border-4 ps-3">
                            <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px; font-size: 0.75rem; color: #444;">INFORMATIONS DU POSTE</h6>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="code" class="form-label small fw-bold text-dark mb-1">Code Poste</label>
                                <input type="text" class="form-control border-0 shadow-none py-2 px-3 fw-bold text-primary" id="code" name="code" placeholder="PST-2023-089" readonly style="background-color: #f0f2f5; border-radius: 6px;">
                            </div>
                            <div class="col-md-6">
                                <label for="libelle" class="form-label small fw-bold text-dark mb-1">Nom du Poste</label>
                                <input type="text" class="form-control border-0 shadow-none py-2 px-3" id="libelle" name="libelle" required placeholder="Ex: Station Diagnostic 01" style="background-color: #f0f2f5; border-radius: 6px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-2">Statut</label>
                                <div class="d-flex gap-4 align-items-center py-1">
                                    <div class="form-check custom-radio">
                                        <input class="form-check-input" type="radio" name="statut" id="statut_actif" value="actif" checked>
                                        <label class="form-check-label small fw-bold" for="statut_actif">Actif</label>
                                    </div>
                                    <div class="form-check custom-radio">
                                        <input class="form-check-input" type="radio" name="statut" id="statut_inactif" value="inactif">
                                        <label class="form-check-label small fw-bold" for="statut_inactif">Inactif</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="dossier_employe_id" class="form-label small fw-bold text-dark mb-1">Employé Affecté (Optionnel)</label>
                                <div class="input-group" style="background-color: #f0f2f5; border-radius: 6px; overflow: hidden;">
                                    <span class="input-group-text border-0 bg-transparent text-muted ps-3 pe-0"><i class="fas fa-user-tag small"></i></span>
                                    <select class="form-select border-0 shadow-none py-2 px-2 select2-custom" id="dossier_employe_id" name="dossier_employe_id">
                                        <option value="">Rechercher un employé...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold me-3" data-bs-dismiss="modal" style="font-size: 0.9rem;">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold d-flex align-items-center" id="btn-save-poste" style="border-radius: 8px; background-color: #005a92;">
                        <span>Créer le Poste</span>
                        <i class="fas fa-arrow-right ms-3 small"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .form-select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23666' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-size: 12px;
        background-position: right 0.75rem center;
    }
    .custom-radio .form-check-input:checked {
        background-color: #005a92;
        border-color: #005a92;
    }
    .custom-radio .form-check-input {
        width: 1.1rem;
        height: 1.1rem;
        margin-top: 0.15rem;
    }
    /* Select2 Custom Styling for #f0f2f5 background */
    .select2-container--bootstrap-5 .select2-selection {
        background-color: #f0f2f5 !important;
        border: none !important;
        border-radius: 6px !important;
        min-height: 38px !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding-left: 0 !important;
        color: #666 !important;
        font-size: 0.875rem !important;
    }
</style>
