{{--
    Onglet de gestion documentaire, partagé par les bons de commande et les
    bordereaux (correction AN-16 : ce bloc était dupliqué à l'identique).

    Variables : $porteur (BonCommande|BordereauLivraison), $type ('bon_commande'|'bordereau')
--}}
<div class="row g-3">
    @can('achat.documents.create')
    <div class="col-md-4">
        <div class="card border-1 rounded-1">
            <div class="card-header bg-light border-0 py-2">
                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-upload me-1"></i>Joindre un document</h6>
            </div>
            <div class="card-body p-3">
                <form id="form-document" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="documentable_type" value="{{ $type }}">
                    <input type="hidden" name="documentable_id" value="{{ $porteur->id }}">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="doc-nom">
                            Intitulé <span class="text-muted">(facultatif)</span>
                        </label>
                        <input type="text" name="nom" id="doc-nom" class="form-control form-control-sm"
                               placeholder="Ex : bon signé, devis fournisseur…">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="doc-fichier">
                            Fichier <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="document" id="doc-fichier" class="form-control form-control-sm" required>
                        <div class="form-text" style="font-size: 0.72rem;">
                            {{ strtoupper(implode(', ', config('achat.documents.mimes', []))) }} &mdash;
                            {{ round(config('achat.documents.taille_max_ko', 10240) / 1024) }} Mo maximum.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="doc-notes">Notes</label>
                        <textarea name="notes" id="doc-notes" class="form-control form-control-sm" rows="2"
                                  placeholder="Informations complémentaires…"></textarea>
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-1" id="btn-upload-document">
                        <i class="fas fa-plus-circle me-1"></i> Téléverser
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    <div class="@can('achat.documents.create') col-md-8 @else col-md-12 @endcan">
        <div class="card border-1 rounded-1">
            <div class="card-header bg-light border-0 py-2">
                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-folder-open me-1"></i>Documents joints</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small" id="table-documents">
                        <thead class="table-light">
                            <tr>
                                <th>Fichier</th>
                                <th>Notes</th>
                                <th class="text-center">Taille</th>
                                <th>Ajouté par</th>
                                <th class="text-end" style="width: 14%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porteur->documents as $document)
                                <tr id="document-{{ $document->id }}">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="{{ $document->icone }} fs-5"></i>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $document->nom }}</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    Ajouté le {{ $document->created_at->format('d/m/Y à H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ $document->notes ?: '-' }}</td>
                                    <td class="text-center text-muted text-nowrap">{{ $document->taille_lisible }}</td>
                                    <td>{{ $document->creator?->name ?? 'Système' }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('achat.documents.telecharger', $document) }}"
                                           class="btn btn-xs btn-outline-primary" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @can('achat.documents.delete')
                                        <button type="button" class="btn btn-xs btn-outline-danger btn-supprimer-document"
                                                data-id="{{ $document->id }}" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr class="ligne-vide-documents">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-1"></i> Aucun document joint.
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
