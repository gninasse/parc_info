<div class="modal fade" id="modal-logiciel" tabindex="-1" aria-labelledby="modalLogicielLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary" id="modalLogicielLabel">
                    <i class="fas fa-compact-disc me-2"></i><span>Nouveau Logiciel</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-logiciel">
                @csrf
                <input type="hidden" name="id" id="logiciel-id">
                <div class="modal-body py-4">
                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Identification & Classification
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Code Logiciel <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="ex: MS-OFF-365" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: Microsoft Office 365 Business" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Éditeur <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="editeur_id" id="select-editeur" class="form-select select2-modal" required>
                                    <option value="">Choisir un éditeur...</option>
                                    @foreach($editeurs as $e)
                                        <option value="{{ $e->id }}">{{ $e->nom }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="btn-quickadd-editeur" title="Ajout rapide">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Type de licence par défaut <span class="text-danger">*</span></label>
                            <select name="type_licence_id" class="form-select" required>
                                <option value="">Choisir un type...</option>
                                @foreach($typesLicences as $t)
                                    <option value="{{ $t->id }}">{{ $t->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Catégorie</label>
                            <input type="text" name="categorie" class="form-control" placeholder="ex: Bureautique, Sécurité...">
                        </div>
                    </div>

                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-sticky-note me-2"></i>Informations complémentaires
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Description brève du logiciel..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Notes internes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Remarques, particularités..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-logiciel">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
