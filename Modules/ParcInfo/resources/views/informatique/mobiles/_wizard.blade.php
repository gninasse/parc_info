<div class="modal fade" id="mobileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter un mobile/tablette</h5>
                    <small class="text-muted" id="wizard-subtitle">Configuration des terminaux mobiles - CHU Yalgado</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Stepper --}}
            <div class="px-4 pt-4 pb-2">
                <div class="d-flex align-items-center justify-content-center gap-0" id="wizard-stepper">
                    @foreach([['1','STATUT'],['2','INFORMATIONS'],['3','AFFECTATION']] as [$n,$label])
                    <div class="d-flex align-items-center {{ !$loop->last ? 'flex-grow-1' : '' }}">
                        <div class="d-flex flex-column align-items-center">
                            <div class="wizard-step-circle {{ $loop->first ? 'active' : '' }}" data-step="{{ $n }}">{{ $n }}</div>
                            <small class="wizard-step-label mt-1 {{ $loop->first ? 'text-primary fw-bold' : 'text-muted' }}" data-step="{{ $n }}">{{ $label }}</small>
                        </div>
                        @if(!$loop->last)
                        <div class="wizard-step-line flex-grow-1 mx-2" data-after="{{ $n }}"></div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <form id="mobileForm" novalidate>
                @csrf
                <input type="hidden" id="mob_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut du terminal</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de ce mobile dans l'inventaire.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',    'En stock',      'Disponible pour attribution'],
                                ['en_service',    'bi-phone-vibrate', 'En service',    'Utilisé par un agent ou service'],
                                ['en_reparation', 'bi-tools',      'En réparation', 'En maintenance ou écran cassé'],
                            ] as [$val,$icon,$label,$desc])
                            <label class="statut-card d-flex align-items-center gap-3 p-3 rounded-3 border cursor-pointer" data-value="{{ $val }}">
                                <input type="radio" name="statut" value="{{ $val }}" class="d-none">
                                <div class="statut-card-icon rounded-2 p-2 bg-light"><i class="bi {{ $icon }} fs-5 text-secondary"></i></div>
                                <div>
                                    <div class="fw-semibold small">{{ $label }}</div>
                                    <div class="text-muted" style="font-size:.78rem">{{ $desc }}</div>
                                </div>
                                <i class="bi bi-check-circle-fill text-primary ms-auto d-none check-icon"></i>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── ÉTAPE 2 : INFORMATIONS ── --}}
                    <div id="step-2" class="wizard-step d-none">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Type de terminal</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="type_mobile_id" id="type_mobile_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($typesMobiles as $t)
                                            <option value="{{ $t->id }}">{{ $t->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="btn-add-type-mobile" title="Nouveau type">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">N° Téléphone associé</label>
                                <input type="text" class="form-control field-input" name="num_tel_associe" id="num_tel_associe" placeholder="Ex: 70 00 00 00">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré automatiquement" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" placeholder="S/N du constructeur" required>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label field-label">Marque</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="marque_id" id="marque_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($marques as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: Galaxy Tab S9, iPhone 15" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">IMEI 1</label>
                                <input type="text" class="form-control field-input" name="imei_1" id="imei_1" placeholder="15 chiffres">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">IMEI 2 (Optionnel)</label>
                                <input type="text" class="form-control field-input" name="imei_2" id="imei_2" placeholder="Second slot SIM">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Version OS</label>
                                <input type="text" class="form-control field-input" name="version_os" id="version_os" placeholder="Ex: Android 14, iOS 17">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Statut MDM</label>
                                <select class="form-select field-input" name="statut_mdm" id="statut_mdm">
                                    <option value="Non enrôlé">Non enrôlé</option>
                                    <option value="Enrôlé">Enrôlé (Géré par l'IT)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">État de l'équipement <span class="text-danger">*</span></label>
                                <select class="form-select field-input" name="etat" id="etat" required>
                                    <option value="bon">Bon</option>
                                    <option value="passable">Passable</option>
                                    <option value="mauvais">Mauvais</option>
                                    <option value="avarie">Avarié / HS</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Batterie (mAh)</label>
                                <input type="number" class="form-control field-input" name="capacite_batterie_mah" id="capacite_batterie_mah" placeholder="Ex: 5000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">État de l'écran</label>
                                <select class="form-select field-input" name="etat_ecran" id="etat_ecran">
                                    <option value="Parfait">Parfait</option>
                                    <option value="Micro-rayures">Micro-rayures</option>
                                    <option value="Fissuré">Fissuré</option>
                                    <option value="Cassé">Cassé / Inexploitable</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="a_coque_protection" id="a_coque_protection" value="1" checked>
                                    <label class="form-check-label small fw-bold" for="a_coque_protection">Présence d'une coque/étui de protection</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── ÉTAPE 3 : AFFECTATION ── --}}
                    <div id="step-3" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3">Attribution du terminal</h6>
                        <div class="row g-3 mb-4" id="affectation-type-cards">
                            @foreach([
                                ['EMPLOYE','bi-person-badge','Attribuer à un agent'],
                                ['SERVICE', 'bi-building',      'Utilisation collective (Service)'],
                                ['LOCAL',   'bi-door-open',    'Affecter à un local'],
                            ] as [$val,$icon,$label])
                            <div class="col-4">
                                <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center" data-value="{{ $val }}">
                                    <input type="radio" name="type_cible" value="{{ $val }}" class="d-none">
                                    <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi {{ $icon }} fs-3 text-secondary"></i></div>
                                    <small class="fw-semibold" style="font-size:.78rem">{{ $label }}</small>
                                    <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        {{-- Summary cards reuse the same IDs as ordinateurs/serveurs for JS compatibility with selection_modals --}}
                        <div id="aff-employe-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-2 fw-bold text-primary"><i class="bi bi-person-badge me-2"></i>Agent bénéficiaire</h6>
                                    <div class="row g-2 small">
                                        <div class="col-md-6"><span class="text-muted">Nom:</span> <strong id="emp-summary-nom">—</strong></div>
                                        <div class="col-md-6"><span class="text-muted">Matricule:</span> <strong id="emp-summary-matricule">—</strong></div>
                                        <div class="col-12 mt-1"><span class="text-muted">Rattachement:</span> <span id="emp-summary-rattachement">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                        </div>

                        <div id="aff-local-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-2 fw-bold text-primary"><i class="bi bi-door-open me-2"></i>Localisation</h6>
                                    <div class="row g-2 small">
                                        <div class="col-12"><span class="text-muted">Local:</span> <strong id="local-summary-libelle">—</strong></div>
                                        <div class="col-12"><span class="text-muted">Emplacement:</span> <span id="local-summary-batiment">—</span> / <span id="local-summary-etage">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="local_id" id="local_id">
                        </div>

                        <div class="text-center mt-3" id="aff-skip-hint">
                            <small class="text-muted">Aucune attribution sélectionnée — le terminal sera mis en stock.</small>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium shadow-none" data-bs-dismiss="modal">Annuler</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4" id="btn-prev" style="display:none!important">
                            <i class="bi bi-chevron-left me-1"></i> Précédent
                        </button>
                        <button type="button" class="btn btn-primary px-4" id="btn-next">
                            Suivant <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-primary px-4 d-none" id="btn-submit">
                            <i class="bi bi-floppy me-1"></i> <span id="btn-submit-label">Enregistrer le terminal</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
