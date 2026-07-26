{{--
    Matériel généré par l'intégration au parc (ENF-TRA-04).
    Variable : $equipements
--}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-light border-0 py-2">
        <h6 class="mb-0 fw-bold small text-dark">
            <i class="fas fa-laptop me-1"></i>Équipements créés dans le parc informatique
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Code inventaire</th>
                        <th>Catégorie</th>
                        <th>Marque et modèle</th>
                        <th>N° de série</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">État</th>
                        <th class="text-end" style="width: 14%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipements as $equipement)
                        <tr>
                            <td class="fw-bold text-dark font-monospace">{{ $equipement->code_inventaire }}</td>
                            <td class="text-muted">
                                @if($equipement->categorie?->icone)
                                    <i class="bi {{ $equipement->categorie->icone }} me-1"></i>
                                @endif
                                {{ $equipement->categorie?->libelle ?? '-' }}
                            </td>
                            <td class="fw-semibold">{{ $equipement->marque?->libelle }} {{ $equipement->modele }}</td>
                            <td class="text-muted font-monospace">{{ $equipement->numero_serie ?: '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    {{ str_replace('_', ' ', $equipement->statut) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ ucfirst($equipement->etat) }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ $equipement->detail_route }}" target="_blank" rel="noopener"
                                   class="btn btn-xs btn-outline-primary" title="Ouvrir la fiche dans ParcInfo">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(Route::has('parc-info.equipements.imprimer-etiquette'))
                                <a href="{{ route('parc-info.equipements.imprimer-etiquette', $equipement->id) }}"
                                   target="_blank" rel="noopener"
                                   class="btn btn-xs btn-outline-secondary" title="Imprimer l'étiquette d'inventaire">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i>
                                Aucun équipement n'a été intégré au parc à ce stade.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
