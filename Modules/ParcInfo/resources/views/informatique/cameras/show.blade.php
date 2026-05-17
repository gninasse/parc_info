@extends('parcinfo::layouts.master')
@section('header', $equipement->code_inventaire)
@section('content')
<div class="card border-0 shadow-sm p-4">
    <h4>{{ $equipement->code_inventaire }}</h4>
    <p>{{ $equipement->modele }}</p>
    <div class="badge bg-primary">IP: {{ $equipement->camera->adresse_ip }}</div>
</div>
@endsection
