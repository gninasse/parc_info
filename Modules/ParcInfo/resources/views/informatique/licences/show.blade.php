@extends('parcinfo::layouts.master')

@section('header', 'Fiche Licence')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.licences.index') }}">Licences</a></li>
    <li class="breadcrumb-item active">{{ $licence->numero_contrat ?: $licence->id }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2-bootstrap-5-theme.min.css') }}">
<style>
    .select2-container--bootstrap-5 .select2-selection { border-radius: 0.375rem; }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- ── En-tête / Profil ── --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="d-flex flex-column flex-md-row">
                    <div class="bg-primary bg-opacity-10 p-4 d-flex align-items-center justify-content-center" style="min-width: 200px;">
                        <i class="fas fa-key fa-5x text-primary"></i>
                    </div>
                    <div class="p-4 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">{{ $licence->logiciel->nom }}</h4>
                                <span class="badge bg-light text-primary border border-primary border-opacity-25 px-3 py-2">
                                    <i class="fas fa-file-contract me-2"></i>{{ $licence->numero_contrat ?: 'Contrat non défini' }}
                                </span>
                                <span class="badge bg-{{ $licence->actif ? 'success' : 'danger' }} px-3 py-2 ms-2">
                                    {{ $licence->actif ? 'ACTIF' : 'INACTIF' }}
                                </span>
                            </div>
                            <div id="view-actions">
                                <button class="btn btn-primary px-4" id="btn-enable-edit">
                                    <i class="fas fa-edit me-2"></i>Modifier
                                </button>
                                <button class="btn btn-outline-primary ms-2" id="btn-open-renouveler">
                                    <i class="fas fa-sync me-2"></i>Renouveler
                                </button>
                            </div>
                            <div id="form-actions" class="d-none">
                                <button type="button" class="btn btn-success px-4" id="btn-save-edit">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                                <button type="button" class="btn btn-secondary ms-2" onclick="window.location.reload()">
                                    Annuler
                                </button>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Expiration</div>
                                <div class="fw-bold text-{{ $licence->statut_validite === 'VALIDE' ? 'success' : 'danger' }}">
                                    {{ $licence->date_expiration->format('d/m/Y') }}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Utilisation</div>
                                <div class="fw-bold">{{ $licence->nombre_postes_utilises }} / {{ $licence->nombre_postes_accordes ?: '∞' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Type Activation</div>
                                <div class="fw-bold">{{ ucfirst($licence->type_activation) }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Coût Total</div>
                                <div class="fw-bold text-primary">{{ number_format($licence->cout_total, 2, ',', ' ') }} {{ $licence->devise }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Détails & Affectations ── --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 text-center">
                <ul class="nav nav-tabs card-header-tabs" id="licenceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#tab-infos" type="button">
                            <i class="fas fa-info-circle me-2"></i>Informations générales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="aff-tab" data-bs-toggle="tab" data-bs-target="#tab-aff" type="button">
                            <i class="fas fa-users me-2"></i>Affectations actives
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="docs-tab" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button">
                            <i class="fas fa-folder me-2"></i>Documents
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="licenceTabsContent">
                    {{-- Tab Infos --}}
                    <div class="tab-pane fade show active" id="tab-infos" role="tabpanel">
                        <form id="form-edit-licence">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Logiciel</label>
                                    <select name="logiciel_id" class="form-select" disabled required>
                                        @foreach($logiciels as $l)
                                            <option value="{{ $l->id }}" {{ $licence->logiciel_id == $l->id ? 'selected' : '' }}>{{ $l->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Clé de licence</label>
                                    <input type="text" name="cle_licence" class="form-control" value="{{ $licence->cle_licence }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Numéro de contrat</label>
                                    <input type="text" name="numero_contrat" class="form-control" value="{{ $licence->numero_contrat }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Type Activation</label>
                                    <select name="type_activation" class="form-select" disabled required>
                                        <option value="volume" {{ $licence->type_activation == 'volume' ? 'selected' : '' }}>Volume (CAL)</option>
                                        <option value="concurrent" {{ $licence->type_activation == 'concurrent' ? 'selected' : '' }}>Concurrent</option>
                                        <option value="subscription" {{ $licence->type_activation == 'subscription' ? 'selected' : '' }}>Abonnement</option>
                                        <option value="free" {{ $licence->type_activation == 'free' ? 'selected' : '' }}>Gratuit</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Modèle Licencing</label>
                                    <select name="modele_licencing" class="form-select" disabled required>
                                        <option value="device" {{ $licence->modele_licencing == 'device' ? 'selected' : '' }}>Per-Device</option>
                                        <option value="user" {{ $licence->modele_licencing == 'user' ? 'selected' : '' }}>Per-User</option>
                                        <option value="concurrent" {{ $licence->modele_licencing == 'concurrent' ? 'selected' : '' }}>Concurrent</option>
                                        <option value="named" {{ $licence->modele_licencing == 'named' ? 'selected' : '' }}>Named</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Postes accordés</label>
                                    <input type="number" name="nombre_postes_accordes" class="form-control" value="{{ $licence->nombre_postes_accordes }}" disabled required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Date acquisition</label>
                                    <input type="date" name="date_acquisition" class="form-control" value="{{ $licence->date_acquisition->format('Y-m-d') }}" disabled required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Date expiration</label>
                                    <input type="date" name="date_expiration" class="form-control" value="{{ $licence->date_expiration->format('Y-m-d') }}" disabled required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Statut</label>
                                    <select name="statut" class="form-select" disabled required>
                                        <option value="actif" {{ $licence->statut == 'actif' ? 'selected' : '' }}>Actif</option>
                                        <option value="expire" {{ $licence->statut == 'expire' ? 'selected' : '' }}>Expiré</option>
                                        <option value="en_renouvellement" {{ $licence->statut == 'en_renouvellement' ? 'selected' : '' }}>Renouvellement</option>
                                        <option value="suspendu" {{ $licence->statut == 'suspendu' ? 'selected' : '' }}>Suspendu</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Fournisseur</label>
                                    <div class="input-group">
                                        <select name="fournisseur_id" id="select-fournisseur" class="form-select" disabled required>
                                            @foreach($fournisseurs as $f)
                                                <option value="{{ $f->id }}" {{ $licence->fournisseur_id == $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-primary d-none" id="btn-quickadd-fournisseur" title="Ajout rapide">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Coût Total</label>
                                    <div class="input-group">
                                        <input type="number" name="cout_total" class="form-control" value="{{ $licence->cout_total }}" step="0.01" disabled>
                                        <span class="input-group-text">EUR</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Notes / Conditions</label>
                                    <textarea name="notes" class="form-control" rows="3" disabled>{{ $licence->notes }}</textarea>
                                </div>
                                <input type="hidden" name="devise" value="EUR">
                            </div>
                        </form>
                    </div>

                    {{-- Tab Affectations --}}
                    <div class="tab-pane fade" id="tab-aff" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Historique des affectations</h6>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-affectation">
                                <i class="fas fa-plus me-1"></i> Nouvelle affectation
                            </button>
                        </div>
                        <div class="table-responsive text-center">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cible</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($licence->affectations as $aff)
                                    <tr>
                                        <td>
                                            @if($aff->employe)
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fas fa-user text-muted"></i>
                                                    <div class="text-start">
                                                        <div class="fw-bold">{{ $aff->employe->nom }} {{ $aff->employe->prenom }}</div>
                                                        <div class="small text-muted">{{ $aff->employe->dossier_employe_id }}</div>
                                                    </div>
                                                </div>
                                            @elseif($aff->equipement)
                                                <div class="d-flex align-items-center gap-2 text-center">
                                                    <i class="fas fa-desktop text-muted"></i>
                                                    <div class="text-start">
                                                        <div class="fw-bold">{{ $aff->equipement->code_inventaire }}</div>
                                                        <div class="small text-muted">{{ $aff->equipement->modele }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ ucfirst($aff->type_affectation) }}</span></td>
                                        <td>{{ $aff->date_affectation->format('d/m/Y') }}</td>
                                        <td><span class="badge bg-{{ $aff->actif ? 'success' : 'secondary' }}">{{ $aff->actif ? 'Actif' : 'Terminé' }}</span></td>
                                        <td class="text-end">
                                            @if($aff->actif)
                                                <button class="btn btn-sm btn-link text-danger p-0 btn-desaffecter" data-id="{{ $aff->id }}"><i class="fas fa-times-circle"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="py-4 text-muted">Aucune affectation.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab Documents --}}
                    <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                        <div class="text-center py-5 opacity-50">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>La gestion documentaire sera disponible prochainement.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar Info ── --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold">Support & Contact</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="small text-muted text-uppercase fw-semibold mb-2">Fournisseur</div>
                    <div class="fw-bold fs-5 text-dark">{{ $licence->fournisseur->nom }}</div>
                    @if($licence->fournisseur->email)
                        <div class="small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $licence->fournisseur->email }}</div>
                    @endif
                </div>
                @if($licence->contactSupport)
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold mb-2">Support Dédié</div>
                        <div class="fw-bold">{{ $licence->contactSupport->nom }} {{ $licence->contactSupport->prenom }}</div>
                        <div class="small"><i class="fas fa-phone me-2 text-muted"></i>{{ $licence->contactSupport->telephone }}</div>
                        <div class="small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $licence->contactSupport->email }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Contrat Maintenance</h6>
                @if($licence->contratMaintenance)
                    <span class="badge bg-success">ACTIF</span>
                @endif
            </div>
            <div class="card-body">
                @if($licence->contratMaintenance)
                    <div class="fw-bold">{{ $licence->contratMaintenance->nom }}</div>
                    <div class="small text-muted">Référence : {{ $licence->contratMaintenance->reference }}</div>
                    <div class="mt-3 small">Expire le : <strong>{{ $licence->contratMaintenance->date_fin?->format('d/m/Y') ?: 'Indéfini' }}</strong></div>
                @else
                    <p class="small text-muted mb-0">Aucun contrat de maintenance rattaché à cette licence.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Modales ── --}}
<div class="modal fade" id="modal-affectation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form id="form-affecter-licence">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Affecter cette licence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Type de cible</label>
                        <select name="type_affectation" id="type_affectation" class="form-select" required>
                            <option value="user">Utilisateur (Employé)</option>
                            <option value="device">Équipement (Poste)</option>
                        </select>
                    </div>
                    <div id="div-employe" class="mb-3">
                        <label class="form-label fw-bold small">Sélectionner l'employé</label>
                        <select name="employe_id" class="form-select select2-ajax-employes" style="width: 100%"></select>
                    </div>
                    <div id="div-equipement" class="mb-3 d-none">
                        <label class="form-label fw-bold small">Sélectionner l'équipement</label>
                        <select name="equipement_id" class="form-select select2-ajax-postes" style="width: 100%"></select>
                    </div>
                    <div class="alert alert-info border-0 small mb-0">
                        Il reste <strong>{{ $licence->disponibilites }}</strong> postes disponibles.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Valider l'affectation</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('parcinfo::shared._modal_fournisseur')
@endsection

@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/licences/show.js') }}?v={{ time() }}"></script>
@endpush
