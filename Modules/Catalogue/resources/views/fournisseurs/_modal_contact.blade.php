{{-- Modale duale création/édition d'un contact fournisseur.

     Même structure que la modale du fournisseur : un seul formulaire pour les
     deux sens, le titre et l'identifiant caché portant la différence. Deux
     modales séparées finiraient par diverger sur un champ. --}}
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true" aria-labelledby="contactModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="contactModalLabel">
                    <i class="fas fa-address-book me-2"></i><span id="contact-modal-title-text">Nouveau contact</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="contact-form" novalidate>
                @csrf
                <input type="hidden" id="contact-id" name="id">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="c-nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="c-nom" name="nom" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="c-prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="c-prenom" name="prenom" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="c-fonction" class="form-label">Fonction</label>
                            <input type="text" class="form-control" id="c-fonction" name="fonction"
                                   maxlength="255" placeholder="Commercial, SAV, comptabilité…">
                            <div class="form-text">Elle indique qui appeler, et pour quoi.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="c-telephone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="c-telephone" name="telephone"
                                   maxlength="30" placeholder="+226 …">
                        </div>
                        <div class="col-md-6">
                            <label for="c-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="c-email" name="email" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="c-est-principal" name="est_principal" value="1">
                                <label class="form-check-label" for="c-est-principal">Contact principal</label>
                                {{-- Un seul principal par fournisseur : le dire ici
                                     évite la surprise de voir l'ancien se décocher
                                     tout seul. --}}
                                <div class="form-text">Il remplacera le principal actuel, s'il y en a un.</div>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="c-est-actif" name="est_actif" value="1" checked>
                                <label class="form-check-label" for="c-est-actif">Contact actif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="c-notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="c-notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-contact-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
