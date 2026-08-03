{{--
    Sélecteurs modaux de bénéficiaire ×6 (S11 — UX §0.5) : copie adaptée du
    pattern `_selection_modals` de ParcInfo, partagée par les écrans de
    documents (sortie, transfert…). Les contrôleurs fournissent $directions
    et $sites ; chaque modale reste incluable individuellement.

    @include('stock::shared._selection_modals._tous')
--}}
@include('stock::shared._selection_modals._employe')
@include('stock::shared._selection_modals._poste')
@include('stock::shared._selection_modals._local')
@include('stock::shared._selection_modals._direction')
@include('stock::shared._selection_modals._service')
@include('stock::shared._selection_modals._unite')
