@extends('parcinfo::layouts.master')

@section('header', 'Fiche Logiciel')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.logiciels.index') }}">Logiciels</a></li>
    <li class="breadcrumb-item active">{{ $logiciel->code }}</li>
@endsection

@section('content')
<div class="row g-4">
    {{-- ── En-tête / Profil ── --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="d-flex flex-column flex-md-row">
                    <div class="bg-primary bg-opacity-10 p-4 d-flex align-items-center justify-content-center" style="min-width: 200px;">
                        <i class="fas fa-compact-disc fa-5x text-primary"></i>
                    </div>
                    <div class="p-4 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">{{ $logiciel->nom }}</h4>
                                <span class="badge bg-light text-primary border border-primary border-opacity-25 px-3 py-2">
                                    <i class="fas fa-barcode me-2"></i>{{ $logiciel->code }}
                                </span>
                                <span class="badge bg-{{ $logiciel->est_actif ? 'success' : 'danger' }} px-3 py-2 ms-2">
                                    {{ $logiciel->est_actif ? 'ACTIF' : 'INACTIF' }}
                                </span>
                            </div>
                            <div id="view-actions">
                                <button class="btn btn-primary px-4" id="btn-enable-edit">
                                    <i class="fas fa-edit me-2"></i>Modifier
                                </button>
                                <button class="btn btn-outline-{{ $logiciel->est_actif ? 'danger' : 'success' }} ms-2" id="btn-toggle-status">
                                    <i class="fas fa-power-off me-2"></i>{{ $logiciel->est_actif ? 'Désactiver' : 'Activer' }}
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
                            <div class="col-md-4">
                                <div class="small text-muted text-uppercase fw-semibold">Éditeur</div>
                                <div class="fw-bold">{{ $logiciel->editeur->nom }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted text-uppercase fw-semibold">Type Licence</div>
                                <div class="fw-bold">{{ $logiciel->typeLicence->libelle }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted text-uppercase fw-semibold">Catégorie</div>
                                <div class="fw-bold">{{ $logiciel->categorie ?: 'Non classé' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Détails & Licences ── --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <ul class="nav nav-tabs card-header-tabs" id="logicielTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#tab-infos" type="button">
                            <i class="fas fa-info-circle me-2"></i>Informations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="licences-tab" data-bs-toggle="tab" data-bs-target="#tab-licences" type="button">
                            <i class="fas fa-key me-2"></i>Licences rattachées ({{ $logiciel->licences->count() }})
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="logicielTabsContent">
                    {{-- Tab Infos --}}
                    <div class="tab-pane fade show active" id="tab-infos" role="tabpanel">
                        <form id="form-edit-logiciel">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Code Logiciel</label>
                                    <input type="text" name="code" class="form-control" value="{{ $logiciel->code }}" disabled required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nom complet</label>
                                    <input type="text" name="nom" class="form-control" value="{{ $logiciel->nom }}" disabled required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Éditeur</label>
                                    <div class="input-group">
                                        <select name="editeur_id" id="select-editeur" class="form-select" disabled required>
                                            @foreach($editeurs as $e)
                                                <option value="{{ $e->id }}" {{ $logiciel->editeur_id == $e->id ? 'selected' : '' }}>{{ $e->nom }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-primary d-none" id="btn-quickadd-editeur" title="Ajout rapide">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Type Licence</label>
                                    <select name="type_licence_id" class="form-select" disabled required>
                                        @foreach($typesLicences as $t)
                                            <option value="{{ $t->id }}" {{ $logiciel->type_licence_id == $t->id ? 'selected' : '' }}>{{ $t->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Catégorie</label>
                                    <input type="text" name="categorie" class="form-control" value="{{ $logiciel->categorie }}" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Description</label>
                                    <textarea name="description" class="form-control" rows="3" disabled>{{ $logiciel->description }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3" disabled>{{ $logiciel->notes }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tab Licences --}}
                    <div class="tab-pane fade" id="tab-licences" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Liste des Licences rattachées</h6>
                            <button class="btn btn-primary btn-sm" id="btn-add-licence">
                                <i class="fas fa-plus me-1"></i> Ajouter une licence
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Clé / Contrat</th>
                                        <th>Expiration</th>
                                        <th>Utilisation</th>
                                        <th>Statut</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logiciel->licences as $lic)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $lic->cle_licence ?: 'Licence sans clé' }}</div>
                                            <div class="small text-muted">{{ $lic->numero_contrat }}</div>
                                        </td>
                                        <td class="text-center">{{ $lic->date_expiration->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-2 justify-content-center">
                                                <span class="small fw-bold">{{ $lic->nombre_postes_utilises }} / {{ $lic->nombre_postes_accordes ?: '∞' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="badge bg-{{ $lic->statut === 'actif' ? 'success' : 'danger' }}">{{ ucfirst($lic->statut) }}</span></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('parc-info.licences.show', $lic->id) }}" class="btn btn-light border" title="Voir"><i class="fas fa-eye text-primary"></i></a>
                                                <button class="btn btn-light border btn-delete-licence" data-id="{{ $lic->id }}" title="Supprimer"><i class="fas fa-trash text-danger"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Aucune licence enregistrée pour ce logiciel.</td></tr>
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
                <h6 class="mb-0 fw-bold">Statistiques Globales</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3"><i class="fas fa-users text-info"></i></div>
                    <div>
                        <div class="small text-muted">Utilisateurs totaux</div>
                        <div class="fw-bold fs-5">{{ $logiciel->licences->sum('nombre_postes_utilises') }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3"><i class="fas fa-chart-pie text-warning"></i></div>
                    <div>
                        <div class="small text-muted">Taux d'utilisation</div>
                        <div class="fw-bold fs-5">{{ $logiciel->utilisation }}%</div>
                    </div>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $logiciel->utilisation }}%"></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fas fa-life-ring me-2"></i>Support Éditeur</h6>
                @if($logiciel->editeur->email_support || $logiciel->editeur->telephone_support)
                    <div class="mb-2"><i class="fas fa-envelope me-2"></i>{{ $logiciel->editeur->email_support ?: 'N/A' }}</div>
                    <div class="mb-2"><i class="fas fa-phone me-2"></i>{{ $logiciel->editeur->telephone_support ?: 'N/A' }}</div>
                    <div><i class="fas fa-globe me-2"></i><a href="{{ $logiciel->editeur->site_web }}" target="_blank" class="text-white text-decoration-none">{{ $logiciel->editeur->site_web ?: 'N/A' }}</a></div>
                @else
                    <p class="small mb-0 opacity-75">Aucune information de support renseignée pour cet éditeur.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@include('parcinfo::informatique.licences._modal')
@include('parcinfo::shared._modal_editeur')
@include('parcinfo::shared._modal_fournisseur')
@include('parcinfo::shared._modal_contrat_maintenance')
@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/parc-info/logiciels/show.js') }}?v={{ time() }}"></script>
@endpush
