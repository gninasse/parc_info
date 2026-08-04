@extends('stock::layouts.master')

@section('header', 'Tableau de bord')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
@endsection

@push('css')
<style>
    .kpi { border-left: 4px solid transparent; }
    .kpi-alerte { border-left-color: #ffc107; }
    .kpi-rupture { border-left-color: #dc3545; }
    .kpi a.stretched-link { text-decoration: none; }
    .pastille-attente { border: 1px dashed var(--bs-border-color); border-radius: .5rem; }
    .ligne-alerte td { vertical-align: middle; }
</style>
@endpush

@section('content')

{{-- ── Zone 1 : les 6 KPI (UX §1) ─────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    @php
        $cartes = [
            ['libelle' => 'Magasins actifs', 'valeur' => $kpis['magasins_actifs'], 'icone' => 'bi-shop', 'couleur' => 'primary', 'url' => Route::has('stock.magasins.index') ? route('stock.magasins.index') : null, 'classe' => ''],
            ['libelle' => 'Références en stock', 'valeur' => $kpis['references_en_stock'], 'icone' => 'bi-boxes', 'couleur' => 'primary', 'url' => route('stock.niveaux.index'), 'classe' => ''],
            ['libelle' => 'Sous seuil', 'valeur' => $kpis['sous_seuil'], 'icone' => 'bi-exclamation-triangle', 'couleur' => 'warning', 'url' => route('stock.niveaux.index', ['statut' => 'SOUS_SEUIL']), 'classe' => 'kpi-alerte'],
            ['libelle' => 'Ruptures', 'valeur' => $kpis['ruptures'], 'icone' => 'bi-x-octagon', 'couleur' => 'danger', 'url' => route('stock.niveaux.index', ['statut' => 'RUPTURE']), 'classe' => 'kpi-rupture'],
            ['libelle' => 'Équipements en stock', 'valeur' => $kpis['equipements_en_stock'], 'icone' => 'bi-pc-display', 'couleur' => 'dark', 'url' => null, 'classe' => ''],
        ];
    @endphp

    @foreach($cartes as $carte)
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm h-100 kpi {{ $carte['classe'] }} position-relative">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1 text-{{ $carte['couleur'] }}">
                        <i class="bi {{ $carte['icone'] }}"></i>
                        <span class="small text-uppercase text-muted">{{ $carte['libelle'] }}</span>
                    </div>
                    <div class="fs-3 fw-bold">{{ number_format($carte['valeur'], 0, ',', ' ') }}</div>
                    @if($carte['url'])
                        <a href="{{ $carte['url'] }}" class="stretched-link" aria-label="{{ $carte['libelle'] }}"></a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="col-6 col-lg-2">
        <div class="card border-0 shadow-sm h-100 kpi">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1 text-success">
                    <i class="bi bi-cash-stack"></i>
                    <span class="small text-uppercase text-muted">Valeur estimée</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ number_format($kpis['valeur_estimee'], 0, ',', ' ') }} <span class="fs-6 fw-normal">FCFA</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pastilles « en attente de validation » (par magasin) --}}
@if($enAttente->isNotEmpty() || $nonValidesAnciens > 0)
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    @foreach($enAttente as $ligne)
        <a href="{{ route('stock.entrees.index', ['statut' => 'BROUILLON', 'magasin_id' => $ligne['magasin']->id]) }}"
           class="pastille-attente px-3 py-2 text-decoration-none text-body small">
            <strong>{{ $ligne['magasin']->code }}</strong> :
            @if($ligne['equipements'] > 0)
                +{{ rtrim(rtrim(number_format($ligne['equipements'], 2, ',', ' '), '0'), ',') }} équipements,
            @endif
            @if($ligne['articles'] > 0)
                +{{ rtrim(rtrim(number_format($ligne['articles'], 2, ',', ' '), '0'), ',') }} articles en réception
            @endif
            @if($ligne['sorties'] > 0)
                <span class="mx-1">·</span>{{ $ligne['sorties'] }} sortie(s) non validée(s)
            @endif
            @if($ligne['transferts'] > 0)
                <span class="mx-1">·</span>{{ $ligne['transferts'] }} transfert(s)
            @endif
        </a>
    @endforeach

    <i class="bi bi-info-circle text-muted" data-bs-toggle="popover" data-bs-trigger="hover focus"
       data-bs-content="Bons enregistrés mais non validés : leurs quantités ne sont pas encore dans les niveaux."></i>

    {{-- Pastille superviseur : bons qui traînent (config jours_alerte_non_valides) --}}
    @can('stock.mouvements.contre')
        @if($nonValidesAnciens > 0)
            <a href="{{ route('stock.entrees.index', ['statut' => 'BROUILLON']) }}"
               class="badge bg-danger text-decoration-none py-2">
                <i class="bi bi-hourglass-split me-1"></i>
                {{ $nonValidesAnciens }} bon(s) non validé(s) depuis plus de {{ $joursAlerte }} jours
            </a>
        @endif
    @endcan
</div>
@endif

<div class="row g-3 mb-3">
    {{-- ── Zone 2 : alertes de seuil (2/3) ────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Alertes de seuil</h6>
                <a href="{{ route('stock.niveaux.index') }}" class="small">Voir l'état des stocks →</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Article</th>
                            <th>Magasin</th>
                            <th class="text-end">Qté</th>
                            <th>Seuil</th>
                            <th class="text-center">Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alertes as $niveau)
                            <tr class="ligne-alerte">
                                <td>
                                    <span class="font-monospace small text-muted">{{ $niveau->article->code }}</span>
                                    {{ $niveau->article->nom }}
                                </td>
                                <td class="small">{{ $niveau->magasin->libelle }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format($niveau->quantite, 2, ',', ' '), '0'), ',') }}</td>
                                <td class="small">
                                    @if($niveau->seuil_effectif !== null)
                                        {{ rtrim(rtrim(number_format($niveau->seuil_effectif, 2, ',', ' '), '0'), ',') }}
                                        <span class="badge {{ $niveau->seuil_origine === 'local' ? 'bg-info-subtle text-info-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">
                                            {{ $niveau->seuil_origine }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{-- Icône + texte, jamais la couleur seule (S7) --}}
                                    @if($niveau->statut_alerte === 'RUPTURE')
                                        <span class="badge bg-danger">⛔ RUPTURE</span>
                                    @else
                                        <span class="badge bg-warning text-dark">⚠ SOUS SEUIL</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('stock.entrees.store')
                                        {{-- Ouvre un brouillon d'entrée pré-rempli magasin + article --}}
                                        <a href="{{ route('stock.entrees.create', ['magasin_id' => $niveau->magasin_id, 'article_id' => $niveau->article_id]) }}"
                                           class="btn btn-sm btn-outline-primary text-nowrap">➜ Réceptionner</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-check-circle text-success me-1"></i>
                                    Aucun article sous seuil ni en rupture.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Actions rapides + répartition (1/3) ────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i>Actions rapides</h6>
            </div>
            <div class="card-body d-grid gap-2">
                @can('stock.entrees.store')
                    <a href="{{ route('stock.entrees.create') }}" class="btn btn-primary">+ Nouvelle entrée</a>
                @endcan
                @can('stock.sorties.store')
                    <a href="{{ route('stock.sorties.create') }}" class="btn btn-outline-danger">− Nouvelle sortie</a>
                @endcan
                @can('stock.transferts.store')
                    <a href="{{ route('stock.transferts.create') }}" class="btn btn-outline-primary">⇄ Nouveau transfert</a>
                @endcan
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-primary"></i>Répartition par magasin</h6>
            </div>
            <div class="card-body">
                @forelse($repartition as $ligne)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small">
                            <span class="fw-semibold">{{ $ligne['magasin']->libelle }}</span>
                            <span class="text-muted">{{ $ligne['nb_references'] }} réf. · {{ $ligne['equipements'] }} équip.</span>
                        </div>
                        <div class="progress mt-1" style="height: 6px;"
                             role="progressbar" aria-label="Références — {{ $ligne['magasin']->libelle }}"
                             aria-valuenow="{{ $ligne['part_references'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $ligne['part_references'] }}%"></div>
                        </div>
                        <div class="progress mt-1" style="height: 6px;"
                             role="progressbar" aria-label="Valeur — {{ $ligne['magasin']->libelle }}"
                             aria-valuenow="{{ $ligne['part_valeur'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: {{ $ligne['part_valeur'] }}%"></div>
                        </div>
                        <div class="text-end small text-muted">{{ number_format($ligne['valeur'], 0, ',', ' ') }} FCFA</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aucun magasin actif.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── Zone 3 : derniers mouvements ───────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Derniers mouvements</h6>
        @if(Route::has('stock.mouvements.index'))
            <a href="{{ route('stock.mouvements.index') }}" class="small">Voir l'historique →</a>
        @endif
    </div>
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Document</th>
                    <th class="text-center">Type</th>
                    <th>Article / Équipement</th>
                    <th>Magasin</th>
                    <th class="text-end">Qté</th>
                    <th>Bénéficiaire</th>
                    <th>Par</th>
                </tr>
            </thead>
            <tbody>
                @forelse($derniersMouvements as $mouvement)
                    <tr>
                        <td class="small text-nowrap">{{ $mouvement['date']?->format('d/m/Y H:i') }}</td>
                        <td class="font-monospace small">
                            @if($mouvement['url_document'])
                                <a href="{{ $mouvement['url_document'] }}">{{ $mouvement['libelle_document'] }}</a>
                            @else
                                {{ $mouvement['libelle_document'] }}
                            @endif
                        </td>
                        <td class="text-center">
                            {{-- Icône + texte (S7) --}}
                            @php
                                $classes = [
                                    'ENTREE' => 'bg-success', 'SORTIE' => 'bg-danger',
                                    'TRANSFERT_ENTREE' => 'bg-primary', 'TRANSFERT_SORTIE' => 'bg-primary',
                                    'AJUSTEMENT' => 'bg-warning text-dark',
                                ];
                            @endphp
                            <span class="badge {{ $classes[$mouvement['type']] ?? 'bg-secondary' }}">
                                {{ \Modules\Stock\Models\Mouvement::TYPE_LABELS[$mouvement['type']] ?? $mouvement['type'] }}
                            </span>
                        </td>
                        <td>
                            <span class="font-monospace small text-muted">{{ $mouvement['code_article'] }}</span>
                            {{ $mouvement['article'] }}
                        </td>
                        <td class="small">{{ $mouvement['magasin'] }}</td>
                        <td class="text-end fw-semibold">{{ $mouvement['quantite_signee'] }}</td>
                        <td class="small">{{ $mouvement['beneficiaire'] ?? '—' }}</td>
                        <td class="small">{{ $mouvement['par'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucun mouvement enregistré — le journal se remplit à la validation des bons.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('js')
<script>
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => new bootstrap.Popover(el));
</script>
@endpush
