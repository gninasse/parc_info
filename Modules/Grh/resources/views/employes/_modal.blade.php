<div class="modal fade" id="employeModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="employeForm" novalidate>
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title" id="modalTitle">Nouveau Dossier Employé</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4 mb-3">
                            <label for="matricule" class="form-label fw-bold">Matricule <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="matricule" name="matricule" required placeholder="Ex: EMP001">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="nom" class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" required placeholder="NOM">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="prenom" class="form-label fw-bold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required placeholder="Prénom">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 mb-3">
                            <label for="date_naissance" class="form-label fw-bold">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="genre" class="form-label fw-bold">Genre</label>
                            <select class="form-select" id="genre" name="genre">
                                <option value="">Choisir...</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="date_embauche" class="form-label fw-bold">Date d'embauche</label>
                            <input type="date" class="form-control" id="date_embauche" name="date_embauche">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="poste" class="form-label fw-bold">Poste de travail</label>
                            <input type="text" class="form-control" id="poste" name="poste" placeholder="Intitulé du poste">
                        </div>
                        <div class="col-md-6">
                            <label for="niveau_rattachement" class="form-label fw-bold">Niveau de rattachement <span class="text-danger">*</span></label>
                            <select class="form-select" id="niveau_rattachement" name="niveau_rattachement" required>
                                <option value="">Choisir...</option>
                                <option value="direction">Direction</option>
                                <option value="service">Service</option>
                                <option value="unite">Unité</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 d-none" id="rattachement-container">
                        <div class="col-12">
                            <label id="rattachement-label" class="form-label fw-bold">Structure <span class="text-danger">*</span></label>
                            <select class="form-select d-none" id="direction_id" name="direction_id">
                                <option value="">Choisir une direction...</option>
                                @foreach($directions as $dir)
                                    <option value="{{ $dir->id }}">{{ $dir->libelle }}</option>
                                @endforeach
                            </select>
                            <select class="form-select d-none" id="service_id" name="service_id">
                                <option value="">Choisir un service...</option>
                                @foreach($services as $srv)
                                    <option value="{{ $srv->id }}">{{ $srv->libelle }}</option>
                                @endforeach
                            </select>
                            <select class="form-select d-none" id="unite_id" name="unite_id">
                                <option value="">Choisir une unité...</option>
                                @foreach($unites as $unt)
                                    <option value="{{ $unt->id }}">{{ $unt->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3 text-secondary"><i class="fas fa-address-book me-1"></i> Contacts (Optionnel)</h6>

                    <div id="contacts-container">
                        <div class="contact-row row g-3 mb-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Type</label>
                                <select class="form-select form-select-sm" name="contacts[0][type_contact]">
                                    <option value="telephone">Téléphone</option>
                                    <option value="email">Email</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Valeur</label>
                                <input type="text" class="form-control form-control-sm" name="contacts[0][valeur]" placeholder="Contact...">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="contacts[0][est_whatsapp]" value="1">
                                    <label class="form-check-label small">WhatsApp?</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">ANNULER</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btn-save">
                        <i class="fas fa-save me-1"></i> ENREGISTRER
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
