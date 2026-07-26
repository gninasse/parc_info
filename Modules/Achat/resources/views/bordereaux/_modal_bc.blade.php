{{-- Sélection d'un bon de commande à réceptionner (M-05) --}}
<div class="modal fade" id="modal-bc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="fas fa-file-invoice me-2 text-primary"></i>Sélectionner un bon de commande
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="recherche-bc" class="form-control bg-light border-start-0"
                           placeholder="Rechercher par numéro ou fournisseur…">
                </div>

                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0 border-top">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 25%;">N° commande</th>
                                <th style="width: 40%;">Fournisseur</th>
                                <th style="width: 15%;">Date</th>
                                <th style="width: 20%;" class="text-end">Montant TTC</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bonsCommande as $bonCommande)
                                <tr class="ligne-bc" style="cursor: pointer"
                                    data-id="{{ $bonCommande->id }}"
                                    data-numero="{{ $bonCommande->numero_commande }}"
                                    data-fournisseur="{{ $bonCommande->fournisseur?->nom }}"
                                    data-recherche="{{ mb_strtolower($bonCommande->numero_commande.' '.$bonCommande->fournisseur?->nom) }}">
                                    <td><span class="badge bg-secondary font-monospace">{{ $bonCommande->numero_commande }}</span></td>
                                    <td class="fw-bold text-dark">{{ $bonCommande->fournisseur?->nom ?? '-' }}</td>
                                    <td class="text-muted small">{{ $bonCommande->date_commande?->format('d/m/Y') }}</td>
                                    <td class="text-end font-monospace text-muted">
                                        {{ number_format($bonCommande->montant_ttc, 0, ',', ' ') }} FCFA
                                    </td>
                                </tr>
                            @empty
                                <x-achat-etat-vide
                                    message="Aucun bon de commande en attente de livraison."
                                    :colspan="4" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
