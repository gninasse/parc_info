@extends('core::layouts.master')

@section('header', $employe->full_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('grh.employes.index') }}">Dossiers employés</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $employe->full_name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Header Section --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="col">
                    <h3 class="mb-1 text-primary fw-bold">{{ $employe->full_name }}</h3>
                    <div class="text-muted d-flex align-items-center flex-wrap gap-3">
                        <span><i class="fas fa-id-card me-1"></i> <strong>Matricule:</strong> {{ $employe->matricule }}</span>
                        <span><i class="fas fa-briefcase me-1"></i> <strong>Poste:</strong> {{ $employe->poste ?: 'Non défini' }}</span>
                        <span><i class="fas fa-sitemap me-1"></i> <strong>Rattachement:</strong> {{ $employe->organisation }}</span>
                        <span id="employe-status-badge" class="badge {{ $employe->est_actif ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                            {{ $employe->est_actif ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-warning px-4" id="btn-toggle-status"
                            data-action="{{ $employe->est_actif ? 'désactiver' : 'activer' }}">
                        <i class="fas fa-power-off me-1"></i> {{ $employe->est_actif ? 'Désactiver' : 'Activer' }}
                    </button>
                    <button type="button" class="btn btn-primary px-4" id="btn-edit-mode">
                        <i class="fas fa-edit me-1"></i> Modifier le profil
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Section --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs border-0" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active border-0 px-4 py-3 fw-bold" data-bs-toggle="tab" href="#general" role="tab">
                        <i class="fas fa-info-circle me-1"></i> Informations Générales
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link border-0 px-4 py-3 fw-bold" data-bs-toggle="tab" href="#contacts" role="tab">
                        <i class="fas fa-phone me-1"></i> Contacts
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content">
                {{-- General Tab --}}
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form id="employe-form">
                        @csrf
                        @method('PUT')
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label for="matricule" class="form-label fw-bold">Matricule <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg bg-light" id="matricule" name="matricule"
                                       value="{{ $employe->matricule }}" required disabled>
                            </div>
                            <div class="col-md-4">
                                <label for="nom" class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg bg-light" id="nom" name="nom"
                                       value="{{ $employe->nom }}" required disabled>
                            </div>
                            <div class="col-md-4">
                                <label for="prenom" class="form-label fw-bold">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg bg-light" id="prenom" name="prenom"
                                       value="{{ $employe->prenom }}" required disabled>
                            </div>

                            <div class="col-md-4">
                                <label for="date_naissance" class="form-label fw-bold">Date de naissance</label>
                                <input type="date" class="form-control form-control-lg bg-light" id="date_naissance" name="date_naissance"
                                       value="{{ $employe->date_naissance ? $employe->date_naissance->format('Y-m-d') : '' }}" disabled>
                            </div>
                            <div class="col-md-4">
                                <label for="genre" class="form-label fw-bold">Genre</label>
                                <select class="form-select form-select-lg bg-light" id="genre" name="genre" disabled>
                                    <option value="">Choisir...</option>
                                    <option value="M" {{ $employe->genre == 'M' ? 'selected' : '' }}>Masculin</option>
                                    <option value="F" {{ $employe->genre == 'F' ? 'selected' : '' }}>Féminin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date_embauche" class="form-label fw-bold">Date d'embauche</label>
                                <input type="date" class="form-control form-control-lg bg-light" id="date_embauche" name="date_embauche"
                                       value="{{ $employe->date_embauche ? $employe->date_embauche->format('Y-m-d') : '' }}" disabled>
                            </div>

                            <div class="col-md-6">
                                <label for="poste" class="form-label fw-bold">Poste de travail</label>
                                <input type="text" class="form-control form-control-lg bg-light" id="poste" name="poste"
                                       value="{{ $employe->poste }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="niveau_rattachement" class="form-label fw-bold text-primary">Niveau de rattachement <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg bg-light" id="niveau_rattachement" name="niveau_rattachement" required disabled>
                                    <option value="direction" {{ $employe->niveau_rattachement == 'direction' ? 'selected' : '' }}>Direction</option>
                                    <option value="service" {{ $employe->niveau_rattachement == 'service' ? 'selected' : '' }}>Service</option>
                                    <option value="unite" {{ $employe->niveau_rattachement == 'unite' ? 'selected' : '' }}>Unité</option>
                                </select>
                            </div>

                            <div class="col-12" id="rattachement-container">
                                <label id="rattachement-label" class="form-label fw-bold">Structure rattachée <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg bg-light {{ $employe->niveau_rattachement != 'direction' ? 'd-none' : '' }}"
                                        id="direction_id" name="direction_id" disabled>
                                    <option value="">Choisir une direction...</option>
                                    @foreach($directions as $dir)
                                        <option value="{{ $dir->id }}" {{ $employe->direction_id == $dir->id ? 'selected' : '' }}>{{ $dir->libelle }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-lg bg-light {{ $employe->niveau_rattachement != 'service' ? 'd-none' : '' }}"
                                        id="service_id" name="service_id" disabled>
                                    <option value="">Choisir un service...</option>
                                    @foreach($services as $srv)
                                        <option value="{{ $srv->id }}" {{ $employe->service_id == $srv->id ? 'selected' : '' }}>{{ $srv->libelle }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-lg bg-light {{ $employe->niveau_rattachement != 'unite' ? 'd-none' : '' }}"
                                        id="unite_id" name="unite_id" disabled>
                                    <option value="">Choisir une unité...</option>
                                    @foreach($unites as $unt)
                                        <option value="{{ $unt->id }}" {{ $employe->unite_id == $unt->id ? 'selected' : '' }}>{{ $unt->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="row mt-5 d-none" id="form-actions">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary px-5 me-2" id="btn-cancel">
                                    <i class="fas fa-times me-1"></i> ANNULER
                                </button>
                                <button type="submit" class="btn btn-success px-5" id="btn-save-profile">
                                    <i class="fas fa-save me-1"></i> ENREGISTRER LES MODIFICATIONS
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Contacts Tab --}}
                <div class="tab-pane fade" id="contacts" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="mb-0 text-secondary"><i class="fas fa-address-book me-1"></i> Liste des contacts</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary px-3 shadow-sm d-none" id="btn-add-contact-row">
                            <i class="fas fa-plus me-1"></i> Ajouter un contact
                        </button>
                    </div>

                    <div id="contacts-list" class="row g-3">
                        @forelse($employe->contacts as $contact)
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 bg-light-subtle shadow-sm border-start border-4 border-primary">
                                    <div class="card-body py-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-primary-subtle text-primary px-3">{{ ucfirst($contact->type_contact) }}</span>
                                            @if($contact->est_whatsapp)
                                                <span class="badge bg-success-subtle text-success px-2 rounded-pill">
                                                    <i class="fab fa-whatsapp me-1"></i> WhatsApp
                                                </span>
                                            @endif
                                        </div>
                                        <h5 class="mb-0 fw-bold">{{ $contact->valeur }}</h5>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info py-4 text-center mb-0">
                                    <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                                    <p class="mb-0 fw-bold">Aucun contact enregistré pour cet employé.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    window.employeId = {{ $employe->id }};
    window.grhRoutes = {
        update: `/grh/employes/${window.employeId}`,
        toggle: `/grh/employes/${window.employeId}/toggle-status`
    };
</script>
<script type="module" src="{{ asset('js/modules/grh/employes/show.js') }}"></script>
@endpush
