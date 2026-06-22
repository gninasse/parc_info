<div class="modal fade" id="field-modal" tabindex="-1" aria-labelledby="fieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="fieldModalLabel"><span>Nouveau</span> Champ de Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="field-form" novalidate>
                @csrf
                <input type="hidden" id="field-id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Technical Code -->
                        <div class="col-md-6" id="field-code-group">
                            <label for="field_code" class="form-label">Code technique <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="field_code" name="code" required placeholder="Ex: resolution_pouces">
                            <small class="text-muted">Lettres minuscules, chiffres et tirets bas uniquement.</small>
                        </div>

                        <!-- Label -->
                        <div class="col-md-6">
                            <label for="field_libelle" class="form-label">Libellé d'affichage <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="field_libelle" name="libelle" required placeholder="Ex: Résolution (pouces)">
                        </div>

                        <!-- Type of field -->
                        <div class="col-md-6">
                            <label for="field_type_champ" class="form-label">Type de saisie <span class="text-danger">*</span></label>
                            <select class="form-select" id="field_type_champ" name="type_champ" required>
                                <option value="text">Texte libre (text)</option>
                                <option value="number">Nombre entier ou décimal (number)</option>
                                <option value="select">Sélecteur dropdown (select)</option>
                                <option value="boolean">Oui / Non (boolean)</option>
                                <option value="date">Saisie de Date (date)</option>
                            </select>
                        </div>

                        <!-- Wizard Panel Group -->
                        <div class="col-md-6">
                            <label for="field_nom_panel" class="form-label">Groupe d'affichage (onglet) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="field_nom_panel" name="nom_panel" required placeholder="Ex: Spécifications" list="common-panels">
                            <datalist id="common-panels">
                                <option value="Spécifications">
                                <option value="Performances">
                                <option value="Stockage">
                                <option value="Système & Licences">
                                <option value="Réseau">
                                <option value="Général">
                            </datalist>
                        </div>

                        <!-- Select Source Options Selection -->
                        <div class="col-md-12 d-none" id="select-source-container">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-2">Configuration de la source d'options</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label d-block">Type de source d'options :</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="source_type" id="src-dict" value="dict" checked>
                                            <label class="form-check-label" for="src-dict">Dictionnaire existant</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="source_type" id="src-custom" value="custom">
                                            <label class="form-check-label" for="src-custom">Valeurs manuelles (JSON)</label>
                                        </div>
                                    </div>

                                    <!-- Dictionaries Select -->
                                    <div class="mb-3" id="dict-select-group">
                                        <label for="dict_code_select" class="form-label">Dictionnaire de référence :</label>
                                        <select class="form-select" id="dict_code_select">
                                            <option value="">-- Choisir un dictionnaire --</option>
                                            @foreach($dictionaries as $dict)
                                                <option value="DICT:{{ $dict->code }}">{{ $dict->libelle }} ({{ $dict->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Custom JSON array -->
                                    <div class="mb-3 d-none" id="custom-json-group">
                                        <label for="custom_json_val" class="form-label">Tableau JSON de valeurs :</label>
                                        <input type="text" class="form-control" id="custom_json_val" placeholder='Ex: ["Option A", "Option B", "Option C"]'>
                                        <small class="text-muted">Un tableau JSON valide de chaînes de caractères.</small>
                                    </div>

                                    <input type="hidden" id="source_options" name="source_options">
                                </div>
                            </div>
                        </div>

                        <!-- Validation Rules -->
                        <div class="col-md-6">
                            <label for="field_regles_validation" class="form-label">Règles de validation (Laravel)</label>
                            <input type="text" class="form-control" id="field_regles_validation" name="regles_validation" placeholder="Ex: nullable|integer|min:1">
                            <small class="text-muted">Exemples: <code>required|string</code>, <code>nullable|numeric</code></small>
                        </div>

                        <!-- Order of field -->
                        <div class="col-md-6">
                            <label for="field_ordre_affichage" class="form-label">Ordre d'affichage dans le formulaire <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="field_ordre_affichage" name="ordre_affichage" required value="10" min="1">
                        </div>

                        <!-- Column list position -->
                        <div class="col-md-6">
                            <label for="field_ordre_colonne_liste" class="form-label">Ordre de colonne dans le tableau d'index</label>
                            <input type="number" class="form-control" id="field_ordre_colonne_liste" name="ordre_colonne_liste" value="99" min="1">
                        </div>

                        <!-- Checkboxes for visibility -->
                        <div class="col-md-6 d-flex align-items-center mt-4">
                            <div class="d-flex flex-column gap-2 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="field_afficher_dans_modal" name="afficher_dans_modal" value="1" checked>
                                    <label class="form-check-label" for="field_afficher_dans_modal">Afficher dans le formulaire d'ajout</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="field_afficher_dans_show" name="afficher_dans_show" value="1" checked>
                                    <label class="form-check-label" for="field_afficher_dans_show">Afficher dans la fiche technique</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="field_afficher_dans_liste" name="afficher_dans_liste" value="1" checked>
                                    <label class="form-check-label" for="field_afficher_dans_liste">Afficher comme colonne de la liste</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-field"><i class="fas fa-save me-2"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
