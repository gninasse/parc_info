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

        {{-- Header Card --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 72px; height: 72px; font-size: 2rem;">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-3 mb-1">
                            <h4 class="mb-0 fw-bold">{{ $employe->full_name }}</h4>
                            <span id="employe-status-badge"
                                  class="badge {{ $employe->est_actif ? 'bg-success' : 'bg-danger' }} px-3 py-2 rounded-pill">
                                <i class="fas {{ $employe->est_actif ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                                {{ $employe->est_actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        <div class="text-muted d-flex flex-wrap gap-4 small">
                            <span><i class="fas fa-id-card text-primary me-1"></i> {{ $employe->matricule }}</span>
                            <span><i class="fas fa-briefcase text-primary me-1"></i> {{ $employe->poste ?: 'Poste non défini' }}</span>
                            <span><i class="fas fa-sitemap text-primary me-1"></i> {{ $employe->organisation }}</span>
                        </div>
                    </div>
                    <div class="col-auto d-flex gap-2" id="view-actions">
                        <button type="button" class="btn btn-outline-warning" id="btn-toggle-status"
                                data-action="{{ $employe->est_actif ? 'désactiver' : 'activer' }}">
                            <i class="fas fa-power-off me-1"></i>
                            {{ $employe->est_actif ? 'Désactiver' : 'Activer' }}
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-edit-mode">
                            <i class="fas fa-edit me-1"></i> Modifier
                        </button>
                    </div>
                    <div class="col-auto d-none" id="form-actions">
                        <button type="button" class="btn btn-secondary me-2" id="btn-cancel">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-success" id="btn-save-profile">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Onglets --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="employe-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general" data-bs-toggle="tab" href="#pane-general">
                            <i class="fas fa-user me-1"></i> Général
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-contacts" data-bs-toggle="tab" href="#pane-contacts">
                            <i class="fas fa-address-book me-1"></i> Contacts
                            @if($employe->contacts->count() > 0)
                                <span class="badge bg-primary rounded-pill ms-1">{{ $employe->contacts->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content">

                    {{-- Onglet Général --}}
                    <div class="tab-pane fade show active" id="pane-general">

                        {{-- 01. Informations de base --}}
                        <div class="mb-4">
                            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                                <i class="fas fa-id-card me-2"></i>Informations de base
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="matricule" class="form-label">Matricule <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="matricule" name="matricule"
                                           value="{{ $employe->matricule }}" required disabled>
                                </div>
                                <div class="col-md-4">
                                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom"
                                           value="{{ $employe->nom }}" required disabled>
                                </div>
                                <div class="col-md-4">
                                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="prenom" name="prenom"
                                           value="{{ $employe->prenom }}" required disabled>
                                </div>
                                <div class="col-md-4">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance"
                                           value="{{ $employe->date_naissance ? $employe->date_naissance->format('Y-m-d') : '' }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Genre</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="genre" id="genre_m" value="M"
                                               autocomplete="off" {{ $employe->genre == 'M' ? 'checked' : '' }} disabled>
                                        <label class="btn btn-outline-primary" for="genre_m">
                                            <i class="fas fa-mars me-1"></i> Homme
                                        </label>
                                        <input type="radio" class="btn-check" name="genre" id="genre_f" value="F"
                                               autocomplete="off" {{ $employe->genre == 'F' ? 'checked' : '' }} disabled>
                                        <label class="btn btn-outline-danger" for="genre_f">
                                            <i class="fas fa-venus me-1"></i> Femme
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="date_embauche" class="form-label">Date d'embauche</label>
                                    <input type="date" class="form-control" id="date_embauche" name="date_embauche"
                                           value="{{ $employe->date_embauche ? $employe->date_embauche->format('Y-m-d') : '' }}" disabled>
                                </div>
                                <div class="col-12">
                                    <label for="poste" class="form-label">Poste occupé</label>
                                    <input type="text" class="form-control" id="poste" name="poste"
                                           value="{{ $employe->poste }}" placeholder="ex: Chargé des ressources humaines" disabled>
                                </div>
                            </div>
                        </div>

                        {{-- 02. Structure administrative --}}
                        <div>
                            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                                <i class="fas fa-sitemap me-2"></i>Structure administrative
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <div class="mb-3">
                                        <label for="niveau_rattachement" class="form-label">Niveau de rattachement <span class="text-danger">*</span></label>
                                        <select class="form-select" id="niveau_rattachement" name="niveau_rattachement" required disabled>
                                            <option value="direction" {{ $employe->niveau_rattachement == 'direction' ? 'selected' : '' }}>Direction</option>
                                            <option value="service" {{ $employe->niveau_rattachement == 'service' ? 'selected' : '' }}>Service</option>
                                            <option value="unite" {{ $employe->niveau_rattachement == 'unite' ? 'selected' : '' }}>Unité</option>
                                        </select>
                                    </div>
                                    <div id="selection-structure-container">
                                        <label id="selection-structure-label" class="form-label">Hiérarchie administrative</label>
                                        <div id="hierarchical-selects">
                                            <div id="dir-select-container" class="mb-2">
                                                <select class="form-select" id="direction_id" name="direction_id" disabled>
                                                    <option value="">Sélectionner la direction...</option>
                                                    @foreach($directions as $dir)
                                                        <option value="{{ $dir->id }}" {{ $employe->direction_id == $dir->id ? 'selected' : '' }}>
                                                            {{ $dir->libelle }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="srv-select-container" class="mb-2 {{ in_array($employe->niveau_rattachement, ['service', 'unite']) ? '' : 'd-none' }}">
                                                <select class="form-select" id="service_id" name="service_id" disabled>
                                                    <option value="">Sélectionner le service...</option>
                                                    @foreach($services as $srv)
                                                        @if($srv->direction_id == $employe->direction_id)
                                                            <option value="{{ $srv->id }}" {{ $employe->service_id == $srv->id ? 'selected' : '' }}>
                                                                {{ $srv->libelle }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="unt-select-container" class="mb-2 {{ $employe->niveau_rattachement == 'unite' ? '' : 'd-none' }}">
                                                <select class="form-select" id="unite_id" name="unite_id" disabled>
                                                    <option value="">Sélectionner l'unité...</option>
                                                    @foreach($unites as $unt)
                                                        @if($unt->service_id == $employe->service_id)
                                                            <option value="{{ $unt->id }}" {{ $employe->unite_id == $unt->id ? 'selected' : '' }}>
                                                                {{ $unt->libelle }}
                                                            </option>
                                                        @endif
                                                    @endforeach
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
                                                    {{ $employe->direction ? $employe->direction->libelle : 'Direction Générale' }}
                                                </div>
                                                <div class="ms-3 p-2 mb-2 bg-primary text-white rounded small fw-semibold {{ $employe->service ? '' : 'd-none' }}" id="preview-service">
                                                    {{ $employe->service ? $employe->service->libelle : 'Service' }}
                                                </div>
                                                <div class="ms-4 p-2 bg-white border border-dashed rounded text-muted small {{ $employe->unite ? '' : 'd-none' }}" id="preview-unite">
                                                    {{ $employe->unite ? $employe->unite->libelle : 'Unité' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /pane-general --}}

                    {{-- Onglet Contacts --}}
                    <div class="tab-pane fade" id="pane-contacts">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-primary fw-semibold mb-0">
                                <i class="fas fa-address-book me-2"></i>Contacts & Communication
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary d-none" id="add-contact-btn">
                                <i class="fas fa-plus me-1"></i> Ajouter un contact
                            </button>
                        </div>

                        <div id="contacts-container">
                            @foreach($employe->contacts as $index => $contact)
                                <div class="contact-row row g-2 mb-2 align-items-center">
                                    <div class="col-md-3">
                                        <select class="form-select form-select-sm" name="contacts[{{ $index }}][type_contact]" disabled>
                                            <option value="telephone" {{ $contact->type_contact == 'telephone' ? 'selected' : '' }}>Téléphone</option>
                                            <option value="email" {{ $contact->type_contact == 'email' ? 'selected' : '' }}>Email</option>
                                            <option value="whatsapp" {{ $contact->type_contact == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control form-control-sm"
                                               name="contacts[{{ $index }}][valeur]"
                                               value="{{ $contact->valeur }}"
                                               placeholder="Contact..." required disabled>
                                    </div>
                                    <div class="col-md-1 text-end contact-actions d-none">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-contact-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($employe->contacts->isEmpty())
                            <div id="no-contacts-alert" class="alert alert-light border text-center py-4">
                                <i class="fas fa-address-book text-muted fs-3 d-block mb-2"></i>
                                <p class="mb-0 text-muted small">Aucun contact enregistré pour cet employé.</p>
                            </div>
                        @endif
                    </div>{{-- /pane-contacts --}}

                </div>{{-- /tab-content --}}
            </div>
        </div>

    </form>
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
            <input type="text" class="form-control form-control-sm"
                   name="contacts[INDEX][valeur]" placeholder="Contact..." required>
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

@endsection

@push('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
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
