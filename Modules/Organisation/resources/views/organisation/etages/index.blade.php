@extends('core::layouts.master')

@section('header', 'Gestion des Étages')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Organisation</li>
    <li class="breadcrumb-item active" aria-current="page">Étages</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">

<script>
// Status formatter
window.statusFormatter = function(value, row) {
    if (row.actif) {
        return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Actif</span>';
    }
    return '<span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inactif</span>';
};

// Toggle status handler
$(document).ready(function() {
    $('#btn-toggle-status').on('click', function() {
        const selections = $('#etages-table').bootstrapTable('getSelections');
        if (selections.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Attention',
                text: 'Veuillez sélectionner une ligne'
            });
            return;
        }
        
        const id = selections[0].id;
        const actif = selections[0].actif;
        const action = actif ? 'désactiver' : 'activer';
        
        Swal.fire({
            title: 'Confirmer l\'action',
            text: \`Voulez-vous vraiment ${action} cet élément ?\`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, confirmer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#ffc107',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('organisation.etages.toggle-status', id),
                    type: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#etages-table').bootstrapTable('refresh');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: xhr.responseJSON?.message || 'Une erreur est survenue'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des étages</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="filter_site_id" class="form-label">Filtrer par Site</label>
                <select class="form-select" id="filter_site_id">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_batiment_id" class="form-label">Filtrer par Bâtiment</label>
                <select class="form-select" id="filter_batiment_id" disabled>
                    <option value="">Tous les bâtiments</option>
                </select>
            </div>
        </div>

        <div id="toolbar">
            @can('cores.organisation.etages.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('cores.organisation.etages.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('cores.organisation.etages.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
            @can('cores.organisation.etages.toggle-status')
            <button id="btn-toggle-status" class="btn btn-warning" disabled data-bs-toggle="tooltip" title="Activer/Désactiver">
                <i class="fas fa-power-off"></i>
            </button>
            @endcan
        </div>

        <table id="etages-table"
               data-toggle="table"
               data-url="{{ route('organisation.etages.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]">
            <thead>
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="numero" data-sortable="true">Numéro</th>
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="batiment.libelle" data-sortable="true">Bâtiment</th>
                    <th data-field="batiment.site.libelle" data-sortable="true">Site</th>
                    <th data-field="actif" data-sortable="true" data-formatter="statutFormatter">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('organisation::organisation.etages._modal')
@stop

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    window.etageRoutes = {
        batimentsBySite: "{{ route('organisation.batiments.by-site', ['siteId' => ':siteId']) }}",
        data: "{{ route('organisation.etages.data') }}"
    };

    function statutFormatter(value) {
        return value 
            ? '<span class="badge bg-success">Actif</span>' 
            : '<span class="badge bg-danger">Inactif</span>';
    }
</script>

<script type="module" src="{{ asset('js/modules/organisation/etages/index.js') }}"></script>
@endpush
