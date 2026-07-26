@props(['libelle', 'valeur', 'detail' => null, 'icone' => 'fa-chart-bar', 'couleur' => 'primary', 'lien' => null])

<div class="card border-1 rounded-1 h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-2 bg-{{ $couleur }} bg-opacity-10 p-3">
            <i class="fas {{ $icone }} fs-4 text-{{ $couleur }}"></i>
        </div>
        <div class="flex-grow-1">
            <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">
                {{ $libelle }}
            </div>
            <div class="fw-bold fs-4 {{ $couleur === 'danger' && $valeur > 0 ? 'text-danger' : '' }}">{{ $valeur }}</div>
            @if($detail)
                <div class="small text-muted">{{ $detail }}</div>
            @endif
        </div>
        @if($lien)
            <a href="{{ $lien }}" class="btn btn-sm btn-outline-secondary rounded-1" title="Consulter">
                <i class="fas fa-arrow-right"></i>
            </a>
        @endif
    </div>
</div>
