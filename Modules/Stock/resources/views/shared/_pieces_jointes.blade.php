{{--
    Carte « Pièces jointes » — commune aux trois documents (diligence 5).
    Paramètres : $typeDocument ('entrees'|'sorties'|'transferts'), $documentId,
                 $modifiable (bool : brouillon/phase, faux sur un bon validé)
--}}
<div class="card border-0 shadow-sm mb-3" id="carte-pieces-jointes"
     data-type="{{ $typeDocument }}"
     data-document-id="{{ $documentId }}"
     data-modifiable="{{ $modifiable ? '1' : '' }}">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-paperclip me-2 text-primary"></i>Pièces jointes
            (<span id="pj-compteur">0</span>)
        </h6>
        @if($modifiable)
            <div>
                <input type="file" id="pj-fichiers" class="d-none" multiple
                       accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt">
                <button type="button" class="btn btn-outline-primary btn-sm" id="pj-ajouter">
                    <i class="bi bi-upload me-1"></i>Ajouter des fichiers
                </button>
            </div>
        @endif
    </div>
    <div class="card-body">
        <div id="pj-liste" class="row g-2"></div>
        <p class="text-muted small mb-0 d-none" id="pj-vide">
            Aucune pièce jointe.
            @if($modifiable)
                Joignez le bon de livraison scanné, une photo du colis ou un courrier
                (PDF, image ou document bureautique, {{ (int) config('stock.documents.taille_max_ko', 5120) / 1024 }} Mo maximum).
            @endif
        </p>
        <div class="progress mt-2 d-none" id="pj-progression" style="height:4px;" role="progressbar" aria-label="Envoi en cours">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
        </div>
    </div>
</div>

@push('js')
<script type="module" src="{{ asset('js/modules/stock/shared/pieces-jointes.js') }}?v={{ time() }}"></script>
@endpush
