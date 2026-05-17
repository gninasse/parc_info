@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.telephonie.index') }}">Téléphonie</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="row">
        <div class="col-md-8">
            <h4 class="fw-bold">{{ $equipement->code_inventaire }}</h4>
            <div class="text-muted small">Modèle: {{ $equipement->modele }} | Marque: {{ $equipement->marque?->libelle ?? 'N/A' }}</div>
            <div class="mt-3">
                <span class="badge bg-primary">Extension: {{ $equipement->telephone->extension ?? '—' }}</span>
                <span class="badge bg-info">Type: {{ $equipement->telephone->est_ip ? 'IP' : 'Analogique' }}</span>
                <span class="badge bg-secondary">IP: {{ $equipement->telephone->adresse_ip ?? '—' }}</span>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-danger btn-sm" id="btn-delete">Supprimer</button>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    $('#btn-delete').on('click', function() {
        Swal.fire({ title: 'Supprimer ?', showCancelButton: true }).then(r => {
            if (r.isConfirmed) {
                $.ajax({ url: `{{ route('parc-info.telephonie.destroy', $equipement->id) }}`, method: 'DELETE', success: () => window.location.href = `{{ route('parc-info.telephonie.index') }}` });
            }
        });
    });
});
</script>
@endpush
