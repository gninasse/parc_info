{{--
    M-09 — « Rattacher des équipements » (SPEC_UX §répertoire des modales).

    Plein écran (comme M-01/M-03/M-05) : la liste peut être longue, et le
    magasinier compare des numéros de série. Cases à cocher (multi-sélection),
    compteur, et le décrément de la DETTE affiché en permanence — c'est la
    seule façon de voir qu'on avance vers l'extinction (UX-17).

    Source : GET /achat/regularisation/equipements-candidats — les
    équipements SANS commande d'origine, ni rattachement ni chaîne Stock.
--}}
<div class="modal fade" id="modal-rattachement" tabindex="-1" aria-labelledby="modal-rattachement-titre" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-rattachement-titre">
                    <span class="pictogramme-regularisation me-1" aria-hidden="true"></span>
                    Rattacher des équipements — {{ $bon->numero_affiche }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body"
                 id="corps-rattachement"
                 data-url-candidats="{{ route('achat.regularisation.candidats') }}"
                 data-url-rattacher="{{ route('achat.regularisation.rattacher', $bon->id) }}">

                <div class="alert alert-info d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <div class="flex-grow-1">
                        Ces équipements sont entrés au parc sans bon de commande.
                        Les rattacher à ce bon documente leur origine.
                    </div>
                    <span class="badge bg-secondary" id="badge-dette">Dette : —</span>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-sm" id="rat-recherche"
                               placeholder="Code d'inventaire, n° de série, modèle…" autocomplete="off">
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="small text-muted" id="rat-compteur">0 sélectionné(s)</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">
                                    <input type="checkbox" class="form-check-input" id="rat-tout"
                                           aria-label="Tout sélectionner">
                                </th>
                                <th>Code inventaire</th>
                                <th>Modèle</th>
                                <th>N° de série</th>
                                <th>Acquis le</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="rat-liste" style="cursor:pointer;"></tbody>
                    </table>
                </div>

                <p class="text-muted small d-none" id="rat-vide">
                    Aucun équipement sans origine pour cette recherche.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary btn-sm" id="rat-confirmer" disabled>
                    <i class="bi bi-link-45deg me-1"></i>Rattacher (<span id="rat-nombre">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>
