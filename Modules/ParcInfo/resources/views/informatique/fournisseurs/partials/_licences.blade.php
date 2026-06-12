<div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
    <div class="card-body p-4">
        <h6 class="section-title mb-4"><span class="section-num"><i class="bi bi-file-lock"></i></span> Licences Associées</h6>
        
        @if($fournisseur->licences->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Logiciel</th>
                        <th>Activation / Licencing</th>
                        <th>Clé de Licence</th>
                        <th>Expiration</th>
                        <th>Postes (Utilisés/Accordés)</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fournisseur->licences as $licence)
                    @php
                        $validite = $licence->statut_validite;
                        $badgeColor = [
                            'VALIDE' => 'success',
                            'ALERTE' => 'warning',
                            'EXPIREE' => 'danger'
                        ][$validite] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $licence->logiciel->nom }}</div>
                            @if($licence->logiciel->editeur)
                                <small class="text-muted">{{ $licence->logiciel->editeur->nom }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="small">{{ ucfirst($licence->type_activation) }}</span>
                            <div class="text-muted small">{{ ucfirst(str_replace('_', ' ', $licence->modele_licencing)) }}</div>
                        </td>
                        <td>
                            <code class="text-dark small">{{ $licence->cle_licence ?: '—' }}</code>
                        </td>
                        <td>
                            {{ $licence->date_expiration ? $licence->date_expiration->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            @if($licence->nombre_postes_accordes === 0)
                                <span class="badge bg-light text-dark">Illimité</span>
                            @else
                                <span class="small">{{ $licence->nombre_postes_utilises }} / {{ $licence->nombre_postes_accordes }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $badgeColor }}">
                                {{ $validite }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-file-lock fs-1 opacity-50 d-block mb-2"></i>
            Aucune licence enregistrée pour ce fournisseur
        </div>
        @endif
    </div>
</div>
