@extends('achat::layouts.master')

@section('header', 'Mes notifications')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item active" aria-current="page">Mes notifications</li>
@endsection

@push('css')
<style>
    .carte-notification { border: 1px solid var(--bs-border-color); }
</style>
@endpush

@section('content')

<div class="alert alert-light border d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-info-circle text-primary mt-1"></i>
    <div>
        <p class="mb-1">
            Ces réglages sont <strong>les vôtres</strong> : ils n'affectent personne d'autre.
        </p>
        <p class="small text-muted mb-0">
            Vous ne recevez que ce qui vous concerne : un bon à viser ne part qu'aux personnes
            habilitées à viser, une livraison qu'à l'auteur du bon.
            @unless($peutViser)
                Vous n'avez pas le droit de viser : la première ligne ne vous concerne donc pas.
            @endunless
        </p>
    </div>
</div>

<div class="row g-3" id="cartes-notification">
    @foreach($preferences as $preference)
        <div class="col-md-6">
            <div class="card h-100 carte-notification" data-type="{{ $preference['type'] }}">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ $preference['libelle'] }}</h6>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input bascule-cloche" type="checkbox"
                               id="cloche-{{ $preference['type'] }}"
                               @checked($preference['par_cloche'])>
                        <label class="form-check-label" for="cloche-{{ $preference['type'] }}">
                            <i class="bi bi-bell me-1"></i>Dans l'application
                        </label>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input bascule-mail" type="checkbox"
                               id="mail-{{ $preference['type'] }}"
                               @checked($preference['par_mail'])>
                        <label class="form-check-label" for="mail-{{ $preference['type'] }}">
                            <i class="bi bi-envelope me-1"></i>Par courriel
                        </label>
                    </div>

                    <div class="small text-muted mt-2 etat-enregistrement"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@endsection

@push('js')
<script>
    // Une carte = un PATCH, comme l'écran d'administration : chaque réglage
    // s'enregistre seul, sans bouton « Enregistrer tout » qui laisserait
    // l'utilisateur dans le doute sur ce qui a été pris en compte.
    $(function () {
        $('#cartes-notification').on('change', '.bascule-cloche, .bascule-mail', function () {
            const $carte = $(this).closest('.carte-notification');
            const type = $carte.data('type');
            const $etat = $carte.find('.etat-enregistrement');

            $etat.removeClass('text-danger text-success').addClass('text-muted').text('Enregistrement…');

            $.ajax({
                url: '{{ url('achat/preferences-notification') }}/' + type,
                method: 'PATCH',
                data: JSON.stringify({
                    par_cloche: $carte.find('.bascule-cloche').is(':checked'),
                    par_mail: $carte.find('.bascule-mail').is(':checked'),
                }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(() => $etat.removeClass('text-muted').addClass('text-success').text('✓ Enregistré'))
                .fail((xhr) => $etat.removeClass('text-muted').addClass('text-danger')
                    .text(xhr.responseJSON?.message ?? 'Échec de l\'enregistrement'));
        });
    });
</script>
@endpush
