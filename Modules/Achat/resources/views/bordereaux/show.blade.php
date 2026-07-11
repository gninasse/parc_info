@extends('achat::layouts.master')

@section('title', "Bordereau {$bl->numero_livraison} - Achat")
@section('header', "Bordereau de Livraison : {$bl->numero_livraison}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.index') }}">Bordereaux</a></li>
    <li class="breadcrumb-item active">{{ $bl->numero_livraison }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-12">
        
        {{-- ── BARRE D'ACTIONS DE L'ENTÊTE ── --}}
        <div class="card border-1 rounded-1 mb-3">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="small text-muted me-2">Statut actuel :</span>
                    @if($bl->statut === 'brouillon')
                        <span class="badge bg-secondary"><i class="fas fa-edit me-1"></i>Brouillon</span>
                    @elseif($bl->statut === 'wizard')
                        <span class="badge bg-warning text-white"><i class="fas fa-magic me-1"></i>Wizard en cours</span>
                    @elseif($bl->statut === 'valide')
                        <span class="badge bg-success"><i class="fas fa-check-double me-1"></i>Validé & Intégré</span>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-sm btn-outline-secondary rounded-1">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    
                    <button type="button" id="btn-print-bl" class="btn btn-sm btn-outline-success rounded-1">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                    
                    @if($bl->statut === 'brouillon')
                        @can('achat.bordereaux.edit')
                        <button type="button" id="btn-toggle-edit" class="btn btn-sm btn-outline-primary rounded-1">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </button>
                        <a href="{{ route('achat.bordereaux.wizard', $bl->id) }}" class="btn btn-sm btn-success rounded-1 text-white">
                            <i class="fas fa-magic me-1"></i>Lancer l'intégration
                        </a>
                        @endcan
                    @elseif($bl->statut === 'wizard')
                        @can('achat.bordereaux.edit')
                        <a href="{{ route('achat.bordereaux.wizard', $bl->id) }}" class="btn btn-sm btn-warning rounded-1 text-white">
                            <i class="fas fa-magic me-1"></i>Continuer l'intégration
                        </a>
                        @endcan
                    @endif

                    {{-- Boutons cachés par défaut, affichés lors de l'édition --}}
                    <button type="submit" id="btn-save-edit" class="btn btn-sm btn-primary rounded-1 px-3 d-none">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                    <button type="button" id="btn-cancel-edit" class="btn btn-sm btn-outline-danger rounded-1 d-none">
                        Annuler
                    </button>
                </div>
            </div>
        </div>

        {{-- ── TAB NAV DE NAVIGATION ── --}}
        <ul class="nav nav-tabs rounded-1 border-bottom-0 bg-white px-3 pt-2 shadow-sm" id="blTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold small text-muted px-3 py-2 border-0" id="fiche-tab" data-bs-toggle="tab" data-bs-target="#fiche" type="button" role="tab" aria-controls="fiche" aria-selected="true">
                    <i class="fas fa-file-invoice me-1"></i>Fiche BL
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="equipements-tab" data-bs-toggle="tab" data-bs-target="#equipements" type="button" role="tab" aria-controls="equipements" aria-selected="false">
                    <i class="fas fa-laptop me-1"></i>Équipements intégrés
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                    <i class="fas fa-paperclip me-1"></i>Documents joints <span class="badge bg-light text-dark border ms-1" id="doc-count-badge">{{ $bl->documents->count() }}</span>
                </button>
            </li>
        </ul>

        {{-- ── CONTENU DES ONGLETS ── --}}
        <div class="tab-content shadow-sm rounded-bottom bg-white p-4 border-top" id="blTabsContent" style="margin-top: -1px;">
            
            {{-- TAB 1: FICHE BL --}}
            <div class="tab-pane fade show active" id="fiche" role="tabpanel" aria-labelledby="fiche-tab">
                <form id="form-edit-bl" autocomplete="off">
                    @csrf
                    <input type="hidden" id="bl-id" name="id" value="{{ $bl->id }}">
                    <input type="hidden" id="input-bc-id" name="bon_de_commande_id" value="{{ $bl->bon_de_commande_id }}">
                    
                    {{-- ── CARD HEADER BL ── --}}
                    <div class="card border-1 rounded-1 mb-3">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast text-primary me-2"></i>Informations de Livraison</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                {{-- Affichage lecture seule BC --}}
                                <div class="col-md-4" id="bc-display-readonly">
                                    <label class="form-label small fw-bold">Bon de Commande Correspondant BC</label>
                                    <input type="text" class="form-control form-control-sm bg-light text-dark fw-semibold" value="{{ $bl->bonCommande->numero_commande }} - {{ $bl->bonCommande->fournisseur->nom }}" readonly>
                                </div>
                                {{-- Affichage mode édition BC --}}
                                <div class="col-md-4 d-none" id="bc-display-edit">
                                    <label class="form-label small fw-bold">Bon de Commande BC <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="input-bc-display" class="form-control bg-light cursor-pointer" 
                                               placeholder="Sélectionner un BC..." readonly 
                                               data-bs-toggle="modal" data-bs-target="#modal-select-bc"
                                               value="{{ $bl->bonCommande->numero_commande }} - {{ $bl->bonCommande->fournisseur->nom }}">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-select-bc">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <button type="button" id="btn-clear-bc" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Date de livraison <span class="text-danger">*</span></label>
                                    <input type="date" name="date_livraison" class="form-control form-control-sm field-input" value="{{ $bl->date_livraison->toDateString() }}" required disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Réf. Bordereau Physique <span class="text-danger">*</span></label>
                                    <input type="text" name="ref_bordereau_physique" class="form-control form-control-sm field-input text-uppercase" value="{{ $bl->ref_bordereau_physique }}" required disabled>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Observations / Remarques</label>
                                    <textarea name="commentaire" class="form-control form-control-sm field-input" rows="2" placeholder="Aucune observation..." disabled>{{ $bl->commentaire }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── CARD LIGNES DE BL ── --}}
                    <div class="card border-1 rounded-1 mb-0">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-boxes text-primary me-2"></i>Articles Reçus</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="table-lignes-bl">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 45%;">Article</th>
                                            <th style="width: 20%;" class="text-center">Qté Commandée (BC)</th>
                                            <th style="width: 20%;" class="text-center">Qté Reçue sur ce BL</th>
                                            <th style="width: 15%;" class="text-center th-action d-none">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lines-container">
                                        {{-- Les lignes seront générées dynamiquement en JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TAB 2: EQUIPEMENTS INTEGRES --}}
            <div class="tab-pane fade" id="equipements" role="tabpanel" aria-labelledby="equipements-tab">
                <div class="card border-1 rounded-1">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-laptop me-1"></i>Équipements générés et intégrés à partir de ce BL</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code Inventaire</th>
                                        <th>Catégorie</th>
                                        <th>Marque & Modèle</th>
                                        <th>N° Série</th>
                                        <th>Statut</th>
                                        <th>État</th>
                                        <th class="text-end" style="width: 15%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($equipements as $eq)
                                        <tr>
                                            <td class="fw-bold text-dark font-monospace">{{ $eq->code_inventaire }}</td>
                                            <td>
                                                <span class="text-muted">
                                                    <i class="bi {{ $eq->categorie->icone }} me-1"></i>{{ $eq->categorie->libelle }}
                                                </span>
                                            </td>
                                            <td class="fw-semibold">{{ $eq->marque?->libelle }} {{ $eq->modele }}</td>
                                            <td class="text-muted">{{ $eq->numero_serie ?: '-' }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $eq->statut) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ ucfirst($eq->etat) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ $eq->detail_route }}" target="_blank" class="btn btn-xs btn-outline-primary" title="Voir la fiche">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('parc-info.equipements.imprimer-etiquette', $eq->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Imprimer l'étiquette">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-1"></i> Aucun équipement n'a encore été intégré pour ce bordereau de livraison.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: DOCUMENTS JOINTS --}}
            <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                <div class="row g-3">
                    {{-- Upload Form --}}
                    <div class="col-md-4">
                        <div class="card border-1 rounded-1">
                            <div class="card-header bg-light border-0 py-2">
                                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-upload me-1"></i>Ajouter un Document</h6>
                            </div>
                            <div class="card-body p-3">
                                <form id="form-upload-document" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="documentable_type" value="bordereau">
                                    <input type="hidden" name="documentable_id" value="{{ $bl->id }}">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Nom du document (Optionnel)</label>
                                        <input type="text" name="nom" class="form-control form-control-sm" placeholder="Ex: Borderau signé, Attestation...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Fichier <span class="text-danger">*</span></label>
                                        <input type="file" name="document" class="form-control form-control-sm" required>
                                        <div class="form-text small" style="font-size: 0.75rem;">PDF, Images, Word, Excel. Max 10 Mo.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Notes / Description</label>
                                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Informations complémentaires..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-1">
                                        <i class="fas fa-plus-circle me-1"></i> Téléverser le document
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Documents List --}}
                    <div class="col-md-8">
                        <div class="card border-1 rounded-1">
                            <div class="card-header bg-light border-0 py-2">
                                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-folder-open me-1"></i>Liste des documents joints</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small" id="table-bc-documents">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nom du Fichier</th>
                                                <th>Notes</th>
                                                <th>Taille</th>
                                                <th>Ajouté par</th>
                                                <th class="text-end" style="width: 15%;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($bl->documents as $doc)
                                                <tr id="doc-row-{{ $doc->id }}">
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if(str_contains($doc->type_mime, 'pdf'))
                                                                <i class="far fa-file-pdf text-danger fs-5"></i>
                                                            @elseif(str_contains($doc->type_mime, 'image'))
                                                                <i class="far fa-file-image text-success fs-5"></i>
                                                            @else
                                                                <i class="far fa-file text-primary fs-5"></i>
                                                            @endif
                                                            <div>
                                                                <div class="fw-bold text-dark">{{ $doc->nom }}</div>
                                                                <div class="text-muted" style="font-size: 0.75rem;">Ajouté le {{ $doc->created_at->format('d/m/Y H:i') }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted">{{ $doc->notes ?: '-' }}</td>
                                                    <td class="text-muted">{{ number_format($doc->taille / 1024, 1) }} Ko</td>
                                                    <td>{{ $doc->createur ? $doc->createur->name : 'Système' }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('achat.documents.telecharger', $doc->id) }}" class="btn btn-xs btn-outline-primary" title="Télécharger">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-xs btn-outline-danger btn-delete-doc" data-id="{{ $doc->id }}" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="empty-docs-row">
                                                    <td colspan="5" class="text-center py-4 text-muted">
                                                        <i class="fas fa-info-circle me-1"></i> Aucun document joint pour ce bordereau de livraison.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── SECTION INFORMATIONS AUDIT ── --}}
        <div class="card border-1 rounded-1 mt-3">
            <div class="card-body py-3 bg-light">
                <div class="row text-muted small">
                    <div class="col-md-6">
                        <i class="fas fa-user-edit me-1"></i><strong>Créé par :</strong> {{ $bl->createur ? $bl->createur->name : 'Système' }}<br>
                        <i class="fas fa-clock me-1"></i><strong>Le :</strong> {{ $bl->created_at->format('d/m/Y à H:i') }}
                    </div>
                    <div class="col-md-6 border-start">
                        <i class="fas fa-history me-1"></i><strong>Dernière modification :</strong> {{ $bl->updated_at->format('d/m/Y à H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sélection Bon de Commande -->
<div class="modal fade shadow" id="modal-select-bc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary border-0 py-3">
                <h5 class="modal-title fs-6 fw-bold text-primary"><i class="fas fa-file-invoice me-2"></i>Sélectionner un Bon de Commande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="search-bc" class="form-control bg-light border-start-0" placeholder="Rechercher par numéro, fournisseur...">
                </div>
                
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0 border-top" id="table-modal-bc">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 25%;">N° Commande</th>
                                <th style="width: 45%;">Fournisseur</th>
                                <th style="width: 15%;">Date</th>
                                <th style="width: 15%; text-align: right;">Montant Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bonsCommande as $bc)
                                <tr class="bc-row cursor-pointer" 
                                    data-id="{{ $bc->id }}" 
                                    data-numero="{{ $bc->numero_commande }}" 
                                    data-fournisseur="{{ $bc->fournisseur->nom }}"
                                    data-search="{{ strtolower($bc->numero_commande . ' ' . $bc->fournisseur->nom) }}">
                                    <td><span class="badge bg-secondary font-monospace">{{ $bc->numero_commande }}</span></td>
                                    <td class="fw-bold text-dark">{{ $bc->fournisseur->nom }}</td>
                                    <td class="text-muted small">{{ $bc->date_commande->format('d/m/Y') }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($bc->montant_total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle me-1"></i> Aucun bon de commande en cours trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal d'impression uniforme --}}
<div class="modal fade shadow" id="printBlModal" tabindex="-1" aria-labelledby="printBlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary border-0 text-primary py-3">
                <h5 class="modal-title fw-bold" id="printBlModalLabel">
                    <i class="fas fa-file-pdf me-2 text-danger"></i>Impression du Bordereau de Livraison
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="print-bl-iframe" class="w-100" style="height: 70vh; border: none; border-radius: 4px;" src=""></iframe>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-1 px-3" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    // Liste des lignes de livraison pour gestion JS
    window.existingLines = @json($existingLines);

    // Soumission du formulaire d'upload de document
    $('#form-upload-document').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        $.ajax({
            url: "{{ route('achat.documents.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    Swal.fire('Succès !', res.message, 'success');
                    
                    // Increment count badge
                    let badge = $('#doc-count-badge');
                    let count = parseInt(badge.text()) || 0;
                    badge.text(count + 1);
                    
                    // Append document row
                    let doc = res.document;
                    let fileIcon = '';
                    if (doc.type_mime.includes('pdf')) {
                        fileIcon = '<i class="far fa-file-pdf text-danger fs-5"></i>';
                    } else if (doc.type_mime.includes('image')) {
                        fileIcon = '<i class="far fa-file-image text-success fs-5"></i>';
                    } else {
                        fileIcon = '<i class="far fa-file text-primary fs-5"></i>';
                    }
                    
                    let newRow = `
                        <tr id="doc-row-${doc.id}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    ${fileIcon}
                                    <div>
                                        <div class="fw-bold text-dark">${doc.nom}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Ajouté le ${doc.date}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted">${$('#form-upload-document textarea[name="notes"]').val() || '-'}</td>
                            <td class="text-muted">${(doc.taille / 1024).toFixed(1)} Ko</td>
                            <td>{{ auth()->user()->name }}</td>
                            <td class="text-end">
                                <a href="${route('achat.documents.telecharger', doc.id)}" class="btn btn-xs btn-outline-primary" title="Télécharger">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" class="btn btn-xs btn-outline-danger btn-delete-doc" data-id="${doc.id}" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    // Remove empty row if exists
                    $('.empty-docs-row').remove();
                    $('#table-bc-documents tbody').append(newRow);
                    
                    // Clear form
                    $('#form-upload-document')[0].reset();
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'upload du document', 'error');
            }
        });
    });

    // Suppression d'un document
    $(document).on('click', '.btn-delete-doc', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Supprimer ce document ?',
            text: 'Voulez-vous vraiment supprimer ce document joint ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('achat.documents.destroy', id),
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Supprimé !', res.message, 'success');
                            
                            // Decrement count badge
                            let badge = $('#doc-count-badge');
                            let count = parseInt(badge.text()) || 0;
                            if (count > 0) badge.text(count - 1);
                            
                            // Remove row
                            $(`#doc-row-${id}`).remove();
                            
                            // If table is empty, show empty message
                            if ($('#table-bc-documents tbody tr').length === 0) {
                                $('#table-bc-documents tbody').append(`
                                    <tr class="empty-docs-row">
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fas fa-info-circle me-1"></i> Aucun document joint pour ce bordereau de livraison.
                                        </td>
                                    </tr>
                                `);
                            }
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la suppression', 'error');
                    }
                });
            }
        });
    });
</script>
<script src="{{ asset('js/modules/achat/bordereaux/show.js') }}?v={{ time() }}"></script>
@endpush
