{{-- Modale MD-MAGASIN duale (UX §9) : site restreint aux sites sans magasin,
     verrouillé en édition (cadenas + tooltip) ; code en lecture seule. --}}
<div class="modal fade" id="magasinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="magasin-form" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title"><span id="modal-title-text">Nouveau magasin</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="magasin-id">

                    <div class="mb-3">
                        <label for="m-libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="m-libelle" name="libelle" required>
                    </div>

                    <div class="mb-3">
                        <label for="m-site" class="form-label">
                            Site <span class="text-danger">*</span>
                            <i id="m-site-cadenas" class="bi bi-lock-fill text-muted d-none"
                               data-bs-toggle="tooltip"
                               title="Le site d'un magasin ne change pas : le code MAG-{SITE} en découle."></i>
                        </label>
                        <select class="form-select" id="m-site" name="site_id" required
                                data-placeholder="Sites sans magasin uniquement"></select>
                        <div class="form-text">Un magasin par site — seuls les sites sans magasin sont proposés.</div>
                    </div>

                    <div class="mb-3">
                        <label for="m-local" class="form-label">Local</label>
                        <select class="form-select" id="m-local" name="local_id"
                                data-placeholder="Choisir d'abord un site"></select>
                        <div class="form-text">Les locaux de type « magasin » sont proposés en premier.</div>
                    </div>

                    <div class="mb-3">
                        <label for="m-responsable" class="form-label">Responsable</label>
                        <select class="form-select" id="m-responsable" name="responsable_id"
                                data-placeholder="Rechercher un employé…"></select>
                    </div>

                    <div class="mb-1">
                        <label for="m-code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="m-code" readonly
                               placeholder="Généré automatiquement (MAG-{SITE})">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
