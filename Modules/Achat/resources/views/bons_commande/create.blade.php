@extends('achat::layouts.master')

@section('title', 'Nouveau bon de commande - Achat')
@section('header', 'Nouveau bon de commande')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de commande</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
    @include('achat::bons_commande._form')
@endsection

@push('js')
<script>
    window.achatBonCommande = {
        catalogue: @json($articlesCatalogue),
        lignes: @json($lignesExistantes),
        mode: 'creation',
        urlEnregistrement: @json(route('achat.bons-commande.store')),
    };
</script>
<script src="{{ asset('js/modules/achat/bons-commande/form.js') }}?v={{ time() }}"></script>
@endpush
