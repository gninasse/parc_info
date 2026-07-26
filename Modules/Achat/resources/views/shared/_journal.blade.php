{{--
    Piste d'audit d'un document (ENF-TRA-03 / ENF-TRA-05).

    Correction AN-09 : les entrées proviennent de spatie/laravel-activitylog et
    reflètent les événements réellement survenus. La version précédente
    reconstituait trois lignes à partir de created_at, updated_at et
    date_validation, ce qui laissait croire à une traçabilité complète.

    Variable : $journal (collection d'activités)
--}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-light border-0 py-2">
        <h6 class="mb-0 fw-bold small text-dark">
            <i class="fas fa-history me-1"></i>Journal des événements
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 18%;">Date et heure</th>
                        <th style="width: 22%;">Utilisateur</th>
                        <th>Événement</th>
                        <th style="width: 28%;">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($journal as $evenement)
                        <tr>
                            <td class="text-nowrap text-muted">{{ $evenement->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="fw-semibold text-dark">{{ $evenement->causer?->name ?? 'Système' }}</td>
                            <td>{{ $evenement->description }}</td>
                            <td class="text-muted" style="font-size: .78rem;">
                                @forelse($evenement->properties ?? [] as $cle => $valeur)
                                    @if(! is_array($valeur))
                                        <div>
                                            <span class="text-capitalize">{{ str_replace('_', ' ', $cle) }}</span> :
                                            <span class="fw-semibold">{{ $valeur }}</span>
                                        </div>
                                    @endif
                                @empty
                                    &mdash;
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i> Aucun événement enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
