@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('content')
<div class="card border-0 shadow-sm p-4">
    <h4>{{ $equipement->code_inventaire }}</h4>
    <p>{{ $equipement->modele }} ({{ $equipement->marque?->libelle }})</p>
    <hr>
    <div class="small">IP: {{ $equipement->reseau->adresse_ip ?? 'N/A' }}</div>
</div>
@endsection
