<div class="modal fade" id="modal-consommer-consommable" tabindex="-1" aria-hidden="true" style="z-index: 1055;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow: hidden;">
            {{-- Accent Top Border --}}
            <div style="height: 4px; background: linear-gradient(90deg, #dc3545, #fd7e14);"></div>
            
            <div class="modal-header bg-light border-0 p-4 pb-3">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="p-1.5 bg-danger-subtle text-danger rounded-3 d-inline-flex align-items-center justify-content-center">
                            <i class="bi bi-dash-circle-fill fs-5"></i>
                        </span>
                        Enregistrer une sortie de stock
                    </h5>
                    <div class="mt-1">
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 me-1">
                            {{ $consommable->code }}
                        </span>
                        <span class="text-muted small">
                            {{ $consommable->nom }} — Stock disponible: 
                            <strong class="text-dark">{{ $consommable->quantite_stock_actuel }} {{ $consommable->typeConsommable->unite_stock }}s</strong>
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="form-consommer-consommable">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-4">
                        {{-- Quantité & Raison --}}
                        <div class="col-md-6">
                            <label class="field-label text-secondary fw-semibold mb-2">Quantité à sortir <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-box-seam"></i></span>
                                <input type="number" name="quantite" class="form-control field-input text-center fw-bold fs-5 border-start-0" value="1" min="1" max="{{ $consommable->quantite_stock_actuel }}" required style="color:#2d3748;">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="field-label text-secondary fw-semibold mb-2">Raison / Motif de la sortie</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-tag"></i></span>
                                <select name="raison" class="form-select field-input border-start-0">
                                    <option value="Remplacement standard">Remplacement standard</option>
                                    <option value="Maintenance préventive">Maintenance préventive</option>
                                    <option value="Panne / Dysfonctionnement">Panne / Dysfonctionnement</option>
                                    <option value="Prêt / Affectation directe">Prêt / Affectation directe</option>
                                    <option value="Ajustement de stock">Ajustement de stock</option>
                                </select>
                            </div>
                        </div>

                        {{-- Type d'affectation / Cible --}}
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h6 class="fw-bold mb-0 small text-uppercase text-muted" style="letter-spacing:.5px">Destinataire / Affectation de la sortie</h6>
                                <span class="badge bg-primary-subtle text-primary text-xs px-2 py-1">Sélectionnez une option</span>
                            </div>
                            
                            <div class="row row-cols-2 row-cols-md-5 g-2">
                                @foreach([
                                    ['NONE', 'bi-x-circle', 'Aucun (Générique)', 'text-muted', 'bg-light'],
                                    ['EQUIPEMENT', 'bi-pc-display', 'Équipement', 'text-primary', 'bg-primary-subtle'],
                                    ['EMPLOYE', 'bi-person-badge', 'Employé', 'text-success', 'bg-success-subtle'],
                                    ['SERVICE', 'bi-building', 'Service', 'text-info', 'bg-info-subtle'],
                                    ['UNITE', 'bi-door-open', 'Unité', 'text-warning', 'bg-warning-subtle'],
                                ] as [$v,$ic,$lb,$tc,$bc])
                                <div class="col">
                                    <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center position-relative w-100 h-100 {{ $v === 'NONE' ? 'selected' : '' }}" data-value="{{ $v }}" style="height: 100px !important;">
                                        <input type="radio" name="target_type" value="{{ $v }}" class="d-none" {{ $v === 'NONE' ? 'checked' : '' }}>
                                        <div class="aff-type-icon rounded-3 p-2 {{ $bc }}"><i class="bi {{ $ic }} fs-4 {{ $tc }}"></i></div>
                                        <span class="fw-semibold text-dark" style="font-size:.78rem; line-height: 1.1;">{{ $lb }}</span>
                                        <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 {{ $v === 'NONE' ? '' : 'd-none' }} check-icon" style="font-size:.85rem"></i>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Inputs dynamiques --}}
                        
                        {{-- 1. Cible Aucun --}}
                        <div id="target-input-none" class="col-12 target-input-section py-3 px-4 bg-light rounded-3 d-flex align-items-center gap-3 border">
                            <i class="bi bi-info-circle-fill text-secondary fs-4"></i>
                            <div class="small text-secondary">
                                <strong>Consommation sans affectation :</strong> Cette sortie de stock sera enregistrée dans l'historique global comme une consommation courante de consommables, sans attribution spécifique à un équipement, employé ou service.
                            </div>
                        </div>
 
                        {{-- 2. Cible Équipement --}}
                        <div id="target-input-equipement" class="col-12 target-input-section d-none">
                            <div class="card border border-primary-subtle rounded-3 bg-primary bg-opacity-10">
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <label class="field-label text-secondary fw-semibold mb-1">Filtrer par type</label>
                                            <select id="consommation-equipement-type" class="form-select field-input bg-white">
                                                <option value="">Tous les types</option>
                                                <option value="ordinateur">Poste de travail (Ordinateur)</option>
                                                <option value="serveur">Serveur</option>
                                                <option value="imprimante">Imprimante</option>
                                                <option value="scanner">Scanner</option>
                                                <option value="telephone">Téléphone</option>
                                                <option value="camera">Caméra IP</option>
                                                <option value="mobile">Mobile / Tablette</option>
                                                <option value="reseau">Équipement Réseau</option>
                                            </select>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="field-label text-secondary fw-semibold mb-1">Équipement cible <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="hidden" name="equipement_id" id="consommation-equipement-id">
                                                <input type="text" id="consommation-equipement-label" class="form-control field-input bg-white border-end-0" placeholder="Rechercher..." readonly>
                                                <button type="button" class="btn btn-primary px-3" id="btn-select-equipement">
                                                    <i class="bi bi-search me-1"></i>Sélectionner
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i>La sortie sera affectée à cet équipement et une affectation de consommable sera automatiquement générée.</small>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Cible Employé --}}
                        <div id="target-input-employe" class="col-12 target-input-section d-none">
                            <div class="card border border-success-subtle rounded-3 bg-success bg-opacity-10">
                                <div class="card-body p-3">
                                    <label class="field-label text-secondary fw-semibold mb-1">Employé bénéficiaire <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="hidden" name="employe_id" id="consommation-employe-id">
                                        <input type="text" id="consommation-employe-label" class="form-control field-input bg-white border-end-0" placeholder="Sélectionnez un employé..." readonly>
                                        <button type="button" class="btn btn-success px-3" id="btn-select-employe">
                                            <i class="bi bi-search me-1"></i>Sélectionner
                                        </button>
                                    </div>
                                    <small class="form-text text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i>Le consommable sera enregistré au nom de cet employé.</small>
                                </div>
                            </div>
                        </div>

                        {{-- 4. Cible Service --}}
                        <div id="target-input-service" class="col-12 target-input-section d-none">
                            <div class="card border border-info-subtle rounded-3 bg-info bg-opacity-10">
                                <div class="card-body p-3">
                                    <label class="field-label text-secondary fw-semibold mb-1">Service bénéficiaire <span class="text-danger">*</span></label>
                                    <select name="service_id" id="consommation-service-id" class="form-select field-input select2-mouvement">
                                        <option value="">Sélectionnez un service...</option>
                                        @foreach($services as $s)
                                            <option value="{{ $s->id }}">{{ $s->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i>La sortie sera rattachée aux consommations globales de ce service.</small>
                                </div>
                            </div>
                        </div>

                        {{-- 5. Cible Unité --}}
                        <div id="target-input-unite" class="col-12 target-input-section d-none">
                            <div class="card border border-warning-subtle rounded-3 bg-warning bg-opacity-10">
                                <div class="card-body p-3">
                                    <label class="field-label text-secondary fw-semibold mb-1">Unité bénéficiaire <span class="text-danger">*</span></label>
                                    <select name="unite_id" id="consommation-unite-id" class="form-select field-input select2-mouvement">
                                        <option value="">Sélectionnez une unité...</option>
                                        @foreach($unites as $u)
                                            <option value="{{ $u->id }}">{{ $u->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i>La sortie sera rattachée aux consommations de cette unité.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="field-label text-secondary fw-semibold mb-2">Notes complémentaires / Observations</label>
                            <textarea name="notes" class="form-control field-input" rows="2" placeholder="Précisez ici les compatibilités, l'emplacement, ou toute autre observation utile..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 d-flex align-items-center gap-2" id="btn-save-consommation">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Valider la sortie</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Style premium pour les cartes de sélection */
.aff-type-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.aff-type-card:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
}
.aff-type-card.selected {
    border-color: #0d6efd !important;
    background: rgba(13, 110, 253, 0.04) !important;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.08) !important;
}
.aff-type-card .check-icon {
    font-size: 0.9rem;
    transition: scale 0.2s ease;
}
.aff-type-card.selected .check-icon {
    display: block !important;
    scale: 1.1;
}
.aff-type-card.selected .aff-type-icon {
    background: #0d6efd !important;
}
.aff-type-card.selected .aff-type-icon i {
    color: #fff !important;
}
.aff-type-icon {
    transition: all 0.2s ease;
}
.bg-opacity-10 {
    --bs-bg-opacity: 0.05;
}
</style>
