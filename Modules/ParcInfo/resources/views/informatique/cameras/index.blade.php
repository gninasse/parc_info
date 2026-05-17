@extends('parcinfo::layouts.master')
@section('header', 'Caméras IP')
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div id="toolbar"><button id="btn-add" class="btn btn-primary"><i class="fas fa-plus"></i></button></div>
        <table id="cameras-table" data-toggle="table" data-url="{{ route('parc-info.cameras.data') }}" data-pagination="true" data-side-pagination="server" data-toolbar="#toolbar">
            <thead>
                <tr>
                    <th data-field="code_inventaire">Code</th>
                    <th data-field="marque_modele">Modèle</th>
                    <th data-field="adresse_ip">IP</th>
                    <th data-field="affectation">Local</th>
                    <th data-field="id" data-formatter="actionsFormatter">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="cameraModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Nouvelle Caméra</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="cameraForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small">N° Série</label><input type="text" class="form-control" name="numero_serie" required></div>
                    <div class="mb-3"><label class="form-label small">Modèle</label><input type="text" class="form-control" name="modele" required></div>
                    <div class="mb-3"><label class="form-label small">Adresse IP</label><input type="text" class="form-control" name="adresse_ip"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('js')
<script>
    window.actionsFormatter = (id) => `<a href="${route('parc-info.cameras.show', id)}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a>`;
    $(function() {
        $('#btn-add').on('click', () => $('#cameraModal').modal('show'));
        $('#cameraForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({ url: route('parc-info.cameras.store'), method: 'POST', data: $(this).serialize(), success: () => { $('#cameraModal').modal('hide'); $('#cameras-table').bootstrapTable('refresh'); } });
        });
    });
</script>
@endpush
