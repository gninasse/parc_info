@extends('achat::layouts.master')

@section('title', 'Paramètres - Achat')
@section('header', 'Paramètres du module Achat')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Paramètres</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-sliders me-2 text-primary"></i>Paramétrage dynamique (EF-ADM)</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            @can('achat.parametres.edit')
            <button id="btn-edit" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Modifier le paramètre sélectionné">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
        </div>

        <table id="parametres-table"
               data-toggle="table"
               data-url="{{ route('achat.parametres.data') }}"
               data-side-pagination="server"
               data-pagination="false"
               data-search="false"
               data-show-refresh="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="libelle" class="fw-semibold">Paramètre</th>
                    <th data-field="cle" class="font-monospace">Clé</th>
                    <th data-field="valeur" data-formatter="valeurFormatter" class="text-center">Valeur</th>
                    <th data-field="description">Description</th>
                    <th data-field="modifiable" data-formatter="modifiableFormatter" class="text-center">Modifiable</th>
                    <th data-field="updated_at" data-formatter="dateHeureFormatter" class="text-end">Dernière modification</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- ── MODAL ÉDITION ───────────────────────────────────────────────────── --}}
<div class="modal fade" id="parametreModal" tabindex="-1" aria-labelledby="parametreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-primary bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="parametreModalLabel">
                    <i class="fas fa-sliders me-2 text-primary"></i><span id="parametre-libelle"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="parametre-form">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small" id="parametre-description"></p>
                    <div class="mb-0">
                        <label class="form-label" for="parametre-valeur">Valeur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="parametre-valeur" name="valeur" required maxlength="255">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/achat/parametres.js') }}?v={{ time() }}"></script>
@endpush
