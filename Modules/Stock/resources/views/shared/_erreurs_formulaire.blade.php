{{--
    Bandeau récapitulatif des erreurs de validation — commun à tous les
    formulaires du module (UX §0.6 : « is-invalid + invalid-feedback champ
    par champ, lignes dynamiques surlignées »).

    Le conteneur est vide au chargement ; il est rempli par
    js/modules/stock/shared/erreurs-formulaire.js à la réception d'un 422.
    Paramètre optionnel : $id (défaut « erreurs-formulaire ») pour cohabiter
    avec une modale sur la même page.
--}}
<div id="{{ $id ?? 'erreurs-formulaire' }}" class="bandeau-erreurs d-none" role="alert" aria-live="assertive" tabindex="-1"></div>

@once
@push('css')
<style>
    /* ── Bandeau récapitulatif ─────────────────────────────────────────── */
    .bandeau-erreurs {
        border: 1px solid var(--bs-danger-border-subtle, #f1aeb5);
        border-left: 4px solid var(--bs-danger, #dc3545);
        background: var(--bs-danger-bg-subtle, #fdf2f3);
        border-radius: .5rem;
        padding: .9rem 1rem;
        margin-bottom: 1rem;
        animation: erreurs-apparition .18s ease-out;
    }
    .bandeau-erreurs.bandeau-blocage {
        border-color: var(--bs-warning-border-subtle, #ffe69c);
        border-left-color: var(--bs-warning, #ffc107);
        background: var(--bs-warning-bg-subtle, #fff9e6);
    }
    @keyframes erreurs-apparition {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .bandeau-erreurs { animation: none; }
    }

    .bandeau-erreurs .titre-erreurs {
        display: flex; align-items: center; gap: .5rem;
        font-weight: 600; margin-bottom: .5rem;
    }
    .bandeau-erreurs .titre-erreurs .compteur {
        font-variant-numeric: tabular-nums;
    }
    .bandeau-erreurs ul { list-style: none; margin: 0; padding: 0; }
    .bandeau-erreurs li + li { margin-top: .15rem; }

    /* Chaque erreur est un lien vers son champ */
    .bandeau-erreurs .lien-erreur {
        display: flex; align-items: baseline; gap: .4rem;
        width: 100%; text-align: left;
        background: none; border: 0; padding: .25rem .35rem;
        border-radius: .35rem; color: inherit; font-size: .9rem;
        transition: background-color .12s;
    }
    .bandeau-erreurs .lien-erreur:hover,
    .bandeau-erreurs .lien-erreur:focus-visible {
        background: rgba(220, 53, 69, .08);
        text-decoration: none;
    }
    .bandeau-erreurs .champ-erreur {
        font-weight: 600;
        white-space: nowrap;
    }
    .bandeau-erreurs .champ-erreur::after { content: ' —'; font-weight: 400; }

    /* ── Champs en erreur ──────────────────────────────────────────────── */
    /* Select2 : le contrôle réel est masqué, on souligne le rendu */
    .select2-container--bootstrap-5.est-invalide .select2-selection {
        border-color: var(--bs-form-invalid-border-color, #dc3545);
    }
    /* Groupes non-input (cartes de bénéficiaire, zone de dépôt) */
    .bloc-invalide {
        border-radius: .5rem;
        box-shadow: 0 0 0 2px var(--bs-danger, #dc3545);
    }

    /* ── Lignes de tableau en erreur ───────────────────────────────────── */
    tr.ligne-en-erreur > td {
        background-color: var(--bs-danger-bg-subtle, #fdf2f3) !important;
    }
    tr.ligne-en-erreur > td:first-child {
        box-shadow: inset 3px 0 0 0 var(--bs-danger, #dc3545);
    }
    .message-ligne {
        display: block;
        font-size: .8rem;
        color: var(--bs-danger-text-emphasis, #b02a37);
        margin-top: .15rem;
    }
</style>
@endpush

@push('js')
<script type="module" src="{{ asset('js/modules/stock/shared/erreurs-formulaire.js') }}?v={{ time() }}"></script>
@endpush
@endonce
