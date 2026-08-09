{{--
    M-01 — Sélection d'articles, mode MULTI (SPEC_UX, répertoire des modales).

    « La modale article Stock en mode multi » (UX2-04) : mêmes colonnes, mêmes
    badges de nature venant du Catalogue, mais avec des cases à cocher et une
    sélection PERSISTANTE entre deux recherches — c'est le point qui la rend
    utilisable : on cherche « toner », on coche, on cherche « Dell », on coche,
    et les deux restent sélectionnés.
--}}
<div class="modal fade" id="modal-articles" tabindex="-1" aria-labelledby="titre-modal-articles" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titre-modal-articles">
                    <i class="bi bi-box-seam me-2"></i>Sélectionner des articles
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <label class="visually-hidden" for="ma-recherche">Rechercher un article</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" class="form-control" id="ma-recherche"
                                   placeholder="Code ou désignation…" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="visually-hidden" for="ma-nature">Nature</label>
                        <select class="form-select form-select-sm" id="ma-nature">
                            <option value="">Toutes les natures</option>
                            <option value="equipement">E — Équipements</option>
                            <option value="consommable">C — Consommables</option>
                            <option value="piece">P — Pièces</option>
                            <option value="licence">L — Licences</option>
                            <option value="prestation">S — Prestations</option>
                        </select>
                    </div>
                </div>

                {{-- Le tri « articles du fournisseur de la commande d'abord »
                     est appliqué par l'API Catalogue (fournisseur_prefere_id) ;
                     l'étoile ci-dessous le rend visible. --}}
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="ma-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:3rem;"></th>
                                <th style="width:3rem;">Nat.</th>
                                <th>Code</th>
                                <th>Désignation</th>
                                <th>Unité</th>
                                <th class="text-end">Prix indicatif</th>
                                <th class="text-center">TVA</th>
                            </tr>
                        </thead>
                        <tbody id="ma-corps"></tbody>
                    </table>
                </div>

                <div id="ma-vide" class="text-center text-muted py-4 d-none">
                    <i class="bi bi-search fs-3"></i>
                    <p class="mb-0 mt-2">Aucun article ne correspond à cette recherche.</p>
                </div>

                <div id="ma-chargement" class="text-center text-muted py-4 d-none">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <span class="ms-2">Chargement…</span>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <div class="small">
                    <span id="ma-compteur">0 article(s) sélectionné(s)</span>
                    <div class="text-muted">
                        {{-- Lien D7 : aucune création implicite d'article
                             depuis Achat. On envoie l'utilisateur au Catalogue
                             dans un nouvel onglet — son brouillon l'attend. --}}
                        L'article n'existe pas ?
                        <a href="{{ route('catalogue.articles.index') }}" target="_blank" rel="noopener">
                            Créez-le dans le module Catalogue →
                        </a>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary btn-sm" id="ma-ajouter" disabled>
                        Ajouter au bon
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
