<div class="modal fade px-0" id="posteModal" tabindex="-1" aria-labelledby="posteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center">
                    <div class="bg-light p-3 rounded-3 me-3">
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

                    <!-- STRUCTURE ADMINISTRATIVE -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-dark mb-3 ps-2" style="border-left: 4px solid #0d6efd; font-size: 0.75rem; letter-spacing: 1px;">Structure Administrative</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="niveau_rattachement" class="form-label text-dark fw-semibold mb-1 small">Niveau de rattachement</label>
                                <select class="form-select bg-light border-0 py-2" id="niveau_rattachement" name="niveau_rattachement" required>
                                    <option value="direction">Direction</option>
                                    <option value="service">Service</option>
                                    <option value="unite">Unité</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="col-direction">
                                <label for="direction_id" class="form-label text-dark fw-semibold mb-1 small">Direction</label>
                                <select class="form-select bg-light border-0 py-2" id="direction_id" name="direction_id" required>
                                    <option value="">Sélectionner une direction</option>
                                    @foreach($directions as $direction)
                                        <option value="{{ $direction->id }}">{{ $direction->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-none" id="col-service">
                                <label for="service_id" class="form-label text-dark fw-semibold mb-1 small">Service</label>
                                <select class="form-select bg-light border-0 py-2" id="service_id" name="service_id">
                                    <option value="">Sélectionner un service</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-none" id="col-unite">
                                <label for="unite_id" class="form-label text-dark fw-semibold mb-1 small">Unité</label>
                                <select class="form-select bg-light border-0 py-2" id="unite_id" name="unite_id">
                                    <option value="">Sélectionner une unité</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- EMPLACEMENT PHYSIQUE -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-dark mb-3 ps-2" style="border-left: 4px solid #0d6efd; font-size: 0.75rem; letter-spacing: 1px;">Emplacement Physique</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="site_id" class="form-label text-dark fw-semibold mb-1 small">Site</label>
                                <select class="form-select bg-light border-0 py-2" id="site_id" name="site_id">
                                    <option value="">Sélectionner un site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="batiment_id" class="form-label text-dark fw-semibold mb-1 small">Bâtiment</label>
                                <select class="form-select bg-light border-0 py-2" id="batiment_id" name="batiment_id">
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="etage_id" class="form-label text-dark fw-semibold mb-1 small">Étage</label>
                                <select class="form-select bg-light border-0 py-2" id="etage_id" name="etage_id">
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="local_id" class="form-label text-dark fw-semibold mb-1 small">Local</label>
                                <select class="form-select bg-light border-0 py-2" id="local_id" name="local_id">
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMATIONS DU POSTE -->
                    <div class="mb-2">
                        <h6 class="text-uppercase fw-bold text-dark mb-3 ps-2" style="border-left: 4px solid #0d6efd; font-size: 0.75rem; letter-spacing: 1px;">Informations du Poste</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="code" class="form-label text-dark fw-semibold mb-1 small">Code Poste</label>
                                <input type="text" class="form-control bg-light border-0 py-2 fw-bold text-primary" id="code" name="code" placeholder="PST-2023-089" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="libelle" class="form-label text-dark fw-semibold mb-1 small">Nom du Poste</label>
                                <input type="text" class="form-control bg-light border-0 py-2" id="libelle" name="libelle" placeholder="Ex: Station Diagnostic 01" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div id="statut_container" class="col-md-6">
                                <label class="form-label text-dark fw-semibold mb-1 small d-block">Statut</label>
                                <div class="d-flex mt-2">
                                    <div class="form-check me-3">
                                        <input class="form-check-input shadow-none" type="radio" name="statut" id="statut_actif" value="actif" checked>
                                        <label class="form-check-label text-dark small fw-medium" for="statut_actif">Actif</label>
                                    </div>
                                    <div class="form-check me-3">
                                        <input class="form-check-input shadow-none" type="radio" name="statut" id="statut_inactif" value="inactif">
                                        <label class="form-check-label text-dark small fw-medium" for="statut_inactif">Inactif</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input shadow-none" type="radio" name="statut" id="statut_renovation" value="en_renovation">
                                        <label class="form-check-label text-dark small fw-medium" for="statut_renovation">Rénovation</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="dossier_employe_id" class="form-label text-dark fw-semibold mb-1 small">Employé Affecté (Optionnel)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-user-plus text-muted"></i></span>
                                    <select class="form-select bg-light border-0 py-2 select2" id="dossier_employe_id" name="dossier_employe_id">
                                        <option value="">Rechercher un employé...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end align-items-center">
                    <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium me-3 small shadow-none" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold d-flex align-items-center rounded-2" id="btn-save-poste">
                        <span>Créer le Poste</span>
                        <i class="fas fa-arrow-right ms-2 fs-7"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #posteModal .form-select, #posteModal .form-control {
        font-size: 0.875rem;
    }
    #posteModal .form-select:focus, #posteModal .form-control:focus {
        background-color: #f1f4f9 !important;
        box-shadow: none;
    }
    #posteModal .input-group-text {
        font-size: 0.875rem;
    }
    #posteModal .btn-primary {
        background-color: #00609b;
        border-color: #00609b;
    }
    #posteModal .btn-primary:hover {
        background-color: #004d7d;
        border-color: #004d7d;
    }
    #posteModal .select2-container--bootstrap-5 .select2-selection {
        background-color: #f8f9fa;
        border: none;
        min-height: 40px;
        display: flex;
        align-items: center;
    }
    #posteModal .select2-container--bootstrap-5 .select2-selection__rendered {
        color: #6c757d;
        padding-left: 0;
    }
</style>
