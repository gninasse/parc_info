@extends('achat::layouts.master')

@section('header', 'Bon de commande — '.$bon->numero_affiche)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de commande</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $bon->numero_affiche }}</li>
@endsection

@push('css')
<style>
    /* Régularisation : orange hachuré, la signature visuelle de l'intérim
       (SPEC_UX §0.2), identique à la liste et au tableau de bord. */
    .pictogramme-regularisation {
        background-image: repeating-linear-gradient(45deg, #fd7e14, #fd7e14 3px, #ffe5d0 3px, #ffe5d0 6px);
        border-radius: 3px;
        display: inline-block;
        height: .85rem;
        width: .85rem;
        vertical-align: -1px;
    }

    .barre-ligne { height: 6px; min-width: 90px; }

    /* Chronologie (maquette P-04) : puces colorées reliées d'un fil. */
    .chrono-item { position: relative; padding-left: 3rem; padding-bottom: 1.25rem; }
    .chrono-puce {
        position: absolute; left: 0; top: 0;
        width: 2rem; height: 2rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
    }
    .chrono-fil {
        position: absolute; left: 1rem; top: 2rem; bottom: 0;
        width: 2px; background: var(--bs-border-color);
        transform: translateX(-50%);
    }

    /* Mobile : la barre d'actions devient collante en bas, boutons pleine
       largeur — LE parcours mobile de première classe (UX3-06). */
    @media (max-width: 767.98px) {
        #barre-actions {
            position: sticky; bottom: 0; z-index: 100;
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            box-shadow: 0 -4px 12px rgba(0,0,0,.06);
            padding: .5rem;
        }
        #barre-actions .btn { width: 100%; }
    }
</style>
@endpush

@section('content')

@php
    $couleurStatut = $bon->statut_couleur === 'orange' ? 'warning text-dark' : $bon->statut_couleur;
    $lignesLivraison = $bon->estEngage();
@endphp

{{-- ── Bandeau d'état (toujours visible, SPEC_UX A-04) ─────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($bon->est_regularisation)
                        <span class="pictogramme-regularisation" title="Bon de régularisation (période d'intérim)" data-bs-toggle="tooltip"></span>
                    @endif
                    <h5 class="mb-0 {{ $bon->numero ? 'font-monospace' : 'text-muted fst-italic' }}">{{ $bon->numero_affiche }}</h5>
                    <span class="badge bg-{{ $couleurStatut }} {{ $bon->statut === 'ANNULE' ? 'text-decoration-line-through' : '' }}">{{ $bon->statut_label }}</span>

                    {{-- Badges contextuels (UX4-03, UX4-07, §0.2) --}}
                    @if($bon->est_regularisation)
                        <span class="badge bg-warning text-dark">🔶 RÉGULARISATION</span>
                    @endif
                    @if($fournisseurRecent !== null)
                        <span class="badge bg-warning text-dark" data-bs-toggle="tooltip"
                              title="Créé au Catalogue le {{ \Illuminate\Support\Carbon::parse($fournisseurRecent['cree_le'])->format('d/m/Y') }}">
                            ⚠ Fournisseur créé il y a {{ $fournisseurRecent['anciennete_jours'] }} j{{ $fournisseurRecent['premier_bc'] ? ' — premier BC' : '' }}
                        </span>
                    @endif
                    @if($autoValidation)
                        <span class="badge bg-secondary" data-bs-toggle="tooltip"
                              title="UX4-07 : le rapport Signaux recense ces validations">
                            saisi et validé par la même personne
                        </span>
                    @endif
                </div>

                <div class="text-muted small mt-1">
                    <i class="bi bi-truck me-1"></i>
                    @if($bon->fournisseur !== null)
                        <a href="{{ route('catalogue.fournisseurs.show', $bon->fournisseur->id) }}">{{ $bon->fournisseur_libelle ?? $bon->fournisseur->raison_sociale }}</a>
                    @else
                        {{ $bon->fournisseur_libelle ?? '—' }}
                    @endif
                    <span class="mx-2">·</span>{{ $bon->date_document?->format('d/m/Y') ?? '—' }}
                    <span class="mx-2">·</span><i class="bi bi-building me-1"></i>{{ $bon->service_demandeur_libelle ?? $bon->serviceDemandeur?->libelle ?? '—' }}
                    @if($bon->reference_demande)
                        <span class="text-muted">(réf. {{ $bon->reference_demande }})</span>
                    @endif
                    <span class="mx-2">·</span>créé par {{ $bon->createur?->name ?? '—' }}
                    @if($bon->valide_le)
                        <span class="mx-2">·</span>validé le {{ $bon->valide_le->format('d/m/Y H:i') }} par {{ $bon->validateur?->name ?? '—' }}
                    @endif
                </div>

                @if($bon->statut === 'ANNULE' && $bon->motif_annulation)
                    <div class="alert alert-danger py-2 px-3 mt-2 mb-0 small">
                        <i class="bi bi-x-octagon me-1"></i>Annulé — motif : « {{ $bon->motif_annulation }} »
                    </div>
                @elseif($bon->statut === 'CLOTURE' && $bon->motif_cloture)
                    <div class="alert alert-dark py-2 px-3 mt-2 mb-0 small">
                        <i class="bi bi-lock me-1"></i>Reliquat clôturé — motif : « {{ $bon->motif_cloture }} »
                    </div>
                @elseif($bon->estRenvoye())
                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small">
                        <i class="bi bi-arrow-return-left me-1"></i>Renvoyé par le visa
                        le {{ $bon->renvoi_le->format('d/m/Y H:i') }} — motif : « {{ $bon->renvoi_motif }} »
                    </div>
                @endif
            </div>

            <div class="text-end">
                <div class="small text-uppercase text-muted">Montant TTC</div>
                <div class="fs-3 fw-bold text-primary text-nowrap">
                    {{ number_format((float) $bon->montant_ttc, 0, ',', ' ') }} <small class="text-muted fs-6">FCFA TTC</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Barre d'actions contextuelle (grille serveur, doctrine §0.3) ─────── --}}
<div id="barre-actions" class="d-flex flex-wrap gap-2 mb-3"
     data-bon="{{ json_encode(['id' => $bon->id, 'numero_affiche' => $bon->numero_affiche, 'nb_lignes' => $bon->lignes->count()]) }}">
    @foreach($actions as $action)
        @continue($action['cle'] === 'voir') {{-- on y est déjà --}}

        @php
            // Extraits en variables simples : un accès de tableau dans une
            // directive inline (@disabled) casse l'analyseur Blade.
            $actionActive = $action['actif'] && $action['url'] !== null;
        @endphp

        @if($action['cle'] === 'pdf')
            {{-- Impression : modale iframe (pattern du projet), jamais un onglet. --}}
            <button type="button" class="btn btn-sm {{ $action['classe'] }}" id="action-pdf"
                    data-url="{{ $action['url'] }}" title="{{ $action['titre'] }}">
                <i class="bi {{ $action['icone'] }} me-1"></i>{{ $action['libelle'] }}
            </button>
        @elseif($action['cle'] === 'soumettre')
            {{-- SW-01 vit sur le récapitulatif : la confirmation chiffrée
                 exige la lecture des lignes (SPEC_UX A-03 ②). --}}
            @if($actionActive)
                <a href="{{ $action['url'] }}" class="btn btn-sm {{ $action['classe'] }}" title="{{ $action['titre'] }}">
                    <i class="bi {{ $action['icone'] }} me-1"></i>{{ $action['libelle'] }}
                </a>
            @else
                <button type="button" class="btn btn-sm {{ $action['classe'] }}" disabled title="{{ $action['titre'] }}">
                    <i class="bi {{ $action['icone'] }} me-1"></i>{{ $action['libelle'] }}
                </button>
            @endif
        @elseif($action['methode'] === 'GET')
            @if($actionActive)
                <a href="{{ $action['url'] }}" class="btn btn-sm {{ $action['classe'] }}" title="{{ $action['titre'] }}">
                    <i class="bi {{ $action['icone'] }} me-1"></i>{{ $action['libelle'] }}
                </a>
            @else
                {{-- Grisé par l'état : le diagnostic en infobulle (§0.3). --}}
                <button type="button" class="btn btn-sm {{ $action['classe'] }}" disabled title="{{ $action['titre'] }}">
                    <i class="bi {{ $action['icone'] }} me-1"></i>{{ $action['libelle'] }}
                </button>
            @endif
        @else
            {{-- Commande POST/DELETE : le JS branche le Swal partagé. --}}
            <button type="button" class="btn btn-sm {{ $action['classe'] }}"
                    id="action-{{ $action['cle'] }}"
                    data-url="{{ $action['url'] }}"
                    @disabled(! $actionActive)
                    title="{{ $action['titre'] }}">
                <i class="bi {{ $action['icone'] }} me-1"></i>{{ $action['libelle'] }}
            </button>
        @endif
    @endforeach

    <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

{{-- ── Onglets (SPEC_UX A-04) — compteurs UX2-05 ───────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#onglet-lignes"
                        type="button" role="tab" aria-controls="onglet-lignes" aria-selected="true">
                    Lignes <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $bon->lignes->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-documents"
                        type="button" role="tab" aria-controls="onglet-documents" aria-selected="false">
                    Documents <span class="badge bg-secondary-subtle text-secondary-emphasis" id="compteur-documents">{{ $bon->documents->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-chronologie"
                        type="button" role="tab" aria-controls="onglet-chronologie" aria-selected="false">
                    Chronologie <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $chronologie->count() }}</span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            {{-- ── Onglet Lignes ───────────────────────────────────────── --}}
            <div class="tab-pane fade show active" id="onglet-lignes" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="table-lignes">
                        <thead class="table-light">
                            <tr>
                                <th>Nature</th>
                                <th>Article</th>
                                <th class="text-end">Qté commandée</th>
                                @if($lignesLivraison)
                                    {{-- Avant validation, il n'y a rien à livrer :
                                         les colonnes n'existent pas (SPEC_UX A-04). --}}
                                    <th class="text-end">Qté livrée</th>
                                    <th class="text-end">Reste</th>
                                    <th>Progression</th>
                                @endif
                                <th class="text-end">Prix figé HT</th>
                                <th class="text-center">TVA</th>
                                <th class="text-end">Montant HT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $qte = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ' '), '0'), ',');
                            @endphp
                            @forelse($bon->lignes as $ligne)
                                <tr>
                                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $ligne->nature }}</span></td>
                                    <td>{{ $ligne->designation }}</td>
                                    <td class="text-end">{{ $qte($ligne->quantite) }}</td>
                                    @if($lignesLivraison)
                                        <td class="text-end">{{ $qte($ligne->quantite_livree) }}</td>
                                        <td class="text-end {{ $ligne->reste > 0 ? 'fw-semibold' : 'text-muted' }}">{{ $qte($ligne->reste) }}</td>
                                        <td>
                                            @php $progression = $ligne->progression; @endphp
                                            <div class="progress barre-ligne" role="progressbar"
                                                 aria-label="Progression de livraison"
                                                 aria-valuenow="{{ $progression }}" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar {{ $progression >= 100 ? 'bg-success' : 'bg-warning' }}"
                                                     style="width: {{ min($progression, 100) }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $qte($ligne->quantite_livree) }}/{{ $qte($ligne->quantite) }}</small>
                                        </td>
                                    @endif
                                    <td class="text-end">{{ number_format((float) $ligne->prix_unitaire_ht, 0, ',', ' ') }}</td>
                                    <td class="text-center">{{ $qte($ligne->taux_tva) }} %</td>
                                    <td class="text-end">{{ number_format((float) $ligne->quantite * (float) $ligne->prix_unitaire_ht, 0, ',', ' ') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $lignesLivraison ? 9 : 6 }}" class="text-center text-muted py-4">Aucune ligne.</td></tr>
                            @endforelse
                        </tbody>
                        @if($bon->lignes->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="{{ $lignesLivraison ? 8 : 5 }}" class="text-end">Total HT</th>
                                    <th class="text-end">{{ number_format((float) $bon->montant_ht, 0, ',', ' ') }} <small class="text-muted">FCFA</small></th>
                                </tr>
                                <tr>
                                    <th colspan="{{ $lignesLivraison ? 8 : 5 }}" class="text-end fw-normal">TVA</th>
                                    <th class="text-end fw-normal">{{ number_format((float) $bon->montant_tva, 0, ',', ' ') }} <small class="text-muted">FCFA</small></th>
                                </tr>
                                <tr>
                                    <th colspan="{{ $lignesLivraison ? 8 : 5 }}" class="text-end">Total TTC</th>
                                    <th class="text-end text-primary">{{ number_format((float) $bon->montant_ttc, 0, ',', ' ') }} <small class="text-muted">FCFA TTC</small></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if(count($decomposition) > 1)
                    {{-- PO-02 : la décomposition n'apparaît que s'il y a
                         plusieurs taux — à taux unique, elle n'apprend rien. --}}
                    <div class="mt-3" id="decomposition-taux">
                        <h6 class="small text-uppercase text-muted">Décomposition par taux de TVA</h6>
                        <table class="table table-sm w-auto mb-0">
                            <thead>
                                <tr>
                                    <th>Taux</th>
                                    <th class="text-end">Base HT</th>
                                    <th class="text-end">TVA</th>
                                    <th class="text-end">TTC</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($decomposition as $tranche)
                                    <tr>
                                        <td>{{ $qte($tranche['taux']) }} %</td>
                                        <td class="text-end">{{ number_format($tranche['base_ht'], 0, ',', ' ') }}</td>
                                        <td class="text-end">{{ number_format($tranche['tva'], 0, ',', ' ') }}</td>
                                        <td class="text-end">{{ number_format($tranche['ttc'], 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($bon->observation_type || $bon->observation_texte)
                    <div class="alert alert-light border mt-3 mb-0">
                        @if($bon->observation_type)
                            <span class="badge bg-secondary-subtle text-secondary-emphasis me-1">{{ $bon->observation_type }}</span>
                        @endif
                        {{ $bon->observation_texte }}
                    </div>
                @endif
            </div>

            {{-- ── Onglet Documents (D-09 : M-05, M-08, pierres tombales) ── --}}
            <div class="tab-pane fade" id="onglet-documents" role="tabpanel"
                 data-url-liste="{{ route('achat.bons-commande.documents.index', $bon->id) }}"
                 data-url-depot="{{ route('achat.bons-commande.documents.store', $bon->id) }}">
                @can('achat.documents.store')
                    @if($bon->statut !== 'ANNULE')
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-ajouter-piece"
                                    data-bs-toggle="modal" data-bs-target="#modal-piece">
                                <i class="bi bi-paperclip me-1"></i>Ajouter une pièce
                            </button>
                        </div>
                    @endif
                @endcan

                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="table-documents">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Fichier</th>
                                <th>Déposé par</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>{{-- rempli par le JS depuis la charge serveur --}}</tbody>
                    </table>
                </div>

                {{-- EV-05 : l'état vide enseigne le geste. --}}
                <p class="text-center text-muted py-3 mb-0 d-none" id="documents-vide">
                    Aucune pièce au dossier. Le BC signé, le bordereau du fournisseur ou la
                    facture pro forma se déposent ici pour ne plus dormir dans un classeur.
                </p>
            </div>

            {{-- ── Onglet Chronologie (IA-14 : le journal, rien que lui) ── --}}
            <div class="tab-pane fade" id="onglet-chronologie" role="tabpanel">
                @forelse($chronologie as $element)
                    <div class="chrono-item">
                        <div class="chrono-puce bg-{{ $element['couleur'] }}">
                            <i class="bi {{ $element['icone'] }}"></i>
                        </div>
                        @unless($loop->last)
                            <div class="chrono-fil"></div>
                        @endunless
                        <div>
                            <div class="fw-semibold {{ $element['couleur'] === 'danger' ? 'text-danger' : '' }}">
                                {{ $element['phrase'] }}
                            </div>
                            @if($element['details'])
                                <div class="small">{{ $element['details'] }}</div>
                            @endif
                            <div class="small text-muted">
                                {{ $element['quand']->format('d/m/Y H:i') }}
                                @if($element['auteur']) — {{ $element['auteur'] }} @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted py-4 mb-0">Aucun événement au journal pour ce bon.</p>
                @endforelse
            </div>

        </div>
    </div>
</div>

@include('achat::shared._modal_pdf')

{{-- M-05 — dépôt d'une pièce (type + fichier, taille max paramétrée A-08) --}}
@can('achat.documents.store')
<div class="modal fade" id="modal-piece" tabindex="-1" aria-labelledby="modal-piece-titre" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-piece">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-piece-titre">Ajouter une pièce — {{ $bon->numero_affiche }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de pièce</label>
                        <div class="d-flex flex-wrap gap-2" id="piece-types">
                            @foreach(config('achat.types_documents') as $code => $libelle)
                                <input type="radio" class="btn-check" name="type" id="type-{{ $code }}"
                                       value="{{ $code }}" @checked($loop->first)>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill" for="type-{{ $code }}">{{ $libelle }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="piece-fichier">Fichier</label>
                        <input type="file" class="form-control" id="piece-fichier" name="fichier"
                               accept=".{{ implode(',.', config('achat.documents.extensions')) }}" required>
                        <div class="form-text">PDF, image ou document bureautique — {{ $tailleMaxPieceMo }} Mo maximum (paramètre de l'établissement).</div>
                    </div>
                    <div class="alert alert-danger d-none py-2 px-3 small" id="piece-erreur" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="piece-deposer">
                        <i class="bi bi-paperclip me-1"></i>Déposer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/achat/bons-commande/show.js') }}?v={{ time() }}"></script>
@endpush
