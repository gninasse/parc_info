<div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalLabel"><span>Nouvelle</span> Catégorie d'Équipement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="item-form" novalidate>
                @csrf
                <input type="hidden" id="item-id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="libelle" name="libelle" required placeholder="Ex: Vidéoprojecteurs">
                    </div>
                    
                    <div class="mb-3" id="code-group">
                        <label for="code" class="form-label">Code technique <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required placeholder="Ex: videoprojecteur">
                        <small class="text-muted">Lettres minuscules, chiffres et tirets uniquement. Non modifiable par la suite.</small>
                    </div>

                    <div class="mb-3">
                        <label for="icone" class="form-label">Icône <span class="text-danger">*</span></label>
                        <select class="form-select" id="icone" name="icone" required>
                            <option value="bi-cpu" data-icon="bi-cpu">Processeur / Défaut (bi-cpu)</option>
                            <option value="bi-pc" data-icon="bi-pc">PC classique (bi-pc)</option>
                            <option value="bi-pc-display" data-icon="bi-pc-display">Ordinateur de bureau (bi-pc-display)</option>
                            <option value="bi-display" data-icon="bi-display">Écran (bi-display)</option>
                            <option value="bi-laptop" data-icon="bi-laptop">Ordinateur portable (bi-laptop)</option>
                            <option value="bi-server" data-icon="bi-server">Serveur (bi-server)</option>
                            <option value="bi-phone-vibrate" data-icon="bi-phone-vibrate">Téléphone / Tablette (bi-phone-vibrate)</option>
                            <option value="bi-printer" data-icon="bi-printer">Imprimante (bi-printer)</option>
                            <option value="bi-camera-video" data-icon="bi-camera-video">Caméra (bi-camera-video)</option>
                            <option value="bi-hdd-network" data-icon="bi-hdd-network">Switch / Routeur (bi-hdd-network)</option>
                            <option value="bi-lightning-charge" data-icon="bi-lightning-charge">Onduleur (bi-lightning-charge)</option>
                            <option value="bi-projector" data-icon="bi-projector">Vidéoprojecteur (bi-projector)</option>
                            <option value="bi-headphones" data-icon="bi-headphones">Casque / Audio (bi-headphones)</option>
                            <option value="bi-device-ssd" data-icon="bi-device-ssd">Stockage (bi-device-ssd)</option>
                            <option value="bi-router" data-icon="bi-router">Modem / Routeur WiFi (bi-router)</option>
                            <option value="bi-wifi" data-icon="bi-wifi">Borne WiFi (bi-wifi)</option>
                        </select>
                        <div class="mt-2 text-center p-2 border rounded bg-light d-flex align-items-center justify-content-center gap-2">
                            <span>Aperçu de l'icône :</span>
                            <span class="badge bg-white text-dark p-2 border"><i id="icon-preview" class="bi bi-cpu fs-4"></i></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save"><i class="fas fa-save me-2"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
