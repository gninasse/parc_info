{{-- Modal Employé --}}
<div class="modal fade" id="employeModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 p-2 rounded">
                        <i class="fas fa-user-plus text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="modalTitle">Nouveau Collaborateur</h5>
                        <small class="text-muted">Renseigner les informations du dossier employé</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="employeForm" novalidate>
                @csrf
                <div class="modal-body">

                    {{-- 01. Informations de base --}}
                    <div class="mb-4">
                        <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                            <i class="fas fa-id-card me-2"></i>Informations de base
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="matricule" class="form-label">Matricule <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="matricule" name="matricule"
                                       required placeholder="ex: EMP-2024-001">
                            </div>
                            <div class="col-md-4">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom"
                                       required placeholder="ex: Kaboré">
                            </div>
                            <div class="col-md-4">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prenom" name="prenom"
                                       required placeholder="ex: Jean-Paul">
                            </div>
                            <div class="col-md-4">
                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Genre</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="genre" id="genre_m" value="M" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary" for="genre_m">
                                        <i class="fas fa-mars me-1"></i> Homme
                                    </label>
                                    <input type="radio" class="btn-check" name="genre" id="genre_f" value="F" autocomplete="off">
                                    <label class="btn btn-outline-danger" for="genre_f">
                                        <i class="fas fa-venus me-1"></i> Femme
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="date_embauche" class="form-label">Date d'embauche</label>
                                <input type="date" class="form-control" id="date_embauche" name="date_embauche">
                            </div>
                            <div class="col-12">
                                <label for="poste" class="form-label">Poste occupé</label>
                                <input type="text" class="form-control" id="poste" name="poste"
                                       placeholder="ex: Chargé des ressources humaines">
                            </div>
                        </div>
                    </div>

                    {{-- 02. Structure administrative --}}
                    <div class="mb-4">
                        <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                            <i class="fas fa-sitemap me-2"></i>Structure administrative
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label for="niveau_rattachement" class="form-label">Niveau de rattachement <span class="text-danger">*</span></label>
                                    <select class="form-select" id="niveau_rattachement" name="niveau_rattachement" required>
                                        <option value="">Choisir...</option>
                                        <option value="direction">Direction</option>
                                        <option value="service">Service</option>
                                        <option value="unite">Unité</option>
                                    </select>
                                </div>
                                <div id="selection-structure-container" class="d-none">
                                    <label id="selection-structure-label" class="form-label">Sélectionner le service</label>
                                    <div id="hierarchical-selects">
                                        <div id="dir-select-container" class="mb-2 d-none">
                                            <select class="form-select" id="direction_id" name="direction_id">
                                                <option value="">Sélectionner la direction...</option>
                                                @foreach($directions as $dir)
                                                    <option value="{{ $dir->id }}">{{ $dir->libelle }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="srv-select-container" class="mb-2 d-none">
                                            <select class="form-select" id="service_id" name="service_id">
                                                <option value="">Sélectionner le service...</option>
                                            </select>
                                        </div>
                                        <div id="unt-select-container" class="mb-2 d-none">
                                            <select class="form-select" id="unite_id" name="unite_id">
                                                <option value="">Sélectionner l'unité...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-3">
                                        <i class="fas fa-sitemap text-primary fs-3 mb-2"></i>
                                        <p class="small text-muted fw-semibold mb-3">Visualisation de la structure</p>
                                        <div id="visualisation-preview" class="w-100 text-start">
                                            <div class="p-2 mb-2 bg-white rounded border-start border-primary border-3 small fw-semibold" id="preview-direction">
                                                Direction Générale
                                            </div>
                                            <div class="ms-3 p-2 mb-2 bg-primary text-white rounded small fw-semibold d-none" id="preview-service">
                                                Service
                                            </div>
                                            <div class="ms-4 p-2 bg-white border border-dashed rounded text-muted small d-none" id="preview-unite">
                                                Unité
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 03. Contacts --}}
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h6 class="text-primary fw-semibold mb-0">
                                <i class="fas fa-address-book me-2"></i>Contacts & Communication
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-contact-btn">
                                <i class="fas fa-plus me-1"></i> Ajouter un contact
                            </button>
                        </div>
                        <div id="contacts-container"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Template contact row --}}
<template id="contact-row-template">
    <div class="contact-row row g-2 mb-2 align-items-center">
        <div class="col-md-3">
            <select class="form-select form-select-sm" name="contacts[INDEX][type_contact]">
                <option value="telephone">Téléphone</option>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
            </select>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control form-select-sm" name="contacts[INDEX][valeur]"
                   placeholder="Valeur du contact..." required>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-contact-btn">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<style>
.border-dashed { border-style: dashed !important; }
</style>
