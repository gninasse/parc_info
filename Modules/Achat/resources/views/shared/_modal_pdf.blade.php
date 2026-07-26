{{-- Aperçu PDF partagé (M-06, M-07, M-08) --}}
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6" id="{{ $id }}-label">
                    <i class="fas fa-file-pdf me-2 text-danger"></i>{{ $titre }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="{{ $id }}-iframe" class="w-100" style="height: 72vh; border: none;" src="" title="{{ $titre }}"></iframe>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-1 px-3" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
