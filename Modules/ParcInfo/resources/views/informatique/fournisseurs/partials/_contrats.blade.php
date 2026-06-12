<div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
    <div class="card-body p-4">
        <h6 class="section-title mb-4"><span class="section-num"><i class="bi bi-file-earmark-text"></i></span> Contrats de Maintenance</h6>
        
        @if($fournisseur->contrats->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nom du Contrat</th>
                        <th>Référence</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Coût</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fournisseur->contrats as $contrat)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $contrat->nom }}</div>
                            @if($contrat->notes)
                                <small class="text-muted">{{ Str::limit($contrat->notes, 60) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="font-monospace small">{{ $contrat->reference }}</span>
                        </td>
                        <td>{{ $contrat->date_debut ? $contrat->date_debut->format('d/m/Y') : '—' }}</td>
                        <td>{{ $contrat->date_fin ? $contrat->date_fin->format('d/m/Y') : '—' }}</td>
                        <td>
                            @if($contrat->cout)
                                <span class="fw-semibold">{{ number_format($contrat->cout, 2, ',', ' ') }} FCFA</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $contrat->est_actif ? 'success' : 'secondary' }}">
                                {{ $contrat->est_actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-file-earmark-text fs-1 opacity-50 d-block mb-2"></i>
            Aucun contrat de maintenance enregistré pour ce fournisseur
        </div>
        @endif
    </div>
</div>
