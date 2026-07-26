@extends('achat::layouts.master')

@section('title', "Modifier {$bonCommande->numero_commande} - Achat")
@section('header', "Modifier le bon de commande {$bonCommande->numero_commande}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de commande</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.show', $bonCommande) }}">{{ $bonCommande->numero_commande }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    @include('achat::bons_commande._form')
@endsection

@push('js')
<script>
    window.achatBonCommande = {
        catalogue: @json($articlesCatalogue),
        lignes: @json($lignesExistantes),
        mode: 'modification',
        urlEnregistrement: @json(route('achat.bons-commande.update', $bonCommande)),
    };
</script>
<script src="{{ asset('js/modules/achat/bons-commande/form.js') }}?v={{ time() }}"></script>
@endpush
