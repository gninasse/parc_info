<div class="modal fade" id="serveurModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter une machine virtuelle</h5>
                    <small id="wizard-subtitle">Configuration de l'infrastructure IT - CHU Yalgado</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="serveurForm" novalidate>
                @csrf
                <input type="hidden" id="srv_id" name="id">
                
                {{-- Hidden fields to pass validation constraints --}}
                <input type="hidden" name="modele" id="modele" value="Machine Virtuelle">
                <input type="hidden" name="etat" id="etat" value="bon">

                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        
                        {{-- ── SECTION 1 : INFRASTRUCTURE & STATUT ── --}}
                        <div class="col-12">
                            <h6 class="section-title mb-3"><span class="section-num">01</span> Statut & Hébergement</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Statut de la VM <span class="text-danger">*</span></label>
                            <select class="form-select field-input" name="statut" id="statut" required>
                                <option value="en_stock">En stock (Éteinte / Hors service)</option>
                                <option value="en_service" selected>En service (Active / Opérationnelle)</option>
                                <option value="en_reparation">En réparation (Maintenance)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Serveur Physique Hôte <span class="text-danger">*</span></label>
                            <select class="form-select field-input" name="serveur_hote_id" id="serveur_hote_id" required>
                                <option value="">Sélectionner l'hôte physique...</option>
                                @foreach($serveursPhysiques as $sp)
                                <option value="{{ $sp->equipement_id }}">{{ $sp->equipement->code_inventaire }} — {{ $sp->equipement->modele }} ({{ $sp->nom_hote ?: 'Sans nom' }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ── SECTION 2 : CONFIGURATION LOGIQUE & RÉSEAU ── --}}
                        <div class="col-12 mt-4">
                            <h6 class="section-title mb-3"><span class="section-num">02</span> Spécifications Système & Réseau</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">UUID / ID Unique de la VM <span class="text-danger">*</span></label>
                            <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" placeholder="Ex: VM-UUID-987654" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Code Inventaire (Généré automatiquement)</label>
                            <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré à la création" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Nom d'hôte / FQDN</label>
                            <input type="text" class="form-control field-input" name="nom_hote" id="nom_hote" placeholder="Ex: vm-db-01.chu.local">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Domaine</label>
                            <input type="text" class="form-control field-input" name="domaine" id="domaine" placeholder="Ex: chu.local">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Système d'exploitation (OS)</label>
                            <div class="input-group">
                                <select class="form-select field-input" name="os_type_id" id="os_type_id">
                                    <option value="">Sélectionner...</option>
                                    @foreach($typesOs as $o)
                                    <option value="{{ $o->id }}">{{ $o->libelle }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-add-nomenclature" data-type="os"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Hyperviseur</label>
                            <input type="text" class="form-control field-input" name="hyperviseur" id="hyperviseur" placeholder="Ex: ESXi 7.0, Proxmox, Hyper-V">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Adresse IP</label>
                            <input type="text" class="form-control field-input" name="adresse_ip" id="adresse_ip" placeholder="Ex: 192.168.1.10">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Adresse MAC vNIC</label>
                            <input type="text" class="form-control field-input" name="adresse_mac" id="adresse_mac" placeholder="Ex: 00:1A:2B:3C:4D:5E">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label field-label">Rôle / Fonction principale</label>
                            <input type="text" class="form-control field-input" name="role_serveur" id="role_serveur" placeholder="Ex: Active Directory, Base de données SQL, Serveur Web Apache">
                        </div>

                        {{-- ── SECTION 3 : RESSOURCES VIRTUELLES ── --}}
                        <div class="col-12 mt-4">
                            <h6 class="section-title mb-3"><span class="section-num">03</span> Allocation des Ressources</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label field-label">Processeur (Sockets / Coeurs vCPU)</label>
                            <div class="input-group">
                                <input type="number" class="form-control field-input" name="nb_processeurs" id="nb_processeurs" placeholder="Sockets vCPU" min="1">
                                <input type="number" class="form-control field-input" name="nb_coeurs_total" id="nb_coeurs_total" placeholder="Total coeurs vCPU" min="1">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label field-label">Mémoire vRAM (Go)</label>
                            <input type="number" class="form-control field-input" name="ram_capacite_go" id="ram_capacite_go" placeholder="Ex: 16" min="1">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label field-label">Disque Virtuel (Go)</label>
                            <input type="number" class="form-control field-input" name="stockage_capacite_go" id="stockage_capacite_go" placeholder="Ex: 250" min="1">
                        </div>

                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-submit">
                        <i class="bi bi-floppy me-1"></i> <span id="btn-submit-label">Enregistrer la VM</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#344054; border-left:3px solid #0d6efd; padding-left:10px; display:flex; align-items:center; gap:8px; }
.section-num   { background:#0d6efd; color:#fff; font-size:.65rem; font-weight:700; border-radius:4px; padding:1px 6px; }
.field-label   { font-size:.78rem; font-weight:600; color:#475467; margin-bottom:4px; display:block; }
.field-input   { font-size:.875rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
.field-input:not(:disabled):focus { background:#fff; border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.1); }
</style>
