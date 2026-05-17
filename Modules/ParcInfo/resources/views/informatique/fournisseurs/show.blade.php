@extends('parcinfo::layouts.master')

@section('header', 'Fiche Fournisseur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.fournisseurs.index') }}">Fournisseurs</a></li>
    <li class="breadcrumb-item active">{{ $fournisseur->code }}</li>
@endsection

@section('content')
<div class="row g-4">
    {{-- ── En-tête / Profil ── --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="d-flex flex-column flex-md-row">
                    <div class="bg-success bg-opacity-10 p-4 d-flex align-items-center justify-content-center" style="min-width: 200px;">
                        <i class="fas fa-truck fa-5x text-success"></i>
                    </div>
                    <div class="p-4 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">{{ $fournisseur->nom }}</h4>
                                <span class="badge bg-light text-success border border-success border-opacity-25 px-3 py-2">
                                    <i class="fas fa-barcode me-2"></i>{{ $fournisseur->code }}
                                </span>
                                <span class="badge bg-{{ $fournisseur->est_actif ? 'success' : 'danger' }} px-3 py-2 ms-2">
                                    {{ $fournisseur->est_actif ? 'ACTIF' : 'INACTIF' }}
                                </span>
                            </div>
                            <div id="view-actions">
                                <button class="btn btn-primary px-4" id="btn-enable-edit">
                                    <i class="fas fa-edit me-2"></i>Modifier
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
                                <div class="small text-muted text-uppercase fw-semibold">Type</div>
                                <div class="fw-bold">{{ $fournisseur->type ?: '—' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Email</div>
                                <div class="fw-bold">{{ $fournisseur->email ?: '—' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Téléphone</div>
                                <div class="fw-bold">{{ $fournisseur->telephone ?: '—' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Fiabilité</div>
                                <div class="fw-bold text-success">{{ $fournisseur->fiabilite_score }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Détails & Contacts ── --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 text-center">
                <ul class="nav nav-tabs card-header-tabs" id="fournisseurTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#tab-infos" type="button">
                            <i class="fas fa-info-circle me-2"></i>Informations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#tab-contacts" type="button">
                            <i class="fas fa-address-book me-2"></i>Contacts ({{ $fournisseur->contacts->count() }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="contrats-tab" data-bs-toggle="tab" data-bs-target="#tab-contrats" type="button">
                            <i class="fas fa-file-signature me-2"></i>Contrats ({{ $fournisseur->contrats->count() }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="licences-tab" data-bs-toggle="tab" data-bs-target="#tab-licences" type="button">
                            <i class="fas fa-key me-2"></i>Licences liées
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="fournisseurTabsContent">
                    {{-- Tab Infos --}}
                    <div class="tab-pane fade show active" id="tab-infos" role="tabpanel">
                        <form id="form-edit-fournisseur">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Code</label>
                                    <input type="text" name="code" class="form-control" value="{{ $fournisseur->code }}" disabled required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Nom Entreprise</label>
                                    <input type="text" name="nom" class="form-control" value="{{ $fournisseur->nom }}" disabled required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Type</label>
                                    <input type="text" name="type" class="form-control" value="{{ $fournisseur->type }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ $fournisseur->email }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Téléphone</label>
                                    <input type="text" name="telephone" class="form-control" value="{{ $fournisseur->telephone }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Score Fiabilité</label>
                                    <input type="number" name="fiabilite_score" class="form-control" value="{{ $fournisseur->fiabilite_score }}" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Adresse</label>
                                    <textarea name="adresse" class="form-control" rows="3" disabled>{{ $fournisseur->adresse }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tab Contacts --}}
                    <div class="tab-pane fade" id="tab-contacts" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Interlocuteurs chez ce fournisseur</h6>
                            <button class="btn btn-primary btn-sm" id="btn-add-contact">
                                <i class="fas fa-user-plus me-1"></i> Nouveau contact
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nom Complet</th>
                                        <th>Fonction</th>
                                        <th>Email / Tel</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fournisseur->contacts as $c)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $c->nom }} {{ $c->prenom }}</div>
                                        </td>
                                        <td>{{ $c->fonction ?: '—' }}</td>
                                        <td>
                                            <div class="small">{{ $c->email }}</div>
                                            <div class="small text-muted">{{ $c->telephone }}</div>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-link text-danger p-0 btn-delete-contact" data-id="{{ $c->id }}"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Aucun contact enregistré.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab Contrats --}}
                    <div class="tab-pane fade" id="tab-contrats" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Contrats de Maintenance</h6>
                            <button class="btn btn-primary btn-sm" id="btn-add-contrat">
                                <i class="fas fa-file-plus me-1"></i> Nouveau contrat
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Référence / Nom</th>
                                        <th>Période</th>
                                        <th class="text-end">Coût (EUR)</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fournisseur->contrats as $ct)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $ct->reference }}</div>
                                            <div class="small">{{ $ct->nom }}</div>
                                        </td>
                                        <td>
                                            <div class="small">Début: {{ $ct->date_debut?->format('d/m/Y') ?: '—' }}</div>
                                            <div class="small">Fin: {{ $ct->date_fin?->format('d/m/Y') ?: '—' }}</div>
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($ct->cout, 2, ',', ' ') }}</td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-light border btn-edit-contrat" data-id="{{ $ct->id }}" title="Modifier"><i class="fas fa-edit text-info"></i></button>
                                                <button class="btn btn-light border btn-delete-contrat" data-id="{{ $ct->id }}" title="Supprimer"><i class="fas fa-trash text-danger"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Aucun contrat enregistré.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab Licences --}}
                    <div class="tab-pane fade" id="tab-licences" role="tabpanel">
                        <div class="table-responsive text-center">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Logiciel</th>
                                        <th>Expiration</th>
                                        <th>Coût</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fournisseur->licences as $lic)
                                    <tr>
                                        <td>{{ $lic->logiciel->nom }}</td>
                                        <td>{{ $lic->date_expiration->format('d/m/Y') }}</td>
                                        <td>{{ number_format($lic->cout_total, 2) }} {{ $lic->devise }}</td>
                                        <td><a href="{{ route('parc-info.licences.show', $lic->id) }}" class="btn btn-sm btn-light border"><i class="fas fa-eye"></i></a></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="py-4 text-muted text-center">Aucune licence.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
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
                <h6 class="mb-0 fw-bold">Conditions & Délais</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small fw-bold text-uppercase d-block">Conditions Paiement</label>
                    <div>{{ $fournisseur->conditions_paiement ?: 'Non défini' }}</div>
                </div>
                <div class="mb-0">
                    <label class="text-muted small fw-bold text-uppercase d-block">Délai Livraison Moyen</label>
                    <div>{{ $fournisseur->delai_livraison ?: 'Non défini' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('parcinfo::shared._modal_contact')
@include('parcinfo::shared._modal_contrat_maintenance')

@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/parc-info/fournisseurs/show.js') }}?v={{ time() }}"></script>
@endpush
