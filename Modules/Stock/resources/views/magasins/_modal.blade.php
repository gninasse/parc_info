{{-- _modal.blade.php --}}

<!-- Modal Magasin (Create/Edit) -->
<div class="modal fade" id="magasinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="magasinModalLabel">
                    <i class="fas fa-warehouse me-2 text-primary"></i><span id="modal-title-text">Nouveau magasin</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="magasin-form" novalidate>
                @csrf
                <input type="hidden" id="magasin-id" name="id">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="f-code" class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="f-code" name="code" required maxlength="30">
                        </div>
                        <div class="col-md-7">
                            <label for="f-nom" class="form-label small fw-semibold">Libellé <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="f-nom" name="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="f-type" class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="f-type" name="type" required>
                                <option value="TECHNIQUE">Technique</option>
                                <option value="CONSOMMABLE">Consommable</option>
                                <option value="REBUT">Rebut</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="f-actif" name="est_actif" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="f-actif">Magasin actif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="f-description" class="form-label small fw-semibold">Description</label>
                            <textarea class="form-control form-control-sm" id="f-description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-1" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Responsables -->
<div class="modal fade" id="responsiblesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-shield me-2 text-warning"></i>Gérer les responsables : <span id="resp-magasin-nom" class="text-secondary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <!-- Formulaire d'ajout -->
                    <div class="col-md-5 border-end">
                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary border-bottom pb-2">Affecter un responsable</h6>
                        <form id="responsible-form" novalidate>
                            @csrf
                            <input type="hidden" id="resp-magasin-id">
                            
                            <div class="mb-3">
                                <label for="r-employe" class="form-label small fw-semibold">Employé (GRH) <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="r-employe" name="employe_id" required>
                                    <option value="">Sélectionner un employé...</option>
                                    @foreach($employes as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->nom }} {{ $emp->prenom }} ({{ $emp->matricule }})</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="r-role" class="form-label small fw-semibold">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="r-role" name="role" required>
                                    <option value="principal">Principal (Un seul actif)</option>
                                    <option value="adjoint">Adjoint</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="r-debut" class="form-label small fw-semibold">Date de début <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="r-debut" name="date_debut" required value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="mb-3">
                                <label for="r-fin" class="form-label small fw-semibold">Date de fin</label>
                                <input type="date" class="form-control form-control-sm" id="r-fin" name="date_fin">
                            </div>
                            
                            <button type="submit" class="btn btn-warning text-white btn-sm rounded-1 w-100" id="btn-save-resp">
                                <i class="fas fa-plus me-1"></i> Ajouter le responsable
                            </button>
                        </form>
                    </div>
                    
                    <!-- Liste des responsables actuels -->
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary border-bottom pb-2">Responsables actuels et passés</h6>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Employé</th>
                                        <th>Rôle</th>
                                        <th>Période</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="responsibles-list" class="small">
                                    <!-- Rempli en JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Droits -->
<div class="modal fade" id="rightsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-lock me-2 text-secondary"></i>Gérer les droits d'accès : <span id="rights-magasin-nom" class="text-secondary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <!-- Formulaire d'ajout -->
                    <div class="col-md-5 border-end">
                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary border-bottom pb-2">Définir des droits d'accès</h6>
                        <form id="right-form" novalidate>
                            @csrf
                            <input type="hidden" id="rights-magasin-id">
                            
                            <div class="mb-3">
                                <label for="d-type-sujet" class="form-label small fw-semibold">Type de sujet <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="d-type-sujet" name="type_sujet" required>
                                    <option value="ROLE">Rôle</option>
                                    <option value="USER">Utilisateur</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="d-sujet-role-wrapper">
                                <label for="d-sujet-role" class="form-label small fw-semibold">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="d-sujet-role" name="sujet_role_id">
                                    <option value="">Sélectionner un rôle...</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="d-sujet-user-wrapper">
                                <label for="d-sujet-user" class="form-label small fw-semibold">Utilisateur <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="d-sujet-user" name="sujet_user_id">
                                    <option value="">Sélectionner un utilisateur...</option>
                                    @foreach($users as $usr)
                                        <option value="{{ $usr->id }}">{{ $usr->name }} ({{ $usr->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold d-block">Permissions autorisées</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="p-lire" name="peut_lire" value="1">
                                            <label class="form-check-label small" for="p-lire">Peut lire (voir)</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="p-entrer" name="peut_entrer_stock" value="1">
                                            <label class="form-check-label small" for="p-entrer">Peut entrer stock</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="p-sortir" name="peut_sortir_stock" value="1">
                                            <label class="form-check-label small" for="p-sortir">Peut sortir stock</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="p-transferer" name="peut_transferer" value="1">
                                            <label class="form-check-label small" for="p-transferer">Peut transférer</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="p-inventorier" name="peut_inventorier" value="1">
                                            <label class="form-check-label small" for="p-inventorier">Peut inventorier</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="p-administrer" name="peut_administrer" value="1">
                                            <label class="form-check-label small text-danger fw-semibold" for="p-administrer">Administrer</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-secondary btn-sm rounded-1 w-100" id="btn-save-right">
                                <i class="fas fa-plus me-1"></i> Enregistrer les droits
                            </button>
                        </form>
                    </div>
                    
                    <!-- Liste des droits actuels -->
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary border-bottom pb-2">Droits d'accès configurés</h6>
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Sujet</th>
                                        <th>Permissions</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="rights-list" class="small">
                                    <!-- Rempli en JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
