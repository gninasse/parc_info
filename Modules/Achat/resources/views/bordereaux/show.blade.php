@extends('achat::layouts.master')

@section('title', "Bordereau {$bordereau->numero_livraison} - Achat")
@section('header', "Bordereau de livraison {$bordereau->numero_livraison}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.index') }}">Bordereaux de livraison</a></li>
    <li class="breadcrumb-item active">{{ $bordereau->numero_livraison }}</li>
@endsection

@section('content')

{{-- ── BARRE D'ACTIONS ─────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="small text-muted me-2">Statut :</span>
            <x-achat-badge-statut :statut="$bordereau->statut" type="bl" />
            @if($bordereau->estValide())
                <span class="small text-muted ms-2">
                    Intégré au parc le {{ $bordereau->date_validation?->format('d/m/Y à H:i') }}
                    par {{ $bordereau->validateur?->name ?? 'Système' }}
                </span>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-sm btn-outline-secondary rounded-1">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>

            <button type="button" id="btn-imprimer" class="btn btn-sm btn-outline-secondary rounded-1">
                <i class="fas fa-print me-1"></i>Imprimer
            </button>

            @if($bordereau->estModifiable())
                @can('achat.bordereaux.edit')
                <button type="button" id="btn-modifier" class="btn btn-sm btn-outline-primary rounded-1">
                    <i class="fas fa-edit me-1"></i>Modifier
                </button>
                @endcan
                @can('achat.bordereaux.valider')
                <a href="{{ route('achat.bordereaux.wizard', $bordereau) }}"
                   class="btn btn-sm btn-success text-white rounded-1">
                    <i class="fas fa-magic me-1"></i>Lancer l'intégration
                </a>
                @endcan
            @elseif($bordereau->peutRevenirEnBrouillon())
                @can('achat.bordereaux.valider')
                <a href="{{ route('achat.bordereaux.wizard', $bordereau) }}"
                   class="btn btn-sm btn-warning text-dark rounded-1">
                    <i class="fas fa-magic me-1"></i>Poursuivre l'intégration
                </a>
                @endcan
                @can('achat.bordereaux.edit')
                {{-- EF-BL-12 : retour arrière possible tant que rien n'est intégré. --}}
                <button type="button" id="btn-revenir-brouillon" class="btn btn-sm btn-outline-danger rounded-1"
                        data-bs-toggle="tooltip"
                        title="Abandonner la saisie en cours et rendre le bordereau modifiable">
                    <i class="fas fa-undo me-1"></i>Revenir en brouillon
                </button>
                @endcan
            @endif

            {{-- Boutons du mode édition, masqués par défaut --}}
            <button type="submit" form="bl-form" id="btn-enregistrer" class="btn btn-sm btn-primary rounded-1 px-3 d-none">
                <i class="fas fa-save me-1"></i>Enregistrer
            </button>
            <button type="button" id="btn-annuler-edition" class="btn btn-sm btn-outline-danger rounded-1 d-none">
                Annuler
            </button>
        </div>
    </div>
</div>

{{-- ── ONGLETS ─────────────────────────────────────────────────────────── --}}
<ul class="nav nav-tabs border-bottom-0 bg-white px-3 pt-2 rounded-top" id="bl-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-fiche" type="button" role="tab">
            <i class="fas fa-file-invoice me-1"></i>Fiche
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-equipements" type="button" role="tab">
            <i class="fas fa-laptop me-1"></i>Matériel intégré
            <span class="badge bg-light text-dark border ms-1">{{ $equipements->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold small px-3 py-2 border-0" data-bs-toggle="tab"
                data-bs-target="#onglet-documents" type="button" role="tab">
            <i class="fas fa-paperclip me-1"></i>Documents
            <span class="badge bg-light text-dark border ms-1" id="compteur-documents">{{ $bordereau->documents->count() }}</span>
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
        <form id="bl-form" autocomplete="off" novalidate>
            @csrf

            <div class="card border-1 rounded-1 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast text-primary me-2"></i>Informations de livraison</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            {{-- EF-BL-14 : le rattachement est définitif, en lecture seule. --}}
                            <label class="form-label small fw-semibold">Bon de commande</label>
                            <input type="text" class="form-control form-control-sm bg-light fw-semibold" readonly
                                   value="{{ $bordereau->bonCommande?->numero_commande }} — {{ $bordereau->bonCommande?->fournisseur?->nom }}">
                            <div class="form-text" style="font-size:.72rem">
                                <a href="{{ route('achat.bons-commande.show', $bordereau->bonCommande) }}">
                                    Consulter le bon de commande <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="date_livraison">
                                Date de livraison <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="date_livraison" id="date_livraison"
                                   class="form-control form-control-sm champ-editable"
                                   value="{{ $bordereau->date_livraison?->toDateString() }}" required disabled>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="ref_bordereau_physique">
                                Réf. du bordereau fournisseur <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="ref_bordereau_physique" id="ref_bordereau_physique"
                                   class="form-control form-control-sm champ-editable text-uppercase"
                                   value="{{ $bordereau->ref_bordereau_physique }}" required disabled>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold" for="commentaire">Observations de réception</label>
                            <textarea name="commentaire" id="commentaire" rows="2" disabled
                                      class="form-control form-control-sm champ-editable"
                                      placeholder="Aucune observation.">{{ $bordereau->commentaire }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-1 rounded-1">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-boxes text-primary me-2"></i>Articles reçus</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="table-lignes">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Article</th>
                                    <th style="width: 15%;" class="text-center">Commandé</th>
                                    <th style="width: 15%;" class="text-center">Reçu sur ce bordereau</th>
                                    <th style="width: 15%;" class="text-center">Refusé</th>
                                    <th style="width: 15%;" class="text-center colonne-action d-none">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody id="lignes-container"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ── MATÉRIEL ────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="onglet-equipements" role="tabpanel">
        @include('achat::shared._equipements', ['equipements' => $equipements])
    </div>

    {{-- ── DOCUMENTS ───────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="onglet-documents" role="tabpanel">
        @include('achat::shared._documents', ['porteur' => $bordereau, 'type' => 'bordereau'])
    </div>

    {{-- ── JOURNAL ─────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="onglet-journal" role="tabpanel">
        @include('achat::shared._journal', ['journal' => $journal])
    </div>
</div>

<div class="card border-1 rounded-1 mt-3 bg-light">
    <div class="card-body py-2 px-3" style="font-size: 0.8rem;">
        <div class="row g-2 text-muted">
            <div class="col-md-6">
                <i class="fas fa-user-edit me-1"></i><strong>Créé par :</strong>
                {{ $bordereau->creator?->name ?? 'Système' }},
                le {{ $bordereau->created_at->format('d/m/Y à H:i') }}
            </div>
            <div class="col-md-6 text-end">
                <i class="fas fa-sync me-1"></i><strong>Dernière modification :</strong>
                {{ $bordereau->updated_at->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</div>

@include('achat::shared._modal_pdf', ['id' => 'modal-pdf', 'titre' => 'Impression du bordereau de livraison'])

@endsection

@push('js')
<script>
    window.achatFicheBl = {
        id: {{ $bordereau->id }},
        numero: @json($bordereau->numero_livraison),
        modifiable: @json($bordereau->estModifiable()),
        lignes: @json($lignesExistantes),
        urls: {
            mettreAJour: @json(route('achat.bordereaux.update', $bordereau)),
            revenirBrouillon: @json(route('achat.bordereaux.revenir-brouillon', $bordereau)),
            imprimer: @json(route('achat.bordereaux.imprimer', $bordereau)),
            documents: @json(route('achat.documents.store')),
        },
    };
</script>
<script src="{{ asset('js/modules/achat/documents.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/achat/bordereaux/show.js') }}?v={{ time() }}"></script>
@endpush
