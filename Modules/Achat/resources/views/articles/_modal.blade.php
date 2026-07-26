{{-- Formulaire de référencement d'un article (M-01) --}}
<div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="item-modal-label"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-2 border-0">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6" id="item-modal-label">
                    <i class="fas fa-cube me-2 text-primary"></i><span id="item-modal-action">Nouvel</span> article
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="item-form" autocomplete="off" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" id="item-id" name="id">

                <div class="modal-body py-3">
                    <ul class="nav nav-tabs border-bottom mb-3" id="item-modal-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-medium py-2" id="tab-btn-general"
                                    data-bs-toggle="tab" data-bs-target="#tab-general" type="button"
                                    role="tab" aria-controls="tab-general" aria-selected="true">
                                <i class="fas fa-info-circle me-1"></i> Général
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-medium py-2" id="tab-btn-specs"
                                    data-bs-toggle="tab" data-bs-target="#tab-specs" type="button"
                                    role="tab" aria-controls="tab-specs" aria-selected="false">
                                <i class="fas fa-sliders-h me-1"></i> Caractéristiques &amp; stock
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- Onglet 1 : général --}}
                        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="type_article">
                                        Type d'article <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-sm" name="type_article" id="type_article" required>
                                        @foreach(config('achat.types_articles') as $code => $libelle)
                                            <option value="{{ $code }}">{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="code_article">
                                        Code article <span class="text-muted">(facultatif)</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm text-uppercase"
                                           name="code_article" id="code_article"
                                           placeholder="Généré automatiquement si laissé vide">
                                    {{-- EF-CAT-13 : le code est réellement généré par le service. --}}
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="designation">
                                        Désignation <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm"
                                           name="designation" id="designation" required
                                           placeholder="Désignation commerciale de l'article">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="marque_id">
                                        Marque <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-sm" name="marque_id" id="marque_id" required>
                                        <option value="">Sélectionner une marque</option>
                                        @foreach($marques as $marque)
                                            <option value="{{ $marque->id }}">{{ $marque->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="reference_constructeur">
                                        Référence constructeur
                                    </label>
                                    <input type="text" class="form-control form-control-sm"
                                           name="reference_constructeur" id="reference_constructeur"
                                           placeholder="Unique pour une même marque">
                                </div>

                                <div class="col-md-6 d-none" id="group-categorie">
                                    <label class="form-label small fw-semibold" for="categorie_equipement_id">
                                        Catégorie d'équipement <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-sm"
                                            name="categorie_equipement_id" id="categorie_equipement_id">
                                        <option value="">Sélectionner une catégorie</option>
                                        @foreach($categories as $categorie)
                                            <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="fournisseur_prefere_id">
                                        Fournisseur préféré
                                    </label>
                                    <select class="form-select form-select-sm"
                                            name="fournisseur_prefere_id" id="fournisseur_prefere_id">
                                        <option value="">Aucun</option>
                                        @foreach($fournisseurs as $fournisseur)
                                            <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="prix_indicatif">
                                        Prix indicatif (FCFA)
                                    </label>
                                    <input type="number" min="0" step="1" class="form-control form-control-sm"
                                           name="prix_indicatif" id="prix_indicatif" value="0">
                                </div>
                            </div>
                        </div>

                        {{-- Onglet 2 : caractéristiques et stock --}}
                        <div class="tab-pane fade" id="tab-specs" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="unite_mesure">
                                        Unité de mesure <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm"
                                           name="unite_mesure" id="unite_mesure" value="Unité" required
                                           placeholder="Unité, boîte, rouleau…">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="taux_tva">
                                        Taux de TVA (%) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="0" max="100" step="0.01"
                                           class="form-control form-control-sm"
                                           name="taux_tva" id="taux_tva"
                                           value="{{ config('achat.taux_tva_defaut', 18) }}" required>
                                    <div class="form-text" style="font-size:.7rem">
                                        Appliqué aux commandes de cet article.
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="compte_comptable">
                                        Compte comptable
                                    </label>
                                    <input type="text" class="form-control form-control-sm"
                                           name="compte_comptable" id="compte_comptable" placeholder="Ex : 601100">
                                </div>

                                <div class="col-md-6 d-none" id="group-seuil-alerte">
                                    <label class="form-label small fw-semibold" for="seuil_alerte">
                                        Seuil d'alerte de stock <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="0" class="form-control form-control-sm"
                                           name="seuil_alerte" id="seuil_alerte" placeholder="Ex : 5">
                                </div>

                                <div class="col-md-6 d-none" id="group-duree-validite">
                                    <label class="form-label small fw-semibold" for="duree_validite_mois">
                                        Durée de validité (mois) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="1" class="form-control form-control-sm"
                                           name="duree_validite_mois" id="duree_validite_mois" placeholder="Ex : 12">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="url_fiche_technique">
                                        Lien vers la fiche technique
                                    </label>
                                    <input type="url" class="form-control form-control-sm"
                                           name="url_fiche_technique" id="url_fiche_technique"
                                           placeholder="https://…">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="image">Photographie</label>
                                    <input type="file" class="form-control form-control-sm"
                                           name="image" id="image" accept="image/*">
                                    <div class="form-text" style="font-size:.7rem">Image de 2 Mo maximum.</div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="description">
                                        Description et spécifications
                                    </label>
                                    <textarea class="form-control form-control-sm" name="description"
                                              id="description" rows="3"
                                              placeholder="Informations complémentaires, détails techniques…"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-1" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-1" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
