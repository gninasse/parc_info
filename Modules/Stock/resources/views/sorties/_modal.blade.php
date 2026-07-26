{{-- sorties/_modal.blade.php --}}

<!-- Modal Créer un Bon de Sortie -->
<div class="modal fade" id="sortieModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-arrow-circle-right me-2 text-danger"></i>Nouveau bon de sortie de stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sortie-form" novalidate>
                @csrf
                <div class="modal-body small">
                    <div class="row g-3">
                        <!-- Magasin & Article -->
                        <div class="col-md-6">
                            <label for="s-magasin" class="form-label fw-semibold">Magasin Source <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="s-magasin" name="magasin_id" required>
                                <option value="">Sélectionner le magasin...</option>
                                @foreach($magasins as $m)
                                    <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="s-article" class="form-label fw-semibold">Article <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="s-article" name="article_id" required>
                                <option value="">Sélectionner un article...</option>
                                @foreach($articles as $a)
                                    <option value="{{ $a->id }}">{{ $a->designation }} ({{ $a->code_article }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quantité & Référence document -->
                        <div class="col-md-4">
                            <label for="s-quantite" class="form-label fw-semibold">Quantité à sortir <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="s-quantite" name="quantite" required min="1" value="1">
                        </div>
                        <div class="col-md-8">
                            <label for="s-ref-doc" class="form-label fw-semibold">Référence document d'origine</label>
                            <input type="text" class="form-control form-control-sm" id="s-ref-doc" name="reference_document" placeholder="Ex: DA-2026-0034, Ordre de sortie #12...">
                        </div>

                        <!-- Type d'affectation & Type de cible -->
                        <div class="col-md-6 border-top pt-3">
                            <label for="s-type-aff" class="form-label fw-semibold text-danger">Type d'affectation (ParcInfo) <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="s-type-aff" name="type_affectation_parcinfo" required>
                                <option value="CONSOMMABLE">Consommable (Ravitaillement)</option>
                                <option value="EQUIPEMENT">Équipement (Physique)</option>
                                <option value="LICENCE">Licence logicielle</option>
                            </select>
                        </div>
                        <div class="col-md-6 border-top pt-3">
                            <label for="s-type-cible" class="form-label fw-semibold">Type de bénéficiaire <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="s-type-cible" name="type_cible" required>
                                <option value="EMPLOYE">Employé (Individuel)</option>
                                <option value="SERVICE">Service</option>
                                <option value="DIRECTION">Direction</option>
                                <option value="UNITE">Unité</option>
                            </select>
                        </div>

                        <!-- Sélection dynamique du bénéficiaire -->
                        <div class="col-md-12" id="wrapper-cible-employe">
                            <label for="s-cible-emp" class="form-label fw-semibold">Employé bénéficiaire <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm select-cible-field" id="s-cible-emp" data-type="EMPLOYE">
                                <option value="">Sélectionner un employé...</option>
                                @foreach($employes as $e)
                                    <option value="{{ $e->id }}">{{ $e->nom }} {{ $e->prenom }} ({{ $e->matricule }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="wrapper-cible-service">
                            <label for="s-cible-srv" class="form-label fw-semibold">Service bénéficiaire <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm select-cible-field" id="s-cible-srv" data-type="SERVICE">
                                <option value="">Sélectionner un service...</option>
                                @foreach($services as $s)
                                    <option value="{{ $s->id }}">{{ $s->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="wrapper-cible-direction">
                            <label for="s-cible-dir" class="form-label fw-semibold">Direction bénéficiaire <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm select-cible-field" id="s-cible-dir" data-type="DIRECTION">
                                <option value="">Sélectionner une direction...</option>
                                @foreach($directions as $d)
                                    <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="wrapper-cible-unite">
                            <label for="s-cible-uni" class="form-label fw-semibold">Unité bénéficiaire <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm select-cible-field" id="s-cible-uni" data-type="UNITE">
                                <option value="">Sélectionner une unité...</option>
                                @foreach($unites as $u)
                                    <option value="{{ $u->id }}">{{ $u->libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Champ caché pour cible_id soumis -->
                        <input type="hidden" id="s-cible-id" name="cible_id">

                        <!-- Sélection dynamique de l'actif physique (si applicable) -->
                        <div class="col-md-12 d-none border-top pt-3" id="wrapper-asset-equipement">
                            <label for="s-asset-eq" class="form-label fw-semibold text-danger">Équipement physique en stock <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="s-asset-eq" name="equipement_id">
                                <option value="">Sélectionner l'équipement physique...</option>
                                @foreach($equipements as $eq)
                                    <option value="{{ $eq->id }}">{{ $eq->modele }} - S/N : {{ $eq->numero_serie }} (Inventaire: {{ $eq->code_inventaire }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-none border-top pt-3" id="wrapper-asset-licence">
                            <label for="s-asset-lic" class="form-label fw-semibold text-danger">Licence logicielle <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="s-asset-lic" name="licence_id">
                                <option value="">Sélectionner la licence...</option>
                                @foreach($licences as $lic)
                                    <option value="{{ $lic->id }}">{{ $lic->logiciel?->nom }} - Clé: {{ $lic->cle_licence }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Motif -->
                        <div class="col-12 border-top pt-3">
                            <label for="s-motif" class="form-label fw-semibold">Motif de la sortie <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="s-motif" name="motif" rows="3" required placeholder="Expliquez la raison de cette sortie..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger btn-sm text-white rounded-1" id="btn-save-sortie-submit">
                        <i class="fas fa-save me-1"></i> Valider la sortie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
