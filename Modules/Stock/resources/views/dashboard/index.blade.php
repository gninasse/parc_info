@extends('stock::layouts.master')

@section('header', 'Tableau de bord')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
@endsection

@section('content')

<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-box-seam display-4 text-primary"></i>
        <h4 class="mt-3">Module Stock</h4>
        <p class="text-muted mb-0">
            Le tableau de bord (KPI, alertes de seuil, bons en attente de validation,
            derniers mouvements) sera disponible avec les premières pages du module.
        </p>
    </div>
</div>

@endsection
