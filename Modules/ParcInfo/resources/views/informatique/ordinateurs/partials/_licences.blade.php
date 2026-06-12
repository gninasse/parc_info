<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="section-title mb-0"><span class="section-num"><i class="bi bi-file-lock"></i></span> Licences Affectées</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-associer-licence">
                <i class="bi bi-plus-circle me-1"></i> Associer une licence
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Logiciel / Éditeur</th>
                        <th>Type d'Activation</th>
                        <th>Clé de Licence</th>
                        <th>Date Affectation</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipement->affectationsLicences as $aff)
                    @php
                        $lic = $aff->licence;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $lic->logiciel->nom }}</div>
                            @if($lic->logiciel->editeur)
                                <small class="text-muted">{{ $lic->logiciel->editeur->nom }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="small">{{ ucfirst($lic->type_activation) }}</span>
                            <div class="text-muted small">{{ ucfirst(str_replace('_', ' ', $lic->modele_licencing)) }}</div>
                        </td>
                        <td>
                            <code class="text-dark small">{{ $lic->cle_licence ?: '—' }}</code>
                        </td>
                        <td>{{ $aff->date_affectation ? $aff->date_affectation->format('d/m/Y') : '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $aff->actif ? 'success' : 'secondary' }}">
                                {{ $aff->actif ? 'Actif' : 'Terminé' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($aff->actif)
                            <button type="button" class="btn btn-outline-danger btn-sm btn-desassocier-licence" data-id="{{ $aff->id }}" title="Désassocier">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            @else
                            —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-file-lock fs-1 opacity-25 d-block mb-2"></i>
                            Aucune licence affectée à cet équipement.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── MODAL ASSOCIER LICENCE ── --}}
<div class="modal fade" id="modal-associer-licence" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Associer une licence
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-associer-licence">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="field-label">Sélectionner la licence <span class="text-danger">*</span></label>
                        <select name="licence_id" id="assoc_licence_id" class="form-select field-input" required>
                            <option value="">Choisissez une licence disponible...</option>
                            @foreach($licencesDisponibles as $lic)
                                <option value="{{ $lic->id }}">
                                    {{ $lic->logiciel->nom }} (Clé: {{ Str::limit($lic->cle_licence, 15) ?: 'N/A' }}) - {{ $lic->disponibilites }} disponible(s)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info border-0 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Seules les licences actives avec des postes disponibles sont listées.
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-associer-licence">
                        <i class="bi bi-check-circle me-1"></i>Associer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
