@extends('stock::layouts.master')

@section('header', 'Statistiques du stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard') }}">Stock</a></li>
    <li class="breadcrumb-item active" aria-current="page">Statistiques</li>
@endsection

@push('css')
<style>
    .carte-stat { transition: transform .15s ease, box-shadow .15s ease; border: 1px solid rgba(0,0,0,.05); }
    .carte-stat:hover { transform: translateY(-4px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.08) !important; }
    .carte-stat .icone {
        width: 44px; height: 44px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
    }
    .zone-graphique { position: relative; height: 290px; width: 100%; }
    .zone-graphique-haute { height: 340px; }
    .classement li { border: 0; }
    .barre-part { height: 6px; border-radius: 3px; background: var(--bs-secondary-bg); overflow: hidden; }
    .barre-part span { display: block; height: 100%; border-radius: 3px; }
</style>
@endpush

@section('content')

{{-- Filtres de la fenêtre d'analyse --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-uppercase text-muted" for="filtre-magasin">Magasin</label>
                <select id="filtre-magasin" class="form-select form-select-sm">
                    <option value="">Tous les magasins (consolidé)</option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected($magasinPreselectionne === $magasin->id)>{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-uppercase text-muted" for="filtre-mois">Fenêtre d'analyse</label>
                <select id="filtre-mois" class="form-select form-select-sm">
                    <option value="3">3 derniers mois</option>
                    <option value="6">6 derniers mois</option>
                    <option value="12" selected>12 derniers mois</option>
                    <option value="24">24 derniers mois</option>
                    <option value="36">36 derniers mois</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2 justify-content-md-end">
                <button id="btn-actualiser" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i>Actualiser
                </button>
                <a href="{{ route('stock.rapports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-journals me-1"></i>États détaillés
                </a>
            </div>
        </div>
        <div class="small text-muted mt-2" id="periode-analysee"></div>
    </div>
</div>

<div id="stats-chargement" class="text-center py-5">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
        <span class="visually-hidden">Calcul des indicateurs…</span>
    </div>
    <h6 class="mt-3 text-muted fw-semibold">Agrégation des indicateurs de stock en cours…</h6>
</div>

<div id="stats-contenu" class="d-none">

    {{-- Ligne 1 : indicateurs de valeur --}}
    <div class="row g-3 mb-3">
        @php
            $cartes = [
                ['id' => 'kpi-valeur', 'libelle' => 'Valeur du stock', 'icone' => 'bi-cash-stack', 'couleur' => 'success'],
                ['id' => 'kpi-references', 'libelle' => 'Références en stock', 'icone' => 'bi-boxes', 'couleur' => 'primary'],
                ['id' => 'kpi-equipements', 'libelle' => 'Équipements en magasin', 'icone' => 'bi-pc-display', 'couleur' => 'dark'],
                ['id' => 'kpi-dispo', 'libelle' => 'Taux de disponibilité', 'icone' => 'bi-check-circle', 'couleur' => 'info'],
            ];
        @endphp
        @foreach($cartes as $carte)
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 carte-stat">
                    <div class="card-body d-flex align-items-center">
                        <div class="icone bg-{{ $carte['couleur'] }}-subtle text-{{ $carte['couleur'] }} me-3 flex-shrink-0">
                            <i class="bi {{ $carte['icone'] }}"></i>
                        </div>
                        <div>
                            <div class="small text-uppercase text-muted mb-1">{{ $carte['libelle'] }}</div>
                            <h4 class="fw-bold mb-0" id="{{ $carte['id'] }}">—</h4>
                            <div class="small text-muted" id="{{ $carte['id'] }}-sous"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Ligne 2 : indicateurs de flux --}}
    <div class="row g-3 mb-4">
        @php
            $cartes2 = [
                ['id' => 'kpi-conso', 'libelle' => 'Consommation mensuelle', 'icone' => 'bi-graph-down-arrow', 'couleur' => 'warning'],
                ['id' => 'kpi-rotation', 'libelle' => 'Rotation annualisée', 'icone' => 'bi-arrow-repeat', 'couleur' => 'primary'],
                ['id' => 'kpi-couverture', 'libelle' => 'Couverture du stock', 'icone' => 'bi-calendar-range', 'couleur' => 'info'],
                ['id' => 'kpi-alertes', 'libelle' => 'Références en alerte', 'icone' => 'bi-exclamation-triangle', 'couleur' => 'danger'],
            ];
        @endphp
        @foreach($cartes2 as $carte)
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 carte-stat">
                    <div class="card-body d-flex align-items-center">
                        <div class="icone bg-{{ $carte['couleur'] }}-subtle text-{{ $carte['couleur'] }} me-3 flex-shrink-0">
                            <i class="bi {{ $carte['icone'] }}"></i>
                        </div>
                        <div>
                            <div class="small text-uppercase text-muted mb-1">{{ $carte['libelle'] }}</div>
                            <h4 class="fw-bold mb-0" id="{{ $carte['id'] }}">—</h4>
                            <div class="small text-muted" id="{{ $carte['id'] }}-sous"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Évolution des flux --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Évolution des flux</h6>
                    <div class="btn-group btn-group-sm" role="group" id="bascule-serie">
                        <input type="radio" class="btn-check" name="serie" id="serie-valeur" value="valeur" checked>
                        <label class="btn btn-outline-primary" for="serie-valeur">Valeur</label>
                        <input type="radio" class="btn-check" name="serie" id="serie-quantite" value="quantite">
                        <label class="btn btn-outline-primary" for="serie-quantite">Quantité</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="zone-graphique zone-graphique-haute"><canvas id="graphique-flux"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2 text-success"></i>Santé du stock</h6>
                </div>
                <div class="card-body">
                    <div class="zone-graphique zone-graphique-haute"><canvas id="graphique-sante"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Structure de la valeur --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-shop me-2 text-primary"></i>Valeur par magasin</h6>
                </div>
                <div class="card-body"><div class="zone-graphique"><canvas id="graphique-magasins"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-tags me-2 text-info"></i>Valeur par nature</h6>
                </div>
                <div class="card-body"><div class="zone-graphique"><canvas id="graphique-natures"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2 text-warning"></i>Top catégories (valeur)</h6>
                </div>
                <div class="card-body"><div class="zone-graphique"><canvas id="graphique-categories"></canvas></div></div>
            </div>
        </div>
    </div>

    {{-- Palmarès --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Articles les plus consommés</h6>
                    <a href="{{ route('stock.rapports.show', 'rotation') }}" class="small text-decoration-none">État de rotation →</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush classement" id="liste-articles"></ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Principaux bénéficiaires</h6>
                    <a href="{{ route('stock.rapports.show', 'consommation') }}" class="small text-decoration-none">État de consommation →</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush classement" id="liste-beneficiaires"></ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Motifs, qualité de saisie, activité --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-ui-radios me-2 text-info"></i>Motifs de sortie</h6>
                </div>
                <div class="card-body"><div class="zone-graphique"><canvas id="graphique-motifs"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clipboard-check me-2 text-danger"></i>Qualité de la saisie</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" id="liste-qualite"></ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-secondary"></i>Activité des magasiniers</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush classement" id="liste-utilisateurs"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script>
(function () {
    const URL_DATA = @json(route('stock.statistiques.data'));
    const PALETTE = @json(\Modules\Stock\Services\StatistiqueService::PALETTE);
    const graphiques = {};
    let donnees = null;

    const fcfa = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v || 0) + ' FCFA';
    const nb = (v, d = 0) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: d }).format(v || 0);

    const dessiner = (id, config) => {
        if (graphiques[id]) graphiques[id].destroy();
        graphiques[id] = new Chart(document.getElementById(id), config);
    };

    const optionsCommunes = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
    };

    const donutValeur = (id, items) => dessiner(id, {
        type: 'doughnut',
        data: {
            labels: items.map((i) => i.libelle),
            datasets: [{ data: items.map((i) => i.valeur ?? i.total), backgroundColor: PALETTE }],
        },
        options: {
            ...optionsCommunes,
            plugins: {
                ...optionsCommunes.plugins,
                tooltip: { callbacks: { label: (c) => `${c.label} : ${fcfa(c.parsed)}` } },
            },
        },
    });

    const rendreFlux = () => {
        const serie = document.querySelector('input[name="serie"]:checked').value;
        const suffixe = serie === 'valeur' ? '_valeur' : '_quantite';
        const mois = donnees.serie_mensuelle;

        dessiner('graphique-flux', {
            type: 'bar',
            data: {
                labels: mois.map((m) => m.mois),
                datasets: [
                    { label: 'Entrées', data: mois.map((m) => m['entrees' + suffixe]), backgroundColor: '#198754', order: 2 },
                    { label: 'Sorties', data: mois.map((m) => m['sorties' + suffixe]), backgroundColor: '#dc3545', order: 2 },
                    {
                        label: 'Solde net', type: 'line', order: 1, tension: .3,
                        data: mois.map((m) => m['entrees' + suffixe] - m['sorties' + suffixe]),
                        borderColor: '#0d6efd', backgroundColor: '#0d6efd', pointRadius: 3,
                    },
                ],
            },
            options: {
                ...optionsCommunes,
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => nb(v) } } },
                plugins: {
                    ...optionsCommunes.plugins,
                    tooltip: {
                        callbacks: {
                            label: (c) => `${c.dataset.label} : ${serie === 'valeur' ? fcfa(c.parsed.y) : nb(c.parsed.y, 2)}`,
                        },
                    },
                },
            },
        });
    };

    const rendreClassement = (id, items, principal, secondaire, couleur) => {
        const cible = document.getElementById(id);
        if (!items.length) {
            cible.innerHTML = '<li class="list-group-item text-center text-muted py-4">Aucune donnée sur la période.</li>';
            return;
        }
        const max = Math.max(...items.map(principal));
        cible.innerHTML = items.map((item, index) => `
            <li class="list-group-item py-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-semibold text-truncate" style="max-width: 65%;">
                        <span class="badge bg-${couleur}-subtle text-${couleur}-emphasis me-2">${index + 1}</span>${item.libelle}
                    </span>
                    <span class="small fw-bold">${secondaire(item)}</span>
                </div>
                <div class="barre-part">
                    <span style="width: ${max > 0 ? Math.round(principal(item) * 100 / max) : 0}%; background: ${PALETTE[index % PALETTE.length]};"></span>
                </div>
            </li>`).join('');
    };

    const rendre = () => {
        const s = donnees.synthese;
        const q = donnees.qualite_saisie;

        document.getElementById('kpi-valeur').textContent = fcfa(s.valeur_stock);
        document.getElementById('kpi-valeur-sous').textContent = `${nb(s.references_en_stock)} référence(s) valorisée(s)`;
        document.getElementById('kpi-references').textContent = nb(s.references_en_stock);
        document.getElementById('kpi-references-sous').textContent = `${nb(s.references_total)} suivie(s) au total`;
        document.getElementById('kpi-equipements').textContent = nb(s.equipements);
        document.getElementById('kpi-equipements-sous').textContent = 'unités sérialisées';
        document.getElementById('kpi-dispo').textContent = `${nb(s.taux_disponibilite, 1)} %`;
        document.getElementById('kpi-dispo-sous').textContent = `${nb(s.ruptures)} rupture(s)`;

        document.getElementById('kpi-conso').textContent = fcfa(s.consommation_mensuelle);
        document.getElementById('kpi-conso-sous').textContent = `${nb(s.ecritures)} écriture(s) sur la fenêtre`;
        document.getElementById('kpi-rotation').textContent = s.rotation_annuelle === null ? '—' : `${nb(s.rotation_annuelle, 2)} ×`;
        document.getElementById('kpi-rotation-sous').textContent = 'consommation / stock, annualisée';
        document.getElementById('kpi-couverture').textContent = s.couverture_mois === null ? '—' : `${nb(s.couverture_mois, 1)} mois`;
        document.getElementById('kpi-couverture-sous').textContent = 'au rythme de consommation actuel';
        document.getElementById('kpi-alertes').textContent = nb(s.ruptures + s.sous_seuil);
        document.getElementById('kpi-alertes-sous').textContent = `${nb(s.taux_alerte, 1)} % des références`;

        const p = donnees.parametres;
        document.getElementById('periode-analysee').innerHTML =
            `<i class="bi bi-calendar3 me-1"></i>Fenêtre analysée : <strong>${new Date(p.debut).toLocaleDateString('fr-FR')}</strong>
             → <strong>${new Date(p.fin).toLocaleDateString('fr-FR')}</strong> (${p.mois} mois)`;

        rendreFlux();

        dessiner('graphique-sante', {
            type: 'polarArea',
            data: {
                labels: donnees.sante_alertes.map((i) => i.libelle),
                datasets: [{ data: donnees.sante_alertes.map((i) => i.total), backgroundColor: ['#198754', '#ffc107', '#dc3545'] }],
            },
            options: optionsCommunes,
        });

        dessiner('graphique-magasins', {
            type: 'bar',
            data: {
                labels: donnees.valeur_par_magasin.map((i) => i.libelle),
                datasets: [{ label: 'Valeur', data: donnees.valeur_par_magasin.map((i) => i.valeur), backgroundColor: '#0d6efd' }],
            },
            options: {
                ...optionsCommunes,
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => fcfa(c.parsed.x) } } },
                scales: { x: { beginAtZero: true, ticks: { callback: (v) => nb(v) } } },
            },
        });

        donutValeur('graphique-natures', donnees.valeur_par_nature);
        donutValeur('graphique-categories', donnees.top_categories);

        dessiner('graphique-motifs', {
            type: 'pie',
            data: {
                labels: donnees.motifs_sortie.map((i) => i.libelle),
                datasets: [{ data: donnees.motifs_sortie.map((i) => i.total), backgroundColor: PALETTE }],
            },
            options: optionsCommunes,
        });

        rendreClassement('liste-articles', donnees.top_articles_sortis,
            (i) => i.quantite, (i) => `${nb(i.quantite, 2)} ${i.unite || ''}`, 'warning');

        rendreClassement('liste-beneficiaires', donnees.top_beneficiaires,
            (i) => i.valeur, (i) => `${fcfa(i.valeur)} · ${i.nb_bons} bon(s)`, 'primary');

        rendreClassement('liste-utilisateurs', donnees.activite_utilisateurs,
            (i) => i.total, (i) => `${nb(i.total)} écriture(s)`, 'secondary');

        const qualite = [
            ['Entrées non validées', q.entrees_non_validees, q.entrees_non_validees > 0 ? 'warning' : 'success'],
            ['Sorties non validées', q.sorties_non_validees, q.sorties_non_validees > 0 ? 'warning' : 'success'],
            ['Transferts non validés', q.transferts_non_valides, q.transferts_non_valides > 0 ? 'warning' : 'success'],
            [`Bons figés depuis + de ${q.jours_alerte} j`, q.anciens, q.anciens > 0 ? 'danger' : 'success'],
            ['Contre-mouvements (corrections)', q.contre_mouvements, 'secondary'],
            ['Délai moyen de validation',
                q.delai_validation_heures === null ? '—' : `${nb(q.delai_validation_heures, 1)} h`, 'info'],
        ];
        document.getElementById('liste-qualite').innerHTML = qualite.map(([libelle, valeur, couleur]) => `
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span class="small">${libelle}</span>
                <span class="badge bg-${couleur} ${couleur === 'warning' ? 'text-dark' : ''}">${typeof valeur === 'number' ? nb(valeur) : valeur}</span>
            </li>`).join('');
    };

    const charger = () => {
        document.getElementById('stats-chargement').classList.remove('d-none');
        document.getElementById('stats-contenu').classList.add('d-none');

        const p = new URLSearchParams();
        const magasin = document.getElementById('filtre-magasin').value;
        if (magasin) p.set('magasin_id', magasin);
        p.set('mois', document.getElementById('filtre-mois').value);

        fetch(`${URL_DATA}?${p.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.json())
            .then((res) => {
                donnees = res.data;
                document.getElementById('stats-chargement').classList.add('d-none');
                document.getElementById('stats-contenu').classList.remove('d-none');
                rendre();
            })
            .catch(() => {
                document.getElementById('stats-chargement').innerHTML =
                    '<div class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Impossible de calculer les statistiques du stock.</div>';
            });
    };

    document.getElementById('btn-actualiser').addEventListener('click', charger);
    document.getElementById('filtre-magasin').addEventListener('change', charger);
    document.getElementById('filtre-mois').addEventListener('change', charger);
    document.querySelectorAll('input[name="serie"]').forEach((radio) =>
        radio.addEventListener('change', () => donnees && rendreFlux()));

    charger();
})();
</script>
@endpush
