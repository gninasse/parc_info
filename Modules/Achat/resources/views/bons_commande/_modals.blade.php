{{-- Sélection d'un fournisseur (M-03) --}}
<div class="modal fade" id="modal-fournisseur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="fas fa-truck me-2 text-primary"></i>Sélectionner un fournisseur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="recherche-fournisseur" class="form-control bg-light border-start-0"
                           placeholder="Rechercher par code, nom ou ville…">
                </div>

                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0 border-top">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 20%;">Code</th>
                                <th style="width: 50%;">Nom</th>
                                <th style="width: 30%;">Ville</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fournisseurs as $fournisseur)
                                <tr class="ligne-fournisseur" style="cursor: pointer"
                                    data-id="{{ $fournisseur->id }}"
                                    data-nom="{{ $fournisseur->nom }}"
                                    data-code="{{ $fournisseur->code }}"
                                    data-recherche="{{ mb_strtolower($fournisseur->code.' '.$fournisseur->nom.' '.($fournisseur->ville ?? '')) }}">
                                    <td><span class="badge bg-secondary font-monospace">{{ $fournisseur->code }}</span></td>
                                    <td class="fw-bold text-dark">{{ $fournisseur->nom }}</td>
                                    <td class="text-muted small">{{ $fournisseur->ville ?? '-' }}</td>
                                </tr>
                            @empty
                                <x-achat-etat-vide message="Aucun fournisseur actif référencé." :colspan="3" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

{{-- Sélection d'un article (M-04) --}}
<div class="modal fade" id="modal-article" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="fas fa-box me-2 text-primary"></i>Sélectionner un article
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="recherche-article" class="form-control bg-light border-start-0"
                                   placeholder="Rechercher par code ou désignation…">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="filtre-type-article" class="form-select form-select-sm bg-light">
                            <option value="">Tous les types</option>
                            @foreach(config('achat.types_articles') as $code => $libelle)
                                <option value="{{ $code }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0 border-top" id="table-articles">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 22%;">Code</th>
                                <th style="width: 40%;">Désignation</th>
                                <th style="width: 16%;">Type</th>
                                <th style="width: 10%;" class="text-center">TVA</th>
                                <th style="width: 12%;" class="text-end">Prix indicatif</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Alimenté par le script à l'ouverture, pour cibler la bonne ligne. --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
