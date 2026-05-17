@extends('parcinfo::layouts.master')

@section('header', 'Terminaux IP')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div id="toolbar">
            <button id="btn-add" class="btn btn-primary"><i class="fas fa-plus"></i></button>
        </div>
        <table id="terminaux-table" data-toggle="table" data-url="{{ route('parc-info.terminaux-ip.data') }}" data-pagination="true" data-side-pagination="server" data-toolbar="#toolbar">
            <thead>
                <tr>
                    <th data-field="code_inventaire" data-formatter="codeFormatter">Code</th>
                    <th data-field="marque_modele">Modèle</th>
                    <th data-field="type_reseau">Type</th>
                    <th data-field="adresse_ip">IP</th>
                    <th data-field="statut_label">Statut</th>
                    <th data-field="id" data-formatter="actionsFormatter">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Wizard simplifié pour Terminaux IP --}}
<div class="modal fade" id="reseauModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Nouveau Terminal IP</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="reseauForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small fw-bold">N° Série</label><input type="text" class="form-control" name="numero_serie" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Modèle</label><input type="text" class="form-control" name="modele" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Type</label>
                        <select class="form-select" name="type_reseau_id">
                            @foreach($typesReseaux as $t)<option value="{{ $t->id }}">{{ $t->libelle }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-bold">Adresse IP</label><input type="text" class="form-control" name="adresse_ip"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
    window.codeFormatter = (val, row) => `<a href="${route('parc-info.terminaux-ip.show', row.id)}" class="fw-bold text-decoration-none">${val}</a>`;
    window.actionsFormatter = (id) => `<a href="${route('parc-info.terminaux-ip.show', id)}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a>`;
    $(function() {
        $('#btn-add').on('click', () => $('#reseauModal').modal('show'));
        $('#reseauForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({ url: route('parc-info.terminaux-ip.store'), method: 'POST', data: $(this).serialize(), success: () => { $('#reseauModal').modal('hide'); $('#terminaux-table').bootstrapTable('refresh'); } });
        });
    });
</script>
@endpush
