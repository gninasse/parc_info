<div class="modal fade" id="employeModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="employeForm" novalidate>
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="fas fa-user-plus text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold mb-0" id="modalTitle">Nouveau Collaborateur</h4>
                            <p class="text-muted small mb-0">Complétez les informations pour l'enregistrement au module GRH</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- 01. INFORMATIONS DE BASE -->
                    <div class="mb-5">
                        <h6 class="text-primary fw-bold mb-4" style="letter-spacing: 1px; font-size: 0.8rem;">01. INFORMATIONS DE BASE</h6>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label for="matricule" class="form-label text-muted small fw-bold">MATRICULE</label>
                                <input type="text" class="form-control bg-light border-0 py-2" id="matricule" name="matricule" required placeholder="MAT-2024-">
                            </div>
                            <div class="col-md-4">
                                <label for="nom" class="form-label text-muted small fw-bold">NOM</label>
                                <input type="text" class="form-control bg-light border-0 py-2" id="nom" name="nom" required placeholder="ex: Kaboré">
                            </div>
                            <div class="col-md-4">
                                <label for="prenom" class="form-label text-muted small fw-bold">PRÉNOM</label>
                                <input type="text" class="form-control bg-light border-0 py-2" id="prenom" name="prenom" required placeholder="ex: Jean-Paul">
                            </div>
                            <div class="col-md-4">
                                <label for="date_naissance" class="form-label text-muted small fw-bold">DATE DE NAISSANCE</label>
                                <input type="date" class="form-control bg-light border-0 py-2" id="date_naissance" name="date_naissance">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">GENRE</label>
                                <div class="btn-group w-100 bg-light p-1 rounded" role="group">
                                    <input type="radio" class="btn-check" name="genre" id="genre_m" value="M" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary border-0 rounded py-2 fw-bold" for="genre_m">HOMME</label>
                                    <input type="radio" class="btn-check" name="genre" id="genre_f" value="F" autocomplete="off">
                                    <label class="btn btn-outline-primary border-0 rounded py-2 fw-bold" for="genre_f">FEMME</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="date_embauche" class="form-label text-muted small fw-bold">DATE D'EMBAUCHE</label>
                                <input type="date" class="form-control bg-light border-0 py-2" id="date_embauche" name="date_embauche">
                            </div>
                            <div class="col-12">
                                <label for="poste" class="form-label text-muted small fw-bold">POSTE OCCUPÉ</label>
                                <select class="form-select bg-light border-0 py-2" id="poste" name="poste">
                                    <option value="">Sélectionner un poste...</option>
                                    <option value="Chargé des Admissions">Chargé des Admissions</option>
                                    <option value="Infirmier">Infirmier</option>
                                    <option value="Médecin">Médecin</option>
                                    <option value="Administrateur">Administrateur</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 02. STRUCTURE ADMINISTRATIVE -->
                    <div class="mb-5 p-4 bg-light bg-opacity-50 rounded-4 border border-light">
                        <h6 class="text-primary fw-bold mb-4" style="letter-spacing: 1px; font-size: 0.8rem;">02. STRUCTURE ADMINISTRATIVE</h6>
                        <div class="row g-4">
                            <div class="col-md-7">
                                <div class="mb-4">
                                    <label for="niveau_rattachement" class="form-label text-muted small fw-bold">NIVEAU DE RATTACHEMENT</label>
                                    <select class="form-select bg-white border-0 py-2 shadow-sm" id="niveau_rattachement" name="niveau_rattachement" required>
                                        <option value="">Choisir...</option>
                                        <option value="direction">Direction</option>
                                        <option value="service">Service</option>
                                        <option value="unite">Unité</option>
                                    </select>
                                </div>
                                <div id="selection-structure-container" class="d-none animate__animated animate__fadeIn">
                                    <label id="selection-structure-label" class="form-label text-muted small fw-bold">SÉLECTIONNER LE SERVICE</label>
                                    <div id="hierarchical-selects">
                                        <!-- Direction Select -->
                                        <div id="dir-select-container" class="mb-3 d-none">
                                            <select class="form-select bg-white border-0 py-2 shadow-sm" id="direction_id" name="direction_id">
                                                <option value="">Sélectionner la Direction...</option>
                                                @foreach($directions as $dir)
                                                    <option value="{{ $dir->id }}">{{ $dir->libelle }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- Service Select -->
                                        <div id="srv-select-container" class="mb-3 d-none">
                                            <select class="form-select bg-white border-0 py-2 shadow-sm" id="service_id" name="service_id">
                                                <option value="">Sélectionner le Service...</option>
                                            </select>
                                        </div>
                                        <!-- Unit Select -->
                                        <div id="unt-select-container" class="mb-3 d-none">
                                            <select class="form-select bg-white border-0 py-2 shadow-sm" id="unite_id" name="unite_id">
                                                <option value="">Sélectionner l'Unité...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="card border-0 shadow-sm h-100" style="background-color: #f0f2f5; border-radius: 15px;">
                                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center p-4">
                                        <div class="mb-3">
                                            <div class="bg-primary text-white p-3 rounded-circle d-inline-block shadow">
                                                <i class="fas fa-sitemap fs-4"></i>
                                            </div>
                                        </div>
                                        <p class="small fw-bold text-uppercase text-muted mb-4" style="letter-spacing: 0.5px;">Visualisation structurelle</p>
                                        <div id="visualisation-preview" class="w-100 px-3">
                                            <div class="p-2 mb-2 bg-white rounded-3 shadow-sm border-start border-primary border-4 text-start small fw-bold" id="preview-direction">
                                                Direction Générale
                                            </div>
                                            <div class="ms-4 p-2 mb-2 bg-primary text-white rounded-3 shadow-sm text-start small fw-bold animate__animated animate__pulse d-none" id="preview-service">
                                                Service RH
                                            </div>
                                            <div class="ms-5 p-2 bg-white border border-dashed rounded-3 text-muted text-start small d-none" id="preview-unite">
                                                Unité (Non définie)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 03. CONTACTS & COMMUNICATION -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="text-primary fw-bold mb-0" style="letter-spacing: 1px; font-size: 0.8rem;">03. CONTACTS & COMMUNICATION</h6>
                            <button type="button" class="btn btn-link text-primary text-decoration-none fw-bold p-0 small" id="add-contact-btn">
                                <i class="fas fa-plus-circle me-1"></i> AJOUTER UN CONTACT
                            </button>
                        </div>

                        <div id="contacts-container">
                            <!-- Dynamic contacts rows -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <div class="w-100 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="avatar-group me-3 d-flex">
                                <img src="https://ui-avatars.com/api/?name=JS&background=0D6EFD&color=fff" class="rounded-circle border border-2 border-white shadow-sm" width="32" alt="">
                                <img src="https://ui-avatars.com/api/?name=AK&background=6C757D&color=fff" class="rounded-circle border border-2 border-white shadow-sm ms-n2" width="32" alt="">
                                <div class="rounded-circle border border-2 border-white shadow-sm ms-n2 bg-primary text-white d-flex align-items-center justify-content-center fw-bold small" style="width: 32px; height: 32px;">+12</div>
                            </div>
                            <p class="text-muted small mb-0">L'employé sera visible par l'équipe d'administration dès validation.</p>
                        </div>
                        <div class="d-flex gap-3 align-items-center">
                            <button type="button" class="btn btn-link text-dark text-decoration-none fw-bold" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-lg" id="btn-save" style="border-radius: 10px;">
                                <i class="fas fa-check-circle me-2"></i> CRÉER L'EMPLOYÉ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Template pour contact row -->
<template id="contact-row-template">
    <div class="contact-row row g-3 mb-3 align-items-center animate__animated animate__fadeIn">
        <div class="col-md-3">
            <select class="form-select bg-light border-0" name="contacts[INDEX][type_contact]">
                <option value="telephone">Téléphone</option>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
            </select>
        </div>
        <div class="col-md-7">
            <input type="text" class="form-control bg-light border-0" name="contacts[INDEX][valeur]" placeholder="Contact..." required>
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-outline-danger border-0 remove-contact-btn">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<style>
.ms-n2 { margin-left: -0.5rem !important; }
.shadow-xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
.border-dashed { border-style: dashed !important; }
</style>
