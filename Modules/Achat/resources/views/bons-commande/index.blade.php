@extends('achat::layouts.master')

@section('header', 'Bons de commande')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item active" aria-current="page">Bons de commande</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    /* Pilules de statut multi-sélection (SPEC_UX A-02) */
    .pilule-statut .btn { border-radius: 999px; }

    /* Régularisation : orange hachuré, la signature visuelle de l'intérim
       (SPEC_UX §0.2), identique au bandeau de la fiche et à la carte du
       tableau de bord. */
    .pictogramme-regularisation {
        background-image: repeating-linear-gradient(45deg, #fd7e14, #fd7e14 3px, #ffe5d0 3px, #ffe5d0 6px);
        border-radius: 3px;
        display: inline-block;
        height: .85rem;
        width: .85rem;
        vertical-align: -1px;
    }

    .barre-livraison { height: 6px; }
    #bons-commande-table td { vertical-align: middle; }
</style>
@endpush

@section('content')

{{-- ── Zone de filtres (SPEC_UX A-02) ──────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small text-uppercase text-muted" for="filter-recherche">Recherche</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="filter-recherche" class="form-control"
                           placeholder="BC-2026-0041, ENT-2026-0034, Brouillon #12, fournisseur, article…"
                           aria-describedby="recherche-aide">
                </div>
                <div id="recherche-aide" class="form-text small">Tous les formats de numéro sont acceptés.</div>
            </div>

            <div class="col-lg-3">
                <label class="form-label small text-uppercase text-muted" for="filter-fournisseur">Fournisseur</label>
                <select id="filter-fournisseur" class="form-select form-select-sm">
                    <option value="">Tous les fournisseurs</option>
                    @foreach($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->raison_sociale }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-4">
                <label class="form-label small text-uppercase text-muted" for="filter-du">Période (date du document)</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="filter-du" class="form-control" aria-label="Du">
                    <span class="input-group-text">→</span>
                    <input type="date" id="filter-au" class="form-control" aria-label="Au">
                </div>
            </div>

            <div class="col-lg-9">
                <label class="form-label small text-uppercase text-muted d-block">Statut</label>
                <div class="d-flex flex-wrap gap-2 pilule-statut" id="filter-statut">
                    @foreach($statuts as $code => $libelle)
                        <input type="checkbox" class="btn-check" name="statut" id="statut-{{ $code }}"
                               value="{{ $code }}" autocomplete="off"
                               @checked(in_array($code, $statutsPreselectionnes, true))>
                        <label class="btn btn-sm btn-outline-{{ $couleurs[$code] === 'orange' ? 'warning' : $couleurs[$code] }}"
                               for="statut-{{ $code }}">{{ $libelle }}</label>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="filter-regularisations"
                           @checked($regularisationsSeules)>
                    <label class="form-check-label small" for="filter-regularisations">
                        <span class="pictogramme-regularisation" aria-hidden="true"></span>
                        Régularisations uniquement
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="filter-mes-brouillons"
                           @checked($mesBrouillons)>
                    <label class="form-check-label small" for="filter-mes-brouillons">Mes brouillons</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-card-checklist me-2 text-primary"></i>Bons de commande
        </h6>
    </div>

    <div class="card-body p-0">
        {{-- Toolbar du tableau (pattern du projet : Core rôles, Stock entrées).
             Les boutons s'activent sur la SÉLECTION d'une ligne ; sans le
             droit, le bouton est ABSENT ; bloqué par l'état, il reste GRISÉ
             avec son diagnostic en infobulle (SPEC_UX §0.3). --}}
        <div id="toolbar">
            @can('achat.bons_commande.store')
                <a href="{{ route('achat.bons-commande.create') }}" id="btn-add" class="btn btn-primary btn-sm"
                   data-bs-toggle="tooltip" title="Nouveau bon de commande">
                    <i class="fas fa-plus"></i>
                </a>
            @endcan
            @can('achat.bons_commande.index')
                <button id="btn-show" class="btn btn-secondary btn-sm" disabled
                        data-bs-toggle="tooltip" title="Voir">
                    <i class="fas fa-eye"></i>
                </button>
            @endcan
            @can('achat.bons_commande.update')
                <button id="btn-edit" class="btn btn-info btn-sm" disabled
                        data-bs-toggle="tooltip" title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
            @endcan
            @can('achat.bons_commande.destroy')
                <button id="btn-delete" class="btn btn-danger btn-sm" disabled
                        data-bs-toggle="tooltip" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            @endcan
            @can('achat.bons_commande.soumettre')
                <button id="btn-soumettre" class="btn btn-warning btn-sm" disabled
                        data-bs-toggle="tooltip" title="Soumettre au visa">
                    <i class="bi bi-send"></i>
                </button>
                <button id="btn-reprendre" class="btn btn-outline-secondary btn-sm" disabled
                        data-bs-toggle="tooltip" title="Reprendre ma soumission">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            @endcan
            @can('achat.bons_commande.valider')
                <button id="btn-valider" class="btn btn-success btn-sm" disabled
                        data-bs-toggle="tooltip" title="Valider">
                    <i class="fas fa-check"></i>
                </button>
                <button id="btn-renvoyer" class="btn btn-outline-warning btn-sm" disabled
                        data-bs-toggle="tooltip" title="Renvoyer en brouillon">
                    <i class="bi bi-arrow-return-left"></i>
                </button>
            @endcan
            @can('achat.bons_commande.index')
                <button id="btn-imprimer" class="btn btn-outline-primary btn-sm" disabled
                        data-bs-toggle="tooltip" title="Imprimer">
                    <i class="bi bi-printer"></i>
                </button>
            @endcan
            {{-- D-23 : recommander à partir d'un bon existant (prix actualisés). --}}
            @can('achat.bons_commande.store')
                <button id="btn-dupliquer" class="btn btn-outline-secondary btn-sm" disabled
                        data-bs-toggle="tooltip" title="Dupliquer">
                    <i class="bi bi-files"></i>
                </button>
            @endcan
            {{-- Menu de régularisation : présent seulement si la porte
                 d'intérim est ouverte (A15) et si l'utilisateur en a le droit. --}}
            @can('achat.bons_commande.regulariser')
                @if($regularisationActive)
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Autres créations">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item"
                                   href="{{ route('achat.bons-commande.create', ['regularisation' => 1]) }}">
                                    <span class="pictogramme-regularisation me-1" aria-hidden="true"></span>
                                    Nouveau BC de régularisation
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif
            @endcan
        </div>

        <table id="bons-commande-table"
               data-toggle="table"
               data-url="{{ route('achat.bons-commande.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="10"
               data-locale="fr-FR"
               data-sort-name="id"
               data-sort-order="desc"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="numero_affiche" data-formatter="bcNumeroFormatter" data-sortable="true" data-sort-name="numero">Numéro</th>
                    <th data-field="fournisseur" data-sortable="false">Fournisseur</th>
                    <th data-field="date_document" data-sortable="true" data-sort-name="date_document">Date</th>
                    <th data-field="service_demandeur" data-visible="false">Service demandeur</th>
                    <th data-field="nb_lignes" data-sortable="true" data-sort-name="nb_lignes" data-align="center">Lignes</th>
                    <th data-field="montant_ttc" data-formatter="bcMontantTtcFormatter" data-sortable="true" data-sort-name="montant_ttc" data-align="end">Montant TTC</th>
                    <th data-field="progression" data-formatter="bcProgressionFormatter" data-align="center">Livraison</th>
                    <th data-field="statut" data-formatter="bcStatutFormatter" data-align="center">Statut</th>
                    <th data-field="cree_par" data-visible="false">Créé par</th>
                </tr>
            </thead>
        </table>

        {{-- Pied de tableau : compteur du FILTRE COURANT, pas de la page
             affichée (SPEC_UX §0.4 — tout montant est qualifié HT ou TTC). --}}
        <div class="border-top px-3 py-2 d-flex justify-content-between align-items-center bg-light-subtle">
            <span class="small text-muted" id="pied-compteur">—</span>
            <span class="small fw-semibold" id="pied-total">—</span>
        </div>
    </div>
</div>

{{-- EV-02 — état vide du filtre (SPEC_UX A-02), masqué par défaut --}}
<div id="etat-vide" class="text-center py-5 d-none">
    <i class="bi bi-inbox fs-1 text-muted"></i>
    <p class="mt-3 mb-3 text-muted">Aucun bon de commande ne correspond à ces filtres.</p>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reinitialiser">
        <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser les filtres
    </button>
    @can('achat.bons_commande.store')
        <a href="{{ route('achat.bons-commande.create') }}" class="btn btn-primary btn-sm ms-2">
            <i class="bi bi-plus-lg me-1"></i>Nouveau bon de commande
        </a>
    @endcan
</div>

@include('achat::shared._modal_pdf')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/achat/bons-commande/index.js') }}?v={{ time() }}"></script>
@endpush
