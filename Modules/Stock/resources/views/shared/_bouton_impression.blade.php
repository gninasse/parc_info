{{--
    Bouton d'impression d'un bon validé — commun aux trois documents.
    Ouvre l'aperçu PDF en modale (iframe) plutôt qu'un nouvel onglet.

    Paramètres : $urlPdf, $titre, $libelleArticles, $libelleEquipements,
                 $avecEquipements (bool)
--}}
@php
    $attributs = [
        'data-pdf-url' => $urlPdf,
        'data-pdf-titre' => $titre,
        'data-pdf-libelle-articles' => $libelleArticles,
        'data-pdf-libelle-equipements' => $libelleEquipements,
        'data-pdf-avec-equipements' => $avecEquipements ? '1' : '',
    ];
@endphp

<div class="btn-group">
    <button type="button" class="btn btn-outline-primary btn-sm"
            @foreach($attributs as $cle => $valeur) {{ $cle }}="{{ $valeur }}" @endforeach
            data-pdf-modele="articles">
        <i class="bi bi-printer me-1"></i>Imprimer le bon
    </button>
    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split"
            data-bs-toggle="dropdown" aria-expanded="false">
        <span class="visually-hidden">Choisir le modèle d'impression</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <button type="button" class="dropdown-item"
                    @foreach($attributs as $cle => $valeur) {{ $cle }}="{{ $valeur }}" @endforeach
                    data-pdf-modele="articles">
                <i class="bi bi-list-columns me-2"></i>{{ $libelleArticles }}
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item @unless($avecEquipements) disabled @endunless"
                    @foreach($attributs as $cle => $valeur) {{ $cle }}="{{ $valeur }}" @endforeach
                    data-pdf-modele="equipements">
                <i class="bi bi-upc-scan me-2"></i>{{ $libelleEquipements }}
                @unless($avecEquipements)
                    <div class="small text-muted">Aucun équipement sur ce bon</div>
                @endunless
            </button>
        </li>
    </ul>
</div>
