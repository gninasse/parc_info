@extends('stock::layouts.master')

@section('title', 'Saisie de Comptage Inventaire')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fas fa-pencil-alt me-2 text-warning"></i>Saisie de Comptage : {{ $inventaire->numero_inventaire }}
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stock</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('stock.inventaires.index') }}">Inventaires</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Saisie</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('stock.inventaires.index') }}" class="btn btn-secondary btn-sm rounded-1">
                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
            </a>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card border-0 shadow-sm rounded-1 mb-4">
        <div class="card-body bg-light small">
            <div class="row text-center text-md-start">
                <div class="col-md-4 mb-2 mb-md-0">
                    <span class="text-uppercase fw-semibold text-secondary d-block mb-1">Magasin :</span>
                    <strong class="text-dark fs-6">{{ $inventaire->magasin?->nom }}</strong>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <span class="text-uppercase fw-semibold text-secondary d-block mb-1">Date d'initialisation :</span>
                    <strong class="text-dark fs-6">{{ $inventaire->date_inventaire?->toLocaleDateString('fr-FR') }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-uppercase fw-semibold text-secondary d-block mb-1">Nombre d'articles figés :</span>
                    <strong class="text-dark fs-6">{{ $inventaire->nombre_articles }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire de comptage -->
    <form id="saisie-inventaire-form">
        @csrf
        <div class="card border-0 shadow-sm rounded-1 mb-3">
            <div class="card-body px-0 py-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Code Article</th>
                                <th>Désignation Article</th>
                                <th class="text-end" style="width: 180px;">Quantité Théorique (Stock)</th>
                                <th class="text-center" style="width: 180px;">Quantité Réelle (Comptée) <span class="text-danger">*</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventaire->lignes as $l)
                                <tr>
                                    <td class="ps-4"><code>{{ $l->article?->code_article }}</code></td>
                                    <td><strong>{{ $l->article?->designation }}</strong></td>
                                    <td class="text-end fs-6 fw-semibold text-secondary">{{ $l->quantite_theorique }}</td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            <input type="number" 
                                                   class="form-control form-control-sm text-center fw-bold text-primary input-qte-reelle" 
                                                   name="saisies[{{ $l->id }}]" 
                                                   value="{{ $l->quantite_reelle }}" 
                                                   min="0" 
                                                   style="width: 100px;">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Boutons d'actions -->
        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-warning text-dark btn-sm rounded-1 fw-semibold" id="btn-submit-saisie">
                <i class="fas fa-save me-1"></i> Enregistrer le comptage
            </button>
            
            @can('stock.inventaires.admin')
                <button type="button" class="btn btn-success btn-sm rounded-1 fw-semibold" id="btn-validate-from-saisie" data-id="{{ $inventaire->id }}">
                    <i class="fas fa-check-double me-1"></i> Clôturer & Valider l'inventaire
                </button>
            @endcan
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script type="module">
    $(function() {
        // Soumission asynchrone des saisies
        $('#saisie-inventaire-form').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#btn-submit-saisie');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url: "{{ route('stock.inventaires.saisie.store', $inventaire->id) }}",
                method: "POST",
                data: $(this).serialize(),
                success: function(res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Enregistré', text: res.message, timer: 2000 });
                    }
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible d\'enregistrer.' });
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer le comptage');
                }
            });
        });

        // Clôture depuis cette page
        $('#btn-validate-from-saisie').on('click', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Clôturer et valider l\'inventaire ?',
                text: 'Les écarts constatés seront automatiquement régularisés en stock de façon irréversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Oui, valider l\'ajustement',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: route('stock.inventaires.valider', id),
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({ 
                                icon: 'success', 
                                title: 'Clôturé', 
                                text: res.message,
                                timer: 2500 
                            }).then(() => {
                                window.location.href = "{{ route('stock.inventaires.index') }}";
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible de valider.' });
                    }
                });
            });
        });
    });
</script>
@endpush
