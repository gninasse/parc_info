{{--
    Page 403 nominative (SPEC_UX §0.5 · DESIGN.md).

    Une 403 muette laisse l'utilisateur sans recours et l'administrateur sans
    diagnostic. Celle-ci nomme la permission manquante : son LIBELLÉ MÉTIER
    (ce que la personne voulait faire) et son NOM TECHNIQUE (ce que
    l'administrateur doit accorder dans l'écran des rôles).

    Variables optionnelles : $permissionsRequises (list<array{name, label}>)
--}}
@extends('core::layouts.master')

@section('title', 'Accès refusé')

@section('header', 'Accès refusé')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Accès refusé</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5 text-center">
                <i class="bi bi-shield-lock fs-1 text-warning d-block mb-3"></i>

                <h4 class="fw-bold mb-2">Vous n'avez pas l'autorisation d'accéder à cette page.</h4>

                @if(! empty($permissionsRequises))
                    <p class="text-muted mb-4">
                        Cette page exige {{ count($permissionsRequises) > 1 ? 'les autorisations suivantes' : "l'autorisation suivante" }} :
                    </p>

                    <div class="d-flex flex-column gap-2 align-items-center mb-4">
                        @foreach($permissionsRequises as $permission)
                            <div class="border rounded px-3 py-2 bg-body-tertiary text-start" style="max-width: 100%;">
                                <div class="fw-semibold">{{ $permission['label'] }}</div>
                                {{-- Le nom technique est ce que l'administrateur cherchera dans la matrice --}}
                                <code class="small text-muted">{{ $permission['name'] }}</code>
                            </div>
                        @endforeach
                    </div>

                    <p class="small text-muted mb-4">
                        Demandez à votre administrateur de vous attribuer un rôle qui la porte.
                    </p>
                @else
                    <p class="text-muted mb-4">
                        {{ $exceptionMessage ?? "Cette action est réservée à d'autres profils d'utilisateurs." }}
                    </p>
                @endif

                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="bi bi-house-door me-1"></i>Accueil général
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
