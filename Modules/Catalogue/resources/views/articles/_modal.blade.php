{{-- Modale duale création/édition article — formulaire conditionnel à 4 natures --}}
<div class="modal fade" id="articleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="articleModalLabel">
                    <i class="fas fa-box me-2"></i><span id="modal-title-text">Nouvel article</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="article-form" novalidate>
                @csrf
                <input type="hidden" id="article-id" name="id">

                <div class="modal-body">

                    {{-- Choix de nature : 5 cartes radio, verrouillées en édition (C6) --}}
                    <div class="row g-2 mb-3" id="cartes-nature">
                        @foreach([
                            'consommable' => ['Consommable', 'fas fa-box-open', 'Consommé en quantités (toner, câble…)'],
                            'piece' => ['Pièce détachée', 'fas fa-cogs', 'Pièce avec compatibilités'],
                            'equipement' => ['Équipement', 'fas fa-desktop', 'Un modèle commandable ; les numéros de série seront créés à la réception'],
                            'licence' => ['Licence', 'fas fa-key', 'Produit logiciel commandable, non stocké'],
                            'prestation' => ['Prestation', 'fas fa-handshake', 'Service commandé (maintenance, formation…), soldé par un constat de service fait'],
                        ] as $nature => [$libelle, $icone, $aide])
                        <div class="col-md">
                            <label class="carte-nature d-block p-2 h-100 mb-0" data-nature-carte="{{ $nature }}">
                                <span class="coche-nature"><i class="fas fa-check-circle"></i></span>
                                <span class="cadenas-nature d-none position-absolute bottom-0 end-0 m-1 text-muted"
                                      data-bs-toggle="tooltip" title="Non modifiable après création">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="radio" class="d-none" name="nature" value="{{ $nature }}">
                                <span class="d-block fw-semibold"><i class="{{ $icone }} me-1"></i>{{ $libelle }}</span>
                                <span class="d-block small text-muted">{{ $aide }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>

                    {{-- Encart nature licence --}}
                    <div class="alert alert-info py-2 d-none" id="info-licence" data-nature="licence">
                        <i class="fas fa-info-circle me-1"></i>
                        Article non stocké : la réception créera directement les licences dans le parc.
                    </div>

                    {{-- Encart nature prestation (P0-A) --}}
                    <div class="alert alert-info py-2 d-none" id="info-prestation" data-nature="prestation">
                        <i class="fas fa-info-circle me-1"></i>
                        Service non stocké : la commande se solde par un constat de service fait
                        dans le module Achat — rien n'entre jamais en magasin.
                    </div>

                    {{-- Onglets --}}
                    <ul class="nav nav-tabs" id="article-onglets" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="onglet-general-tab" data-bs-toggle="tab" data-bs-target="#onglet-general" type="button" role="tab">Général</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="onglet-caracteristiques-tab" data-bs-toggle="tab" data-bs-target="#onglet-caracteristiques" type="button" role="tab">Caractéristiques &amp; seuils</button>
                        </li>
                    </ul>

                    <div class="tab-content border border-top-0 rounded-bottom p-3">
                        {{-- Onglet Général --}}
                        <div class="tab-pane fade show active" id="onglet-general" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="f-nom" class="form-label">Désignation <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="f-nom" name="nom" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="f-code" class="form-label">Code</label>
                                    <input type="text" class="form-control" id="f-code" name="code" placeholder="Généré selon la nature">
                                </div>
                                <div class="col-md-6">
                                    <label for="f-categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select class="form-select" id="f-categorie" name="categorie_id" required>
                                        <option value="">Sélectionnez…</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="f-fournisseur" class="form-label">Fournisseur préféré</label>
                                    <select class="form-select" id="f-fournisseur" name="fournisseur_principal_id">
                                        <option value="">Aucun</option>
                                        @foreach($fournisseurs as $f)
                                            <option value="{{ $f->id }}">{{ $f->raison_sociale }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="f-marque" class="form-label">Marque</label>
                                    <select class="form-select" id="f-marque" name="marque_id">
                                        <option value="">Aucune</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="f-modele" class="form-label">Modèle</label>
                                    <input type="text" class="form-control" id="f-modele" name="modele" placeholder="Latitude 3540, LaserJet Pro M404…">
                                </div>
                                <div class="col-md-4">
                                    <label for="f-reference" class="form-label">Référence constructeur</label>
                                    <input type="text" class="form-control" id="f-reference" name="reference_constructeur" placeholder="Unique par marque">
                                </div>
                                <div class="col-md-6">
                                    <label for="f-prix" class="form-label">Prix indicatif (FCFA)</label>
                                    <input type="number" class="form-control" id="f-prix" name="prix_indicatif" min="0" step="0.01">
                                </div>
                                <div class="col-md-6">
                                    <label for="f-tva" class="form-label">Taux de TVA (%)</label>
                                    <input type="number" class="form-control" id="f-tva" name="taux_tva" min="0" max="100" step="0.01" value="18">
                                </div>
                                {{--
                                    P0-B (PRQ-03) — l'imputation comptable. Format LIBRE en v1 :
                                    le plan comptable de l'établissement n'est pas arrêté dans
                                    l'application, et imposer un format reviendrait à choisir
                                    à la place du service financier.
                                --}}
                                <div class="col-md-6">
                                    <label for="f-compte" class="form-label">Compte comptable</label>
                                    <input type="text" class="form-control" id="f-compte" name="compte_comptable"
                                           maxlength="50" placeholder="ex. 6063">
                                    <div class="form-text">Plan comptable de l'établissement — facultatif.</div>
                                </div>
                            </div>
                        </div>

                        {{-- Onglet Caractéristiques & seuils — blocs conditionnels par nature --}}
                        <div class="tab-pane fade" id="onglet-caracteristiques" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-4" data-nature="consommable piece">
                                    <label for="f-unite" class="form-label">Unité de stock <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="f-unite" name="unite_stock" placeholder="unité, cartouche, boîte…">
                                </div>
                                <div class="col-md-4" data-nature="consommable piece">
                                    <label for="f-seuil" class="form-label">Seuil par défaut</label>
                                    <input type="number" class="form-control" id="f-seuil" name="seuil_defaut" min="0" step="0.01">
                                </div>
                                <div class="col-md-12" data-nature="consommable piece">
                                    <label for="f-compatibilites" class="form-label">Compatibilités (catégories d'équipements)</label>
                                    <select class="form-select" id="f-compatibilites" name="compatibilites[]" multiple></select>
                                </div>
                                <div class="col-md-6" data-nature="equipement">
                                    <label for="f-categorie-equipement" class="form-label">Catégorie d'équipements ParcInfo <span class="text-danger">*</span></label>
                                    <select class="form-select" id="f-categorie-equipement" name="categorie_equipement_id">
                                        <option value="">Sélectionnez…</option>
                                    </select>
                                    <div class="form-text">Détermine les champs des fiches créées à la sérialisation.</div>
                                </div>
                                <div class="col-md-6" data-nature="licence">
                                    <label for="f-logiciel" class="form-label">Logiciel du parc <span class="text-danger">*</span></label>
                                    <select class="form-select" id="f-logiciel" name="logiciel_id">
                                        <option value="">Sélectionnez…</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="f-notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="f-notes" name="notes" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
