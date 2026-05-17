<div class="modal fade" id="modal-licence" tabindex="-1" aria-labelledby="modalLicenceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary" id="modalLicenceLabel">
                    <i class="fas fa-key me-2"></i><span>Nouvelle Licence</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-licence">
                @csrf
                <input type="hidden" name="id" id="licence-id">
                <div class="modal-body py-4">
                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-compact-disc me-2"></i>Logiciel & Identification
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Logiciel <span class="text-danger">*</span></label>
                            <select name="logiciel_id" class="form-select select2-modal" required>
                                <option value="">Sélectionnez un logiciel...</option>
                                @foreach($logiciels as $l)
                                    <option value="{{ $l->id }}">{{ $l->nom }} ({{ $l->editeur->nom }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Clé de licence</label>
                            <input type="text" name="cle_licence" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Numéro de contrat / Bon commande</label>
                            <input type="text" name="numero_contrat" class="form-control" placeholder="CONTRAT-2026-001">
                        </div>
                    </div>

                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-cog me-2"></i>Type & Volume
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Type d'activation <span class="text-danger">*</span></label>
                            <select name="type_activation" class="form-select" required>
                                <option value="volume">Volume (CAL)</option>
                                <option value="concurrent">Concurrent</option>
                                <option value="subscription">Abonnement (SaaS)</option>
                                <option value="free">Gratuite / Open Source</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Modèle de licencing <span class="text-danger">*</span></label>
                            <select name="modele_licencing" class="form-select" required>
                                <option value="device">Par équipement (Device)</option>
                                <option value="user">Par utilisateur (User)</option>
                                <option value="concurrent">Accès simultanés (Concurrent)</option>
                                <option value="named">Utilisateur nommé (Named)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nombre de postes <span class="text-danger">*</span></label>
                            <input type="number" name="nombre_postes_accordes" class="form-control" value="1" min="0" required>
                            <div class="form-text small">0 = Illimité</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Statut <span class="text-danger">*</span></label>
                            <select name="statut" class="form-select" required>
                                <option value="actif">Actif</option>
                                <option value="expire">Expiré</option>
                                <option value="en_renouvellement">En renouvellement</option>
                                <option value="suspendu">Suspendu</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-calendar-alt me-2"></i>Dates & Coûts
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date acquisition <span class="text-danger">*</span></label>
                            <input type="date" name="date_acquisition" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date activation</label>
                            <input type="date" name="date_activation" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date expiration <span class="text-danger">*</span></label>
                            <input type="date" name="date_expiration" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Coût Unitaire</label>
                            <div class="input-group">
                                <input type="number" name="cout_unitaire" class="form-control" step="0.01" placeholder="0.00">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Coût Total</label>
                            <div class="input-group">
                                <input type="number" name="cout_total" class="form-control" step="0.01" placeholder="0.00">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-truck me-2"></i>Tiers & Support
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fournisseur <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="fournisseur_id" id="select-fournisseur" class="form-select select2-modal" required>
                                    <option value="">Sélectionnez...</option>
                                    @foreach($fournisseurs as $f)
                                        <option value="{{ $f->id }}">{{ $f->nom }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="btn-quickadd-fournisseur">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Support Technique</label>
                            <select name="contact_support_id" class="form-select select2-modal">
                                <option value="">Aucun contact...</option>
                                @foreach($contacts as $c)
                                    <option value="{{ $c->id }}">{{ $c->nom }} {{ $c->prenom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Contrat de Maintenance</label>
                            <div class="input-group">
                                <select name="contrat_maintenance_id" id="select-contrat" class="form-select select2-modal">
                                    <option value="">Aucun contrat...</option>
                                    @foreach($contrats as $ct)
                                        <option value="{{ $ct->id }}">{{ $ct->reference }} - {{ $ct->nom }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-info" id="btn-quickadd-contrat" title="Ajout rapide">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="devise" value="EUR">
                <input type="hidden" name="actif" value="1">
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-licence">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
