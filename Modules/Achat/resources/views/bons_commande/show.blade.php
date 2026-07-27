@extends('achat::layouts.master')

@section('title', "Bon de commande {$bonCommande->numero_commande} - Achat")
@section('header', "Bon de commande {$bonCommande->numero_commande}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de commande</a></li>
    <li class="breadcrumb-item active">{{ $bonCommande->numero_commande }}</li>
@endsection

@section('content')

{{-- ── BANDEAU ─────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h4 class="mb-0 fw-bold text-dark">{{ $bonCommande->numero_commande }}</h4>
                    <x-achat-badge-statut :statut="$bonCommande->statut" type="bc" />
                </div>
                <div class="text-muted small">
                    <span class="me-3">
                        <i class="fas fa-truck me-1"></i>Fournisseur :
                        <strong class="text-dark">{{ $bonCommande->fournisseur?->nom ?? '-' }}</strong>
                    </span>
                    <span class="me-3">
                        <i class="fas fa-calendar-alt me-1"></i>Date :
                        <strong class="text-dark">{{ $bonCommande->date_commande?->format('d/m/Y') }}</strong>
                    </span>
                    <span>
                        <i class="fas fa-coins me-1"></i>Montant TTC :
                        <strong class="text-primary">{{ number_format($bonCommande->montant_ttc, 0, ',', ' ') }} FCFA</strong>
                    </span>
                </div>
            </div>

            {{-- Actions contextuelles : ENF-ERG-07, l'indisponibilité s'explique. --}}
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-sm btn-outline-secondary rounded-1 px-3">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>

                @if($bonCommande->estModifiable())
                    @can('achat.bons_commande.edit')
                    <a href="{{ route('achat.bons-commande.edit', $bonCommande) }}"
                       class="btn btn-sm btn-outline-primary rounded-1 px-3">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </a>
                    @endcan
                @endif

                @if($bonCommande->estValidable())
                    @can('achat.bons_commande.valider')
                    <button type="button" id="btn-valider" class="btn btn-sm btn-success text-white rounded-1 px-3">
                        <i class="fas fa-check me-1"></i>Valider la commande
                    </button>
                    @endcan
                @endif

                @if($bonCommande->accepteLivraison())
                    @can('achat.bordereaux.create')
                    <a href="{{ route('achat.bordereaux.create', ['bon_de_commande_id' => $bonCommande->id]) }}"
                       class="btn btn-sm btn-success text-white rounded-1 px-3">
                        <i class="fas fa-plus me-1"></i>Enregistrer une réception
                    </a>
                    @endcan
                @endif

                @if($bonCommande->estAnnulable())
                    @can('achat.bons_commande.annuler')
                    <button type="button" id="btn-annuler" class="btn btn-sm btn-warning text-dark rounded-1 px-3">
                        <i class="fas fa-ban me-1"></i>Annuler
                    </button>
                    @endcan
                @endif

                @if($bonCommande->estCloturable())
                    @can('achat.bons_commande.cloturer')
                    <button type="button" id="btn-cloturer" class="btn btn-sm btn-dark rounded-1 px-3"
                            data-bs-toggle="tooltip"
                            title="Solder le reliquat sans annuler les livraisons déjà intégrées">
                        <i class="fas fa-lock me-1"></i>Clôturer le reliquat
                    </button>
                    @endcan
                @endif

                <button type="button" id="btn-imprimer" class="btn btn-sm btn-info text-white rounded-1 px-3">
                    <i class="fas fa-print me-1"></i>Imprimer
                </button>
            </div>
        </div>

        @if($bonCommande->statut === 'annule' && $bonCommande->motif_annulation)
            <div class="alert alert-danger border-0 rounded-1 mt-3 mb-0 py-2 px-3 small">
                <strong>Motif d'annulation :</strong> {{ $bonCommande->motif_annulation }}
                <span class="text-muted">
                    &mdash; {{ $bonCommande->annulateur?->name ?? 'Système' }},
                    le {{ $bonCommande->date_annulation?->format('d/m/Y à H:i') }}
                </span>
            </div>
        @endif

        @if($bonCommande->statut === 'cloture' && $bonCommande->motif_cloture)
            <div class="alert alert-dark border-0 rounded-1 mt-3 mb-0 py-2 px-3 small">
                <strong>Reliquat clôturé :</strong> {{ $bonCommande->motif_cloture }}
                <span class="text-muted">
                    &mdash; le {{ $bonCommande->date_cloture?->format('d/m/Y à H:i') }}
                </span>
            </div>
        @endif
    </div>
</div>

{{-- ── ONGLETS ─────────────────────────────────────────────────────────── --}}
<ul class="nav nav-tabs border-bottom-0 bg-white px-3 pt-2 rounded-top" id="bc-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-fiche" type="button" role="tab">
            <i class="fas fa-file-invoice me-1"></i>Fiche
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-bordereaux" type="button" role="tab">
            <i class="fas fa-truck me-1"></i>Réceptions
            <span class="badge bg-light text-dark border ms-1">{{ $bonCommande->bordereauxLivraison->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-equipements" type="button" role="tab">
            <i class="fas fa-laptop me-1"></i>Matériel intégré
            <span class="badge bg-light text-dark border ms-1">{{ count($equipements) }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-documents" type="button" role="tab">
            <i class="fas fa-paperclip me-1"></i>Documents
            <span class="badge bg-light text-dark border ms-1" id="compteur-documents">{{ $bonCommande->documents->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-journal" type="button" role="tab">
            <i class="fas fa-history me-1"></i>Journal
        </button>
    </li>
</ul>

<div class="tab-content bg-white p-4 border rounded-bottom">

    {{-- ── FICHE ───────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade show active" id="onglet-fiche" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-1 rounded-1 h-100">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-info-circle me-1"></i>Informations générales</h6>
                    </div>
                    <div class="card-body p-3">
                        <table class="table table-sm table-borderless small mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">N° commande</td>
                                <td class="fw-bold text-dark font-monospace">{{ $bonCommande->numero_commande }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Fournisseur</td>
                                <td class="fw-semibold text-dark">
                                    {{ $bonCommande->fournisseur?->nom }}
                                    <span class="text-muted">({{ $bonCommande->fournisseur?->code }})</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date de commande</td>
                                <td>{{ $bonCommande->date_commande?->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Statut</td>
                                <td><x-achat-badge-statut :statut="$bonCommande->statut" type="bc" /></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Validé par</td>
                                <td>{{ $bonCommande->validateur?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date de validation</td>
                                <td>{{ $bonCommande->date_validation?->format('d/m/Y à H:i') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Avancement</td>
                                <td>
                                    <span class="fw-semibold">{{ $bonCommande->quantite_livree }}</span>
                                    <span class="text-muted">/ {{ $bonCommande->quantite_totale }} unité(s)</span>
                                </td>
                            </tr>
                            <tr class="border-top">
                                <td colspan="2" class="pt-2">
                                    <div class="small fw-semibold text-muted mb-1">Observations</div>
                                    <div class="bg-light p-2 rounded small text-dark" style="min-height: 48px;">
                                        {{ $bonCommande->commentaire ?: 'Aucune observation.' }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-1 rounded-1 h-100">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-list me-1"></i>Lignes de commande</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Article</th>
                                        <th>Type</th>
                                        <th class="text-center">Qté</th>
                                        <th class="text-end">PU HT</th>
                                        <th class="text-center">TVA</th>
                                        <th class="text-end">Montant HT</th>
                                        <th class="text-center">Livré</th>
                                        <th class="text-center">Reste</th>
                                        <th class="text-center">État</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bonCommande->lignesCommande as $ligne)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $ligne->article?->designation }}</div>
                                                <div class="small text-muted font-monospace">{{ $ligne->article?->code_article }}</div>
                                            </td>
                                            <td><x-achat-badge-type :type="$ligne->article?->type_article" /></td>
                                            <td class="text-center">{{ $ligne->quantite }}</td>
                                            <td class="text-end font-monospace">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                                            <td class="text-center text-muted">{{ rtrim(rtrim(number_format($ligne->taux_tva, 2, ',', ''), '0'), ',') }} %</td>
                                            <td class="text-end font-monospace fw-semibold">{{ number_format($ligne->montant_ht, 0, ',', ' ') }}</td>
                                            <td class="text-center fw-semibold text-primary">{{ $ligne->quantite_livree }}</td>
                                            <td class="text-center fw-semibold {{ $ligne->reste_a_livrer > 0 ? 'text-danger' : 'text-muted' }}">
                                                {{ $ligne->reste_a_livrer }}
                                            </td>
                                            <td class="text-center">
                                                @switch($ligne->etat_livraison)
                                                    @case('livre')
                                                        <span class="badge bg-success">Livré</span>
                                                        @break
                                                    @case('partiel')
                                                        <span class="badge bg-warning text-dark">Partiel</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">En attente</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 p-3">
                        {{-- ENF-FIA-04 : montants issus du service, jamais recalculés en vue. --}}
                        <div class="row g-2 justify-content-end text-end" style="font-size: 0.9rem;">
                            <div class="col-md-5 offset-md-7">
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">Total HT</span>
                                    <span class="fw-semibold text-dark">{{ number_format($bonCommande->montant_ht, 0, ',', ' ') }} FCFA</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">TVA</span>
                                    <span class="fw-semibold text-dark">{{ number_format($bonCommande->montant_tva, 0, ',', ' ') }} FCFA</span>
                                </div>
                                <div class="d-flex justify-content-between py-2">
                                    <span class="fw-bold text-dark">Montant TTC</span>
                                    <span class="fw-bold text-primary fs-5">{{ number_format($bonCommande->montant_ttc, 0, ',', ' ') }} FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── RÉCEPTIONS ──────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="onglet-bordereaux" role="tabpanel">
        <div class="row g-3">
            @forelse($bonCommande->bordereauxLivraison as $bordereau)
                <div class="col-md-4">
                    <div class="card border-1 rounded-1 h-100">
                        <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark font-monospace small">
                                <i class="fas fa-file-invoice me-1 text-success"></i>{{ $bordereau->numero_livraison }}
                            </span>
                            <x-achat-badge-statut :statut="$bordereau->statut" type="bl" />
                        </div>
                        <div class="card-body p-3 small">
                            <div class="mb-2"><strong>Réf. physique :</strong> {{ $bordereau->ref_bordereau_physique }}</div>
                            <div class="mb-2"><strong>Date :</strong> {{ $bordereau->date_livraison?->format('d/m/Y') }}</div>
                            <div class="mb-3">
                                <strong>Unités reçues :</strong>
                                <span class="badge bg-primary">{{ $bordereau->lignesLivraison->sum('quantite_livree') }}</span>
                            </div>
                            <a href="{{ route('achat.bordereaux.show', $bordereau) }}"
                               class="btn btn-xs btn-outline-success w-100 rounded-1">
                                Consulter <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <x-achat-etat-vide
                        icone="fa-truck-loading"
                        message="Aucune réception n'a encore été enregistrée pour ce bon de commande." />
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── MATÉRIEL INTÉGRÉ ────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="onglet-equipements" role="tabpanel">
        @include('achat::shared._equipements', ['equipements' => $equipements])
    </div>

    {{-- ── DOCUMENTS ───────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="onglet-documents" role="tabpanel">
        @include('achat::shared._documents', ['porteur' => $bonCommande, 'type' => 'bon_commande'])
    </div>

    {{-- ── JOURNAL ─────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="onglet-journal" role="tabpanel">
        @include('achat::shared._journal', ['journal' => $journal])
    </div>
</div>

{{-- ── PIED ────────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mt-3 bg-light">
    <div class="card-body py-2 px-3" style="font-size: 0.8rem;">
        <div class="row g-2 text-muted">
            <div class="col-md-4">
                <i class="fas fa-user-edit me-1"></i><strong>Créé par :</strong>
                {{ $bonCommande->creator?->name ?? 'Système' }}
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-clock me-1"></i><strong>Le :</strong>
                {{ $bonCommande->created_at->format('d/m/Y à H:i') }}
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-sync me-1"></i><strong>Dernière modification :</strong>
                {{ $bonCommande->updated_at->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</div>

@include('achat::shared._modal_pdf', ['id' => 'modal-pdf', 'titre' => 'Impression du bon de commande'])

@endsection

@push('js')
<script>
    window.achatFicheBc = {
        id: {{ $bonCommande->id }},
        numero: @json($bonCommande->numero_commande),
        resteALivrer: {{ $bonCommande->reste_a_livrer }},
        urls: {
            valider: @json(route('achat.bons-commande.valider', $bonCommande)),
            annuler: @json(route('achat.bons-commande.annuler', $bonCommande)),
            cloturer: @json(route('achat.bons-commande.cloturer', $bonCommande)),
            imprimer: @json(route('achat.bons-commande.imprimer', $bonCommande).'?pdf=1'),
            documents: @json(route('achat.documents.store')),
        },
    };
</script>
<script src="{{ asset('js/modules/achat/documents.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/achat/bons-commande/show.js') }}?v={{ time() }}"></script>
@endpush
