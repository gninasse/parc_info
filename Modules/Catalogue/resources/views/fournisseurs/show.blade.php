@extends('core::layouts.master')

@section('header', 'Fiche fournisseur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Catalogue</li>
    <li class="breadcrumb-item"><a href="{{ route('catalogue.fournisseurs.index') }}">Fournisseurs</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $fournisseur->raison_sociale }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div id="fiche-fournisseur"
     data-id="{{ $fournisseur->id }}"
     data-raison-sociale="{{ $fournisseur->raison_sociale }}"
     data-est-actif="{{ $fournisseur->est_actif ? 1 : 0 }}">

    {{-- En-tête --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                        <i class="fas fa-truck fa-lg text-primary"></i>
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1">{{ $fournisseur->raison_sociale }}</h4>
                    <span class="badge bg-secondary me-1">{{ $fournisseur->code }}</span>
                    <span class="badge {{ $fournisseur->est_actif ? 'bg-success' : 'bg-danger' }}" id="badge-statut">
                        {{ $fournisseur->est_actif ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
                <div class="col-auto">
                    @can('catalogue.fournisseurs.update')
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-edit" data-bs-toggle="tooltip" title="Modifier les informations">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </button>
                    @endcan
                    @can('catalogue.fournisseurs.toggle-status')
                    <button type="button" class="btn btn-outline-warning btn-sm" id="btn-toggle" data-bs-toggle="tooltip" title="Activer/Désactiver">
                        <i class="fas fa-power-off me-1"></i>{{ $fournisseur->est_actif ? 'Désactiver' : 'Activer' }}
                    </button>
                    @endcan
                    @can('catalogue.fournisseurs.destroy')
                        @if($motifsBlocage !== [])
                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip"
                              title="Suppression impossible : {{ implode(' ; ', $motifsBlocage) }}">
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btn-delete" disabled>
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        </span>
                        @else
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-delete" data-bs-toggle="tooltip" title="Supprimer ce fournisseur">
                            <i class="fas fa-trash me-1"></i>Supprimer
                        </button>
                        @endif
                    @endcan
                    <a href="{{ route('catalogue.fournisseurs.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Onglets ─────────────────────────────────────────────────────────
         Même structure que la fiche du bon de commande (Achat A-04) : des
         boutons (et non des liens) porteurs de data-bs-toggle, avec les
         attributs ARIA, et un compteur en pastille sur les onglets qui
         listent des éléments. --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#onglet-informations"
                            type="button" role="tab" aria-controls="onglet-informations" aria-selected="true">
                        <i class="fas fa-circle-info me-1"></i>Informations
                    </button>
                </li>
                @can('catalogue.contacts.index')
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-contacts"
                            type="button" role="tab" aria-controls="onglet-contacts" aria-selected="false">
                        <i class="fas fa-address-book me-1"></i>Contacts
                        <span class="badge bg-secondary-subtle text-secondary-emphasis" id="compteur-contacts">{{ $nbContacts }}</span>
                    </button>
                </li>
                @endcan
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-articles"
                            type="button" role="tab" aria-controls="onglet-articles" aria-selected="false">
                        <i class="fas fa-boxes-stacked me-1"></i>Articles
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $fournisseur->nb_articles }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-journal"
                            type="button" role="tab" aria-controls="onglet-journal" aria-selected="false">
                        <i class="fas fa-clock-rotate-left me-1"></i>Journal
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $activites->count() }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">

                {{-- ── Onglet Informations ─────────────────────────────── --}}
                <div class="tab-pane fade show active" id="onglet-informations" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold">Personne à contacter</div>
                            <div>{{ $fournisseur->contact ?: '—' }}</div>
                            {{-- Ce champ est l'interlocuteur « historique », saisi
                                 en texte libre sur la fiche du fournisseur. Le
                                 carnet complet, lui, vit dans l'onglet Contacts :
                                 on le dit, sinon les deux se confondent. --}}
                            @can('catalogue.contacts.index')
                            <div class="form-text">
                                Carnet complet dans l'onglet <a href="#" class="lien-onglet-contacts">Contacts</a>.
                            </div>
                            @endcan
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold">Téléphone</div>
                            <div>
                                @if($fournisseur->telephone)
                                    <a href="tel:{{ $fournisseur->telephone }}">{{ $fournisseur->telephone }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold">Email</div>
                            <div>
                                @if($fournisseur->email)
                                    <a href="mailto:{{ $fournisseur->email }}">{{ $fournisseur->email }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold">Adresse</div>
                            <div>{{ $fournisseur->adresse ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold">Ajouté le</div>
                            <div>{{ $fournisseur->created_at?->format('d/m/Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold">Articles au catalogue</div>
                            <div>{{ $fournisseur->nb_articles }}</div>
                        </div>
                        @if($fournisseur->notes)
                        <div class="col-12">
                            <div class="small text-uppercase text-muted fw-semibold">Notes</div>
                            <div class="text-muted">{{ $fournisseur->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ── Onglet Contacts ─────────────────────────────────── --}}
                @can('catalogue.contacts.index')
                <div class="tab-pane fade" id="onglet-contacts" role="tabpanel">

                    {{-- Toolbar + sélection de ligne : convention du projet,
                         jamais de colonne d'actions par ligne. --}}
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @can('catalogue.contacts.store')
                        <button type="button" class="btn btn-primary btn-sm" id="btn-contact-add">
                            <i class="fas fa-plus me-1"></i>Ajouter
                        </button>
                        @endcan
                        @can('catalogue.contacts.update')
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-contact-edit" disabled>
                            <i class="fas fa-edit me-1"></i>Modifier
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btn-contact-principal" disabled
                                data-bs-toggle="tooltip" title="Désigner comme interlocuteur principal">
                            <i class="fas fa-star me-1"></i>Définir principal
                        </button>
                        @endcan
                        @can('catalogue.contacts.destroy')
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-contact-delete" disabled>
                            <i class="fas fa-trash me-1"></i>Supprimer
                        </button>
                        @endcan
                    </div>

                    <table id="contacts-table"
                           data-toggle="table"
                           data-url="{{ route('catalogue.contacts.index', $fournisseur->id) }}"
                           data-click-to-select="true"
                           data-single-select="true"
                           data-locale="fr-FR"
                           class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th data-field="state" data-checkbox="true"></th>
                                <th data-field="nom_complet" data-sortable="true">Nom</th>
                                <th data-field="fonction">Fonction</th>
                                <th data-field="telephone" data-formatter="telephoneFormatter">Téléphone</th>
                                <th data-field="email" data-formatter="emailFormatter">Email</th>
                                <th data-field="est_principal" data-formatter="principalFormatter" data-align="center">Principal</th>
                                <th data-field="est_actif" data-formatter="statutFormatter" data-align="center">Statut</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                @endcan

                {{-- ── Onglet Articles ─────────────────────────────────── --}}
                <div class="tab-pane fade" id="onglet-articles" role="tabpanel">
                    <div class="d-flex justify-content-end mb-2">
                        <a href="{{ route('catalogue.articles.index', ['fournisseur_id' => $fournisseur->id]) }}" class="small">
                            Voir tous les articles <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <table id="articles-table"
                           data-toggle="table"
                           data-url="{{ route('catalogue.articles.data', ['fournisseur_id' => $fournisseur->id]) }}"
                           data-pagination="true"
                           data-side-pagination="server"
                           data-page-size="10"
                           data-page-list="[10, 25, 50]"
                           data-locale="fr-FR"
                           class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th data-field="code" data-sortable="true">Code</th>
                                <th data-field="nom" data-sortable="true">Désignation</th>
                                <th data-field="nature_label">Nature</th>
                                <th data-field="prix_indicatif" data-sortable="true" data-formatter="fcfaFormatter" data-align="end">Prix indicatif</th>
                                <th data-field="est_actif" data-formatter="statutFormatter" data-align="center">Statut</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ── Onglet Journal ──────────────────────────────────── --}}
                <div class="tab-pane fade" id="onglet-journal" role="tabpanel">
                    @forelse($activites as $activite)
                        @php
                            $evenements = ['created' => ['Création', 'success'], 'updated' => ['Modification', 'info'], 'deleted' => ['Suppression', 'danger']];
                            [$libelle, $couleur] = $evenements[$activite->event] ?? [ucfirst((string) $activite->event), 'secondary'];
                        @endphp
                        <div class="d-flex mb-3 {{ $loop->last ? '' : 'border-bottom pb-3' }}">
                            <div class="me-3">
                                <span class="badge bg-{{ $couleur }}">{{ $libelle }}</span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small">
                                    @if(($modifies = array_keys($activite->changes()['attributes'] ?? [])) !== [])
                                        Champs : {{ implode(', ', $modifies) }}
                                    @else
                                        {{ $activite->description }}
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    {{ $activite->causer?->name ?? 'Système' }} — {{ $activite->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>Aucune activité enregistrée.</p>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</div>

@include('catalogue::fournisseurs._modal')
@can('catalogue.contacts.index')
    @include('catalogue::fournisseurs._modal_contact')
@endcan
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/catalogue/fournisseurs/show.js') }}?v={{ time() }}"></script>
@endpush
