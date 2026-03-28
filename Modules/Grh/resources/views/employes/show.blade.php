@extends('grh::layouts.master')

@section('header', $employe->full_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('grh.employes.index') }}">Dossiers employés</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $employe->full_name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    <form id="employe-form" novalidate>
        @csrf
        @method('PUT')

        {{-- Header Section --}}
        <div class="card mb-4 border-0 shadow-lg" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                             style="width: 80px; height: 80px; font-size: 2.2rem;">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-3 mb-1">
                            <h3 class="mb-0 fw-bold text-dark">{{ $employe->full_name }}</h3>
                            <span id="employe-status-badge" class="badge {{ $employe->est_actif ? 'bg-success' : 'bg-danger' }} px-3 py-2 rounded-pill shadow-xs">
                                <i class="fas {{ $employe->est_actif ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                                {{ $employe->est_actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        <div class="text-muted d-flex align-items-center flex-wrap gap-4 small fw-bold">
                            <span><i class="fas fa-id-card text-primary me-2"></i> MATRICULE: <span class="text-dark">{{ $employe->matricule }}</span></span>
                            <span><i class="fas fa-briefcase text-primary me-2"></i> POSTE: <span class="text-dark">{{ $employe->poste ?: 'Non défini' }}</span></span>
                            <span><i class="fas fa-sitemap text-primary me-2"></i> RATACHEMENT: <span class="text-dark">{{ $employe->organisation }}</span></span>
                        </div>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-warning border-2 px-4 fw-bold" id="btn-toggle-status"
                                data-action="{{ $employe->est_actif ? 'désactiver' : 'activer' }}">
                            <i class="fas fa-power-off me-2"></i> {{ $employe->est_actif ? 'Désactiver le dossier' : 'Activer le dossier' }}
                        </button>
                        <button type="button" class="btn btn-primary px-4 fw-bold shadow-lg" id="btn-edit-mode">
                            <i class="fas fa-edit me-2"></i> MODIFIER LE PROFIL
                        </button>
                        <div class="d-none" id="form-actions">
                            <button type="button" class="btn btn-link text-dark text-decoration-none fw-bold me-3" id="btn-cancel">ANNULER</button>
                            <button type="submit" class="btn btn-success px-4 fw-bold shadow-lg" id="btn-save-profile">
                                <i class="fas fa-save me-2"></i> ENREGISTRER
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-lg" style="border-radius: 20px;">
            <div class="card-body p-5">
                <!-- 01. INFORMATIONS DE BASE -->
                <div class="mb-5">
                    <h6 class="text-primary fw-bold mb-4" style="letter-spacing: 1px; font-size: 0.8rem;">01. INFORMATIONS DE BASE</h6>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="matricule" class="form-label text-muted small fw-bold">MATRICULE</label>
                            <input type="text" class="form-control bg-light border-0 py-2" id="matricule" name="matricule"
                                   value="{{ $employe->matricule }}" required disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="nom" class="form-label text-muted small fw-bold">NOM</label>
                            <input type="text" class="form-control bg-light border-0 py-2" id="nom" name="nom"
                                   value="{{ $employe->nom }}" required disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="prenom" class="form-label text-muted small fw-bold">PRÉNOM</label>
                            <input type="text" class="form-control bg-light border-0 py-2" id="prenom" name="prenom"
                                   value="{{ $employe->prenom }}" required disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="date_naissance" class="form-label text-muted small fw-bold">DATE DE NAISSANCE</label>
                            <input type="date" class="form-control bg-light border-0 py-2" id="date_naissance" name="date_naissance"
                                   value="{{ $employe->date_naissance ? $employe->date_naissance->format('Y-m-d') : '' }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">GENRE</label>
                            <div class="btn-group w-100 bg-light p-1 rounded" role="group">
                                <input type="radio" class="btn-check" name="genre" id="genre_m" value="M" autocomplete="off" {{ $employe->genre == 'M' ? 'checked' : '' }} disabled>
                                <label class="btn btn-outline-primary border-0 rounded py-2 fw-bold" for="genre_m">HOMME</label>
                                <input type="radio" class="btn-check" name="genre" id="genre_f" value="F" autocomplete="off" {{ $employe->genre == 'F' ? 'checked' : '' }} disabled>
                                <label class="btn btn-outline-primary border-0 rounded py-2 fw-bold" for="genre_f">FEMME</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="date_embauche" class="form-label text-muted small fw-bold">DATE D'EMBAUCHE</label>
                            <input type="date" class="form-control bg-light border-0 py-2" id="date_embauche" name="date_embauche"
                                   value="{{ $employe->date_embauche ? $employe->date_embauche->format('Y-m-d') : '' }}" disabled>
                        </div>
                        <div class="col-12">
                            <label for="poste" class="form-label text-muted small fw-bold">POSTE OCCUPÉ</label>
                            <select class="form-select bg-light border-0 py-2" id="poste" name="poste" disabled>
                                <option value="">Sélectionner un poste...</option>
                                <option value="Chargé des Admissions" {{ $employe->poste == 'Chargé des Admissions' ? 'selected' : '' }}>Chargé des Admissions</option>
                                <option value="Infirmier" {{ $employe->poste == 'Infirmier' ? 'selected' : '' }}>Infirmier</option>
                                <option value="Médecin" {{ $employe->poste == 'Médecin' ? 'selected' : '' }}>Médecin</option>
                                <option value="Administrateur" {{ $employe->poste == 'Administrateur' ? 'selected' : '' }}>Administrateur</option>
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
                                <select class="form-select bg-white border-0 py-2 shadow-sm" id="niveau_rattachement" name="niveau_rattachement" required disabled>
                                    <option value="direction" {{ $employe->niveau_rattachement == 'direction' ? 'selected' : '' }}>Direction</option>
                                    <option value="service" {{ $employe->niveau_rattachement == 'service' ? 'selected' : '' }}>Service</option>
                                    <option value="unite" {{ $employe->niveau_rattachement == 'unite' ? 'selected' : '' }}>Unité</option>
                                </select>
                            </div>
                            <div id="selection-structure-container">
                                <label id="selection-structure-label" class="form-label text-muted small fw-bold">HIÉRARCHIE ADMINISTRATIVE</label>
                                <div id="hierarchical-selects">
                                    <!-- Direction Select -->
                                    <div id="dir-select-container" class="mb-3">
                                        <select class="form-select bg-white border-0 py-2 shadow-sm" id="direction_id" name="direction_id" disabled>
                                            <option value="">Sélectionner la Direction...</option>
                                            @foreach($directions as $dir)
                                                <option value="{{ $dir->id }}" {{ $employe->direction_id == $dir->id ? 'selected' : '' }}>{{ $dir->libelle }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Service Select -->
                                    <div id="srv-select-container" class="mb-3 {{ in_array($employe->niveau_rattachement, ['service', 'unite']) ? '' : 'd-none' }}">
                                        <select class="form-select bg-white border-0 py-2 shadow-sm" id="service_id" name="service_id" disabled>
                                            <option value="">Sélectionner le Service...</option>
                                            @foreach($services as $srv)
                                                @if($srv->direction_id == $employe->direction_id)
                                                    <option value="{{ $srv->id }}" {{ $employe->service_id == $srv->id ? 'selected' : '' }}>{{ $srv->libelle }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Unit Select -->
                                    <div id="unt-select-container" class="mb-3 {{ $employe->niveau_rattachement == 'unite' ? '' : 'd-none' }}">
                                        <select class="form-select bg-white border-0 py-2 shadow-sm" id="unite_id" name="unite_id" disabled>
                                            <option value="">Sélectionner l'Unité...</option>
                                            @foreach($unites as $unt)
                                                @if($unt->service_id == $employe->service_id)
                                                    <option value="{{ $unt->id }}" {{ $employe->unite_id == $unt->id ? 'selected' : '' }}>{{ $unt->libelle }}</option>
                                                @endif
                                            @endforeach
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
                                            {{ $employe->direction ? $employe->direction->libelle : 'Direction Générale' }}
                                        </div>
                                        <div class="ms-4 p-2 mb-2 bg-primary text-white rounded-3 shadow-sm text-start small fw-bold {{ $employe->service ? '' : 'd-none' }}" id="preview-service">
                                            {{ $employe->service ? $employe->service->libelle : 'Service RH' }}
                                        </div>
                                        <div class="ms-5 p-2 bg-white border border-dashed rounded-3 text-muted text-start small {{ $employe->unite ? '' : 'd-none' }}" id="preview-unite">
                                            {{ $employe->unite ? $employe->unite->libelle : 'Unité (Non définie)' }}
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
                        <button type="button" class="btn btn-link text-primary text-decoration-none fw-bold p-0 small d-none" id="add-contact-btn">
                            <i class="fas fa-plus-circle me-1"></i> AJOUTER UN CONTACT
                        </button>
                    </div>

                    <div id="contacts-container">
                        @foreach($employe->contacts as $index => $contact)
                            <div class="contact-row row g-3 mb-3 align-items-center">
                                <div class="col-md-3">
                                    <select class="form-select bg-light border-0" name="contacts[{{ $index }}][type_contact]" disabled>
                                        <option value="telephone" {{ $contact->type_contact == 'telephone' ? 'selected' : '' }}>Téléphone</option>
                                        <option value="email" {{ $contact->type_contact == 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="whatsapp" {{ $contact->type_contact == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control bg-light border-0" name="contacts[{{ $index }}][valeur]"
                                           value="{{ $contact->valeur }}" placeholder="Contact..." required disabled>
                                </div>
                                <div class="col-md-2 text-end d-none contact-actions">
                                    <button type="button" class="btn btn-outline-danger border-0 remove-contact-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($employe->contacts->isEmpty())
                        <div id="no-contacts-alert" class="alert alert-light border border-dashed text-center py-4">
                            <p class="mb-0 text-muted small fw-bold">Aucun contact enregistré pour cet employé.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
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
.bg-primary-subtle { background-color: rgba(13, 110, 253, 0.1); }
</style>
@endsection

@push('js')
<script>
    window.employeId = {{ $employe->id }};
    window.grhRoutes = {
        update: `/grh/employes/${window.employeId}`,
        toggle: `/grh/employes/${window.employeId}/toggle-status`,
        services: (id) => `/grh/employes/services-by-direction/${id}`,
        unites: (id) => `/grh/employes/unites-by-service/${id}`
    };
</script>
<script type="module" src="{{ asset('js/modules/grh/employes/show.js') }}"></script>
@endpush
