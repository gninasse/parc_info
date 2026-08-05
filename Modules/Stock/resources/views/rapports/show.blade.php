@extends('stock::layouts.master')

@section('header', $definition['titre'])

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard') }}">Stock</a></li>
    <li class="breadcrumb-item"><a href="{{ route('stock.rapports.index') }}">États & rapports</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $definition['titre'] }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .carte-resume { border-left: 4px solid var(--bs-primary); }
    .carte-resume .valeur { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    #rapport-table tfoot td { font-weight: 700; background: var(--bs-tertiary-bg); }
    .filtre-actif { font-size: .8rem; }
</style>
@endpush

@section('content')

{{-- Bandeau : intention de l'état + navigation vers les autres états --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi {{ $definition['icone'] }} me-2 text-primary"></i>{{ $definition['titre'] }}</h5>
            <p class="text-muted small mb-0">{{ $definition['intention'] }}</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-collection me-1"></i>Autre état
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach($autres as $famille => $etats)
                        <li><h6 class="dropdown-header">{{ $famille }}</h6></li>
                        @foreach($etats as $autre)
                            <li>
                                <a class="dropdown-item {{ $autre['code'] === $code ? 'active' : '' }}"
                                   href="{{ route('stock.rapports.show', $autre['code']) }}">
                                    <i class="bi {{ $autre['icone'] }} me-2"></i>{{ $autre['titre'] }}
                                </a>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
            @can('stock.rapports.export')
            <div class="btn-group">
                <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i>Exporter
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item export-lien" href="#" data-format="csv"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                    <li><a class="dropdown-item export-lien" href="#" data-format="xlsx"><i class="bi bi-file-earmark-excel me-2"></i>Excel</a></li>
                    <li><a class="dropdown-item export-lien" href="#" data-format="pdf"><i class="bi bi-file-earmark-pdf me-2"></i>PDF</a></li>
                </ul>
            </div>
            @endcan
        </div>
    </div>
</div>

{{-- Filtres : seuls ceux qui ont un sens pour l'état courant sont rendus --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end" id="zone-filtres">
            @if(in_array('periode', $definition['filtres'], true))
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted" for="filtre-date-debut">Du</label>
                    <input type="date" id="filtre-date-debut" class="form-control form-control-sm"
                           value="{{ $filtres['date_debut'] ?? now()->subDays(30)->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted" for="filtre-date-fin">Au</label>
                    <input type="date" id="filtre-date-fin" class="form-control form-control-sm"
                           value="{{ $filtres['date_fin'] ?? now()->toDateString() }}">
                </div>
            @endif

            @if(in_array('magasin_id', $definition['filtres'], true))
                <div class="col-md-3">
                    <label class="form-label small text-uppercase text-muted" for="filtre-magasin">Magasin</label>
                    <select id="filtre-magasin" class="form-select form-select-sm">
                        <option value="">Tous les magasins</option>
                        @foreach($magasins as $magasin)
                            <option value="{{ $magasin->id }}" @selected(($filtres['magasin_id'] ?? null) == $magasin->id)>{{ $magasin->libelle }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(in_array('nature', $definition['filtres'], true))
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted" for="filtre-nature">Nature</label>
                    <select id="filtre-nature" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach($natures as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(($filtres['nature'] ?? null) === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(in_array('categorie_id', $definition['filtres'], true))
                <div class="col-md-3">
                    <label class="form-label small text-uppercase text-muted" for="filtre-categorie">Catégorie</label>
                    <select id="filtre-categorie" class="form-select form-select-sm">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $categorie)
                            <option value="{{ $categorie->id }}" @selected(($filtres['categorie_id'] ?? null) == $categorie->id)>{{ $categorie->libelle }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(in_array('type', $definition['filtres'], true))
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted" for="filtre-type">Type</label>
                    <select id="filtre-type" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach($typesMouvement as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(($filtres['type'] ?? null) === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(in_array('statut_document', $definition['filtres'], true) && count($statutsDocument))
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted" for="filtre-statut">Statut</label>
                    <select id="filtre-statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach($statutsDocument as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(($filtres['statut_document'] ?? null) === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(in_array('motif_type', $definition['filtres'], true))
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted" for="filtre-motif">Motif</label>
                    <select id="filtre-motif" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach($motifsSortie as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(($filtres['motif_type'] ?? null) === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-2 d-flex gap-2">
                <button id="btn-appliquer" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i>Appliquer
                </button>
                <button id="btn-reinitialiser" class="btn btn-outline-secondary btn-sm" title="Réinitialiser les filtres">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>

        <div id="filtres-actifs" class="d-flex flex-wrap gap-2 mt-3"></div>
    </div>
</div>

{{-- Résumé de l'état (cartes calculées côté serveur) --}}
<div class="row g-3 mb-3" id="zone-resume"></div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Détail de l'état</h6>
        <span class="text-muted small" id="compteur-lignes"></span>
    </div>
    <div class="card-body p-0">
        <div id="rapport-chargement" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement…</span></div>
            <div class="mt-2 text-muted small">Agrégation des données en cours…</div>
        </div>
        <div class="table-responsive">
            <table id="rapport-table" class="table table-hover table-sm align-middle mb-0 d-none">
                <thead class="table-light"><tr></tr></thead>
                <tbody></tbody>
                <tfoot></tfoot>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0" for="taille-page">Lignes</label>
            <select id="taille-page" class="form-select form-select-sm" style="width: auto;">
                <option>25</option><option>50</option><option>100</option><option value="0">Toutes</option>
            </select>
        </div>
        <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
    </div>
</div>

@endsection

@push('js')
<script>
window.RAPPORT = {
    code: @json($code),
    urlData: @json(route('stock.rapports.data', $code)),
    urlExport: @json(route('stock.rapports.export', $code)),
    filtresDisponibles: @json($definition['filtres']),
};
</script>
<script>
(function () {
    const config = window.RAPPORT;
    const $table = document.getElementById('rapport-table');
    const $entete = $table.querySelector('thead tr');
    const $corps = $table.querySelector('tbody');
    const $pied = $table.querySelector('tfoot');
    let page = 1;
    let taille = 25;

    const nombre = (v, decimales = 0) => v === null || v === undefined || v === ''
        ? '—'
        : new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: decimales }).format(v);

    const dateFr = (v, avecHeure) => {
        if (!v) return '—';
        const d = new Date(String(v).replace(' ', 'T'));
        if (isNaN(d)) return v;
        return avecHeure ? d.toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })
                         : d.toLocaleDateString('fr-FR');
    };

    const formater = (valeur, type) => {
        // Un libellé de ligne de totaux peut tomber sur une colonne typée :
        // on le laisse tel quel plutôt que de produire « NaN » ou « Invalid Date ».
        const numerique = ['montant', 'decimal', 'nombre'].includes(type);
        if (numerique && valeur !== null && valeur !== '' && isNaN(valeur)) return valeur;
        if (['date', 'datetime'].includes(type) && valeur && isNaN(new Date(String(valeur).replace(' ', 'T')))) return valeur;

        switch (type) {
            case 'montant': return nombre(valeur, 0);
            case 'decimal': return valeur === null ? '—' : nombre(valeur, 2);
            case 'nombre': return valeur === null ? '—' : nombre(valeur, 0);
            case 'date': return dateFr(valeur, false);
            case 'datetime': return dateFr(valeur, true);
            case 'badge': return valeur ? `<span class="badge bg-secondary-subtle text-secondary-emphasis">${valeur}</span>` : '—';
            default: return valeur === null || valeur === '' ? '—' : valeur;
        }
    };

    const alignement = (type) => ['montant', 'decimal', 'nombre'].includes(type) ? 'text-end' : '';

    const parametres = () => {
        const p = new URLSearchParams();
        const lire = (id) => document.getElementById(id)?.value ?? '';
        const champs = {
            'filtre-date-debut': 'date_debut',
            'filtre-date-fin': 'date_fin',
            'filtre-magasin': 'magasin_id',
            'filtre-nature': 'nature',
            'filtre-categorie': 'categorie_id',
            'filtre-type': 'type',
            'filtre-statut': 'statut_document',
            'filtre-motif': 'motif_type',
        };
        Object.entries(champs).forEach(([id, cle]) => {
            const valeur = lire(id);
            if (valeur) p.set(cle, valeur);
        });
        return p;
    };

    const rendreFiltresActifs = (filtres) => {
        const zone = document.getElementById('filtres-actifs');
        zone.innerHTML = Object.entries(filtres)
            .map(([libelle, valeur]) =>
                `<span class="badge rounded-pill bg-light text-body border filtre-actif">
                    <i class="bi bi-funnel me-1"></i><strong>${libelle}</strong> : ${valeur}</span>`)
            .join('');
    };

    const rendreResume = (resume) => {
        document.getElementById('zone-resume').innerHTML = resume.map((item) => `
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 carte-resume">
                    <div class="card-body py-3">
                        <div class="small text-uppercase text-muted mb-1">${item.libelle}</div>
                        <div class="valeur">${item.valeur}</div>
                    </div>
                </div>
            </div>`).join('');
    };

    const rendreTableau = (res) => {
        $entete.innerHTML = res.colonnes
            .map((c) => `<th class="${alignement(c.type)}">${c.libelle}</th>`).join('');

        $corps.innerHTML = res.rows.length === 0
            ? `<tr><td colspan="${res.colonnes.length}" class="text-center text-muted py-4">
                 <i class="bi bi-inbox fs-3 d-block mb-2"></i>Aucune donnée pour ces filtres.</td></tr>`
            : res.rows.map((ligne) => '<tr>' + res.colonnes
                .map((c) => `<td class="${alignement(c.type)}">${formater(ligne[c.cle] ?? null, c.type)}</td>`)
                .join('') + '</tr>').join('');

        $pied.innerHTML = Object.keys(res.totaux || {}).length === 0 || res.rows.length === 0
            ? ''
            : '<tr>' + res.colonnes.map((c) => {
                const valeur = res.totaux[c.cle];
                return `<td class="${alignement(c.type)}">${valeur === undefined ? '' : formater(valeur, c.type)}</td>`;
              }).join('') + '</tr>';

        $table.classList.remove('d-none');
    };

    const rendrePagination = (total) => {
        const $pagination = document.getElementById('pagination');
        if (taille === 0 || total <= taille) { $pagination.innerHTML = ''; return; }
        const pages = Math.ceil(total / taille);
        const boutons = [];
        const ajouter = (numero, libelle, desactive) => boutons.push(
            `<li class="page-item ${desactive ? 'disabled' : ''} ${numero === page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${numero}">${libelle ?? numero}</a></li>`);
        ajouter(page - 1, '«', page === 1);
        const debut = Math.max(1, page - 2);
        for (let i = debut; i <= Math.min(pages, debut + 4); i++) ajouter(i);
        ajouter(page + 1, '»', page === pages);
        $pagination.innerHTML = boutons.join('');
        $pagination.querySelectorAll('a').forEach((a) => a.addEventListener('click', (e) => {
            e.preventDefault();
            const cible = parseInt(a.dataset.page, 10);
            if (cible >= 1 && cible <= pages) { page = cible; charger(); }
        }));
    };

    const charger = () => {
        majLiensExport();
        document.getElementById('rapport-chargement').classList.remove('d-none');
        $table.classList.add('d-none');

        const p = parametres();
        p.set('limit', taille);
        p.set('offset', taille === 0 ? 0 : (page - 1) * taille);

        fetch(`${config.urlData}?${p.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.json())
            .then((res) => {
                document.getElementById('rapport-chargement').classList.add('d-none');
                rendreFiltresActifs(res.filtres_actifs || {});
                rendreResume(res.resume || []);
                rendreTableau(res);
                rendrePagination(res.total);
                document.getElementById('compteur-lignes').textContent =
                    `${new Intl.NumberFormat('fr-FR').format(res.total)} ligne(s)`;
            })
            .catch(() => {
                document.getElementById('rapport-chargement').innerHTML =
                    '<div class="text-danger"><i class="bi bi-x-circle me-1"></i>Impossible de charger cet état.</div>';
            });
    };

    document.getElementById('btn-appliquer').addEventListener('click', () => { page = 1; charger(); });

    document.getElementById('btn-reinitialiser').addEventListener('click', () => {
        document.querySelectorAll('#zone-filtres select').forEach((s) => { s.value = ''; });
        const debut = document.getElementById('filtre-date-debut');
        const fin = document.getElementById('filtre-date-fin');
        if (debut) debut.value = new Date(Date.now() - 30 * 864e5).toISOString().slice(0, 10);
        if (fin) fin.value = new Date().toISOString().slice(0, 10);
        page = 1;
        charger();
    });

    document.getElementById('taille-page').addEventListener('change', (e) => {
        taille = parseInt(e.target.value, 10);
        page = 1;
        charger();
    });

    // Les liens d'export restent de vrais liens : leur href suit les filtres
    // courants, ce qui préserve le clic milieu et « ouvrir dans un onglet ».
    const majLiensExport = () => {
        document.querySelectorAll('.export-lien').forEach((lien) => {
            const p = parametres();
            p.set('format', lien.dataset.format);
            lien.href = `${config.urlExport}?${p.toString()}`;
        });
    };

    // Un filtre modifié met immédiatement les liens d'export à jour, même si
    // l'utilisateur n'a pas encore cliqué sur « Appliquer ».
    document.querySelectorAll('#zone-filtres select, #zone-filtres input')
        .forEach((champ) => champ.addEventListener('change', majLiensExport));

    charger();
})();
</script>
@endpush
