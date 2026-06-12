<div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="section-title mb-0"><span class="section-num"><i class="bi bi-people"></i></span> Contacts Associés</h6>
            <button type="button" class="btn btn-primary btn-sm" id="btn-add-contact">
                <i class="bi bi-person-plus me-1"></i> Ajouter un contact
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="contacts-table">
                <thead class="table-light">
                    <tr>
                        <th>Nom Complet</th>
                        <th>Fonction</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="contacts-tbody">
                    @forelse($fournisseur->contacts as $contact)
                    <tr data-contact-id="{{ $contact->id }}">
                        <td>
                            <div class="fw-bold">{{ $contact->nom }} {{ $contact->prenom }}</div>
                        </td>
                        <td>{{ $contact->fonction ?: '—' }}</td>
                        <td>
                            @if($contact->email)
                                <a href="mailto:{{ $contact->email }}" class="text-decoration-none"><i class="bi bi-envelope me-1"></i>{{ $contact->email }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $contact->telephone ?: '—' }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-info btn-sm btn-edit-contact" data-id="{{ $contact->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-contact" data-id="{{ $contact->id }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr id="contacts-empty-row">
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1 opacity-50 d-block mb-2"></i>
                            Aucun contact enregistré
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── MODAL CONTACT ── --}}
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="bi bi-person-plus text-primary me-2"></i><span>Ajouter un contact</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="contactForm">
                @csrf
                <input type="hidden" name="id" id="contact_id" />
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="c_nom" class="form-control field-input" placeholder="ex: Martin" />
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" id="c_prenom" class="form-control field-input" placeholder="ex: Jean" />
                        </div>
                        <div class="col-12">
                            <label class="field-label">Fonction / Poste</label>
                            <input type="text" name="fonction" id="c_fonction" class="form-control field-input" placeholder="ex: Responsable Commercial" />
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Email</label>
                            <input type="email" name="email" id="c_email" class="form-control field-input" placeholder="ex: j.martin@fournisseur.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Téléphone</label>
                            <input type="text" name="telephone" id="c_telephone" class="form-control field-input" placeholder="ex: +33 6 12 34 56 78" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" id="btn-cancel-contact" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-contact">
                        <i class="bi bi-check-circle me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
