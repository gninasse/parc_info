{{--
    Formulaire de bon de commande, partagé par la création et la modification.
    Variables : $bonCommande (null en création), $fournisseurs, $articlesCatalogue, $lignesExistantes
--}}
@php $modification = (bool) $bonCommande; @endphp

<form id="bc-form" autocomplete="off" novalidate>
    @csrf
    @if($modification)
        <input type="hidden" id="bc-id" value="{{ $bonCommande->id }}">
    @endif

    {{-- ── EN-TÊTE ─────────────────────────────────────────────────────── --}}
    <div class="card border-1 rounded-1 mb-3">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-bold"><i class="fas fa-file-contract text-primary me-2"></i>En-tête du bon de commande</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Fournisseur <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="fournisseur_id" id="fournisseur_id"
                               value="{{ $bonCommande?->fournisseur_id }}">
                        <input type="text" class="form-control" id="fournisseur-libelle" readonly
                               placeholder="Sélectionner un fournisseur…"
                               value="{{ $bonCommande?->fournisseur?->nom }}">
                        <button class="btn btn-outline-primary" type="button" id="btn-choisir-fournisseur">
                            <i class="fas fa-search me-1"></i>Choisir
                        </button>
                        <button class="btn btn-outline-danger {{ $modification ? '' : 'd-none' }}"
                                type="button" id="btn-vider-fournisseur" title="Retirer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="fournisseur-code" class="small text-muted mt-1 {{ $modification ? '' : 'd-none' }}">
                        Code fournisseur :
                        <span class="fw-semibold font-monospace">{{ $bonCommande?->fournisseur?->code }}</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="date_commande">
                        Date de commande <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="date_commande" id="date_commande" class="form-control form-control-sm"
                           value="{{ $bonCommande?->date_commande?->toDateString() ?? date('Y-m-d') }}" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-semibold" for="commentaire">Observations</label>
                    <textarea name="commentaire" id="commentaire" class="form-control form-control-sm" rows="2"
                              placeholder="Notes, précisions à porter à la connaissance du fournisseur…">{{ $bonCommande?->commentaire }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── LIGNES ──────────────────────────────────────────────────────── --}}
    <div class="card border-1 rounded-1 mb-3">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-list-ol text-primary me-2"></i>Lignes de commande</h6>
            <button type="button" id="btn-ajouter-ligne" class="btn btn-sm btn-outline-primary rounded-1">
                <i class="fas fa-plus me-1"></i>Ajouter une ligne
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-lignes">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 38%;">Article <span class="text-danger">*</span></th>
                            <th style="width: 12%;" class="text-center">Quantité <span class="text-danger">*</span></th>
                            <th style="width: 17%;" class="text-end">Prix unitaire HT <span class="text-danger">*</span></th>
                            <th style="width: 10%;" class="text-center">TVA</th>
                            <th style="width: 18%;" class="text-end">Montant HT</th>
                            <th style="width: 5%;" class="text-center">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody id="lignes-container"></tbody>
                    <tfoot class="table-light border-top">
                        <tr>
                            <td colspan="4" class="text-end fw-semibold">Total HT</td>
                            <td class="text-end fw-semibold text-nowrap" id="total-ht">0 FCFA</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end fw-semibold">
                                TVA
                                <span class="text-muted fw-normal" style="font-size:.75rem">
                                    (taux propre à chaque article)
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-nowrap" id="total-tva">0 FCFA</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Montant TTC</td>
                            <td class="text-end fw-bold text-primary fs-5 text-nowrap" id="total-ttc">0 FCFA</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ── ACTIONS ─────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ $modification ? route('achat.bons-commande.show', $bonCommande) : route('achat.bons-commande.index') }}"
           class="btn btn-sm btn-outline-secondary rounded-1 px-4">Annuler</a>
        <button type="submit" class="btn btn-sm btn-primary rounded-1 px-4" id="btn-save">
            <i class="fas fa-save me-2"></i>{{ $modification ? 'Enregistrer les modifications' : 'Enregistrer le bon de commande' }}
        </button>
    </div>
</form>

@include('achat::bons_commande._modals')
